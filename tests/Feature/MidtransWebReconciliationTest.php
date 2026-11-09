<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\PaymentTransaction;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\MidtransCallbackService;
use App\Services\MidtransStatusGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MidtransWebReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
        config()->set('midtrans.server_key', 'sandbox-test-key');
        config()->set('midtrans.is_production', false);
    }

    private function buatTransaksi(): PaymentTransaction
    {
        $user = User::create([
            'no_hp_email' => 'reconciliation@example.com',
            'status_otp' => true,
        ]);

        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900003',
            'nik_hash' => hash(
                'sha256',
                '3576010101900003'
            ),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $slotId = (string) Str::uuid();

        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 100,
            'kuota_terpakai' => 0,
        ]);

        $tariff = TariffReference::create([
            'jenis_pengunjung' => 'LUAR_KOTA',
            'nominal' => 50000,
            'berlaku_mulai' => now()
                ->subDay()
                ->toDateString(),
            'aktif' => true,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'DRAFT',
        ]);

        return PaymentTransaction::create([
            'booking_id' => $booking->id,
            'kode_bayar' => 'GOR-' . Str::uuid(),
            'metode_pembayaran' => 'MIDTRANS',
            'nominal' => 50000,
            'status_pembayaran' => 'PENDING',
            'jumlah_retry' => 0,
            'referensi_gateway' => null,
            'expired_at' => now()->addHours(24),
            'snap_token' => 'token-testing',
        ]);
    }


    private function gateway(PaymentTransaction $payment, string $status, string $amount = '50000.00'): void
    {
        $gateway = Mockery::mock(MidtransStatusGateway::class);
        $gateway->shouldReceive('getStatus')->once()->with($payment->kode_bayar)
            ->andReturn((object) [
                'order_id' => $payment->kode_bayar,
                'gross_amount' => $amount,
                'transaction_status' => $status,
                'status_code' => '200',
                'fraud_status' => 'accept',
            ]);
        $this->app->instance(MidtransStatusGateway::class, $gateway);
    }

    public function test_pending_gateway_tidak_mengonfirmasi_booking(): void
    {
        $payment = $this->buatTransaksi();
        $this->gateway($payment, 'pending');
        $result = app(MidtransCallbackService::class)->reconcile($payment->kode_bayar);
        $this->assertSame('PENDING', $result['status']);
        $this->assertSame('DRAFT', $payment->booking->fresh()->status_booking);
        $this->assertSame(0, DB::table('payment_gateway_callbacks')->count());
    }

    public function test_nominal_tidak_cocok_tidak_mengubah_status(): void
    {
        $payment = $this->buatTransaksi();
        $this->gateway($payment, 'settlement', '40000.00');
        try {
            app(MidtransCallbackService::class)->reconcile($payment->kode_bayar);
            $this->fail('Nominal tidak cocok harus ditolak.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('PENDING', $payment->fresh()->status_pembayaran);
            $this->assertSame('DRAFT', $payment->booking->fresh()->status_booking);
        }
    }

    public function test_web_pengunjung_lain_tidak_boleh_sinkronisasi(): void
    {
        $payment = $this->buatTransaksi();
        $otherUser = User::create(['no_hp_email' => 'other-reconcile@example.com', 'status_otp' => true]);
        $this->actingAs($otherUser, 'web')
            ->post(route('booking.payment.sync', ['booking' => $payment->booking_id]))
            ->assertForbidden();
        $this->assertSame('PENDING', $payment->fresh()->status_pembayaran);
    }

    public function test_web_tamu_tidak_boleh_sinkronisasi(): void
    {
        $payment = $this->buatTransaksi();
        $response = $this->post(route('booking.payment.sync', ['booking' => $payment->booking_id]));
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403], true));
        $this->assertSame('PENDING', $payment->fresh()->status_pembayaran);
    }
}
