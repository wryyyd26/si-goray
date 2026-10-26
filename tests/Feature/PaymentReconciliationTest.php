<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\PaymentTransaction;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use App\Services\MidtransStatusGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new RuntimeException(
                'Database testing tidak sesuai.'
            );
        }

        config()->set(
            'midtrans.server_key',
            'sandbox-key-for-automated-tests'
        );

        config()->set(
            'midtrans.is_production',
            false
        );
    }

    /**
     * Membuat transaksi pembayaran khusus testing.
     */
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

    /**
     * Membuat gateway palsu untuk pengujian.
     * Tidak ada request HTTP ke Midtrans.
     */
    private function gatewayDenganStatus(
        PaymentTransaction $payment,
        string $status,
        ?string $orderId = null,
        string $amount = '50000.00'
    ): MidtransStatusGateway {
        $gateway = Mockery::mock(
            MidtransStatusGateway::class
        );

        $gateway->shouldReceive('getStatus')
            ->once()
            ->with($payment->kode_bayar)
            ->andReturn((object) [
                'order_id' => $orderId
                    ?? $payment->kode_bayar,
                'gross_amount' => $amount,
                'transaction_status' => $status,
                'status_code' => '200',
                'fraud_status' => 'accept',
            ]);

        return $gateway;
    }

    /**
     * Helper untuk memeriksa status gateway
     * tanpa mengubah database.
     */
    private function periksaStatus(
        string $status,
        string $expectedAction
    ): void {
        $payment = $this->buatTransaksi();

        $service = new PaymentReconciliationService(
            $this->gatewayDenganStatus(
                $payment,
                $status
            )
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            $status,
            $hasil['status_gateway']
        );

        $this->assertSame(
            $expectedAction,
            $hasil['tindakan']
        );

        $this->assertSame(
            'PENDING',
            $payment->fresh()->status_pembayaran
        );

        $this->assertSame(
            'DRAFT',
            $payment->booking
                ->fresh()
                ->status_booking
        );
    }

    /**
     * TEST 1:
     * Pembayaran lokal SUCCESS tidak perlu
     * menghubungi Midtrans lagi.
     */
    public function test_success_lokal_tidak_memanggil_gateway(): void
    {
        $payment = $this->buatTransaksi();

        $payment->status_pembayaran = 'SUCCESS';
        $payment->save();

        $gateway = Mockery::mock(
            MidtransStatusGateway::class
        );

        $gateway->shouldNotReceive('getStatus');

        $service = new PaymentReconciliationService(
            $gateway
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            'SUDAH_BERHASIL',
            $hasil['tindakan']
        );

        $this->assertSame(
            'SUCCESS',
            $payment->fresh()->status_pembayaran
        );
    }

    /**
     * TEST 2:
     * Order tidak ditemukan pada Midtrans.
     */
    public function test_gateway_not_found_tidak_mengubah_status(): void
    {
        $payment = $this->buatTransaksi();

        $gateway = Mockery::mock(
            MidtransStatusGateway::class
        );

        $gateway->shouldReceive('getStatus')
            ->once()
            ->with($payment->kode_bayar)
            ->andThrow(
                new RuntimeException(
                    "HTTP status code: 404 Transaction doesn't exist"
                )
            );

        $service = new PaymentReconciliationService(
            $gateway
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            'NOT_FOUND',
            $hasil['status_gateway']
        );

        $this->assertSame(
            'PERLU_REKONSILIASI',
            $hasil['tindakan']
        );

        $this->assertSame(
            'PENDING',
            $payment->fresh()->status_pembayaran
        );
    }

    /**
     * TEST 3:
     * Gateway timeout atau tidak tersedia.
     */
    public function test_gateway_timeout_tidak_mengubah_status(): void
    {
        $payment = $this->buatTransaksi();

        $gateway = Mockery::mock(
            MidtransStatusGateway::class
        );

        $gateway->shouldReceive('getStatus')
            ->once()
            ->with($payment->kode_bayar)
            ->andThrow(
                new RuntimeException('Simulasi timeout')
            );

        $service = new PaymentReconciliationService(
            $gateway
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            'UNAVAILABLE',
            $hasil['status_gateway']
        );

        $this->assertSame(
            'COBA_PERIKSA_KEMBALI',
            $hasil['tindakan']
        );

        $this->assertSame(
            'PENDING',
            $payment->fresh()->status_pembayaran
        );
    }

    /**
     * TEST 4:
     * Gateway masih berstatus pending.
     */
    public function test_gateway_pending_tetap_menunggu(): void
    {
        $this->periksaStatus(
            'pending',
            'MASIH_MENUNGGU'
        );
    }

    /**
     * TEST 5:
     * Gateway settlement memerlukan verifikasi.
     */
    public function test_gateway_settlement_memerlukan_verifikasi(): void
    {
        $this->periksaStatus(
            'settlement',
            'VERIFIKASI_PEMBAYARAN'
        );
    }

    /**
     * TEST 6:
     * Gateway expire memerlukan proses status akhir.
     */
    public function test_gateway_expire_memerlukan_proses_status_akhir(): void
    {
        $this->periksaStatus(
            'expire',
            'PROSES_STATUS_AKHIR'
        );
    }

    /**
     * TEST 7:
     * Gateway cancel memerlukan proses status akhir.
     */
    public function test_gateway_cancel_memerlukan_proses_status_akhir(): void
    {
        $this->periksaStatus(
            'cancel',
            'PROSES_STATUS_AKHIR'
        );
    }

    /**
     * TEST 8:
     * Gateway deny memerlukan proses status akhir.
     */
    public function test_gateway_deny_memerlukan_proses_status_akhir(): void
    {
        $this->periksaStatus(
            'deny',
            'PROSES_STATUS_AKHIR'
        );
    }

    /**
     * TEST 9:
     * Order ID gateway tidak sesuai.
     */
    public function test_order_mismatch_ditandai_review(): void
    {
        $payment = $this->buatTransaksi();

        $gateway = $this->gatewayDenganStatus(
            $payment,
            'settlement',
            'GOR-ORDER-LAIN'
        );

        $service = new PaymentReconciliationService(
            $gateway
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            'ORDER_MISMATCH',
            $hasil['status_gateway']
        );

        $this->assertSame(
            'REVIEW',
            $hasil['tindakan']
        );

        $this->assertSame(
            'PENDING',
            $payment->fresh()->status_pembayaran
        );
    }

    /**
     * TEST 10:
     * Nominal gateway berbeda dari database.
     */
    public function test_nominal_mismatch_ditandai_review(): void
    {
        $payment = $this->buatTransaksi();

        $gateway = $this->gatewayDenganStatus(
            $payment,
            'settlement',
            null,
            '60000.00'
        );

        $service = new PaymentReconciliationService(
            $gateway
        );

        $hasil = $service->inspect($payment->id);

        $this->assertSame(
            'AMOUNT_MISMATCH',
            $hasil['status_gateway']
        );

        $this->assertSame(
            'REVIEW',
            $hasil['tindakan']
        );

        $this->assertSame(
            'PENDING',
            $payment->fresh()->status_pembayaran
        );
    }
}
