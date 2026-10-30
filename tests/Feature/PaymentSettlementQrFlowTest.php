<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\MidtransSnapGateway;
use App\Services\MidtransStatusGateway;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PaymentSettlementQrFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh menggunakan gor_reservation_testing.');
        }

        // Kunci dummy dipakai hanya pada proses pengujian.
        config()->set('midtrans.server_key', 'sigoray-test-midtrans-secret');
        config()->set('midtrans.is_production', false);
    }

    private function createDraftBooking(): Booking
    {
        $user = User::create([
            'no_hp_email' => 'settlement-qr-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $tariff = TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 25000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        $slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 200,
            'kuota_terpakai' => 1,
            'status' => 'active',
        ]);

        return Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'DRAFT',
        ]);
    }

    public function test_settlement_mengonfirmasi_booking_dan_menerbitkan_satu_qr(): void
    {
        $booking = $this->createDraftBooking();

        $this->mock(MidtransSnapGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createToken')
                ->once()
                ->andReturn('snap-token-dummy-testing');
        });

        Sanctum::actingAs($booking->user, ['booking:read']);

        $this->postJson("/api/bookings/{$booking->id}/payment")
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_pembayaran', 'PENDING')
            ->assertJsonPath('data.snap_token', 'snap-token-dummy-testing');

        $payment = DB::table('payment_transactions')
            ->where('booking_id', $booking->id)
            ->first();

        $this->assertNotNull($payment);
        $this->assertSame('DRAFT', $booking->fresh()->status_booking);
        $this->assertSame(0, DB::table('qr_tickets')->where('booking_id', $booking->id)->count());

        $this->mock(MidtransStatusGateway::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('getStatus')
                ->twice()
                ->with($payment->kode_bayar)
                ->andReturn((object) [
                    'order_id' => $payment->kode_bayar,
                    'gross_amount' => '25000.00',
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'fraud_status' => 'accept',
                ]);
        });

        $callback = [
            'order_id' => $payment->kode_bayar,
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'transaction_status' => 'settlement',
        ];
        $callback['signature_key'] = hash(
            'sha512',
            $callback['order_id']
                . $callback['status_code']
                . $callback['gross_amount']
                . config('midtrans.server_key')
        );

        $this->postJson('/api/payments/callback', $callback)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $payment->id,
            'status_pembayaran' => 'SUCCESS',
        ]);
        $this->assertSame('CONFIRMED', $booking->fresh()->status_booking);

        $qr = DB::table('qr_tickets')->where('booking_id', $booking->id)->first();
        $this->assertNotNull($qr);
        $this->assertSame('AKTIF', $qr->status_qr);
        $this->assertTrue(app(QrTicketService::class)->validate($qr->payload_signed)['valid']);

        // Endpoint yang dipanggil pengunjung harus mengembalikan QR yang sama.
        $this->postJson("/api/bookings/{$booking->id}/qr-ticket")
            ->assertOk()
            ->assertJsonPath('data.id', $qr->id);

        // Callback settlement yang berulang tidak boleh membuat QR kedua.
        $this->postJson('/api/payments/callback', $callback)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, DB::table('qr_tickets')->where('booking_id', $booking->id)->count());
        $this->assertSame('SUCCESS', DB::table('payment_transactions')
            ->where('id', $payment->id)->value('status_pembayaran'));
    }
}
