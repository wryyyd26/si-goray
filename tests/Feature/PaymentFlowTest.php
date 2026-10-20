<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\PaymentTransaction;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\MidtransSnapGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan pernah menjalankan test ini
        // pada database development.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new RuntimeException(
                'Database testing tidak sesuai.'
            );
        }

        // Hanya konfigurasi uji, bukan kredensial Midtrans asli.
        config()->set(
            'midtrans.server_key',
            'sandbox-key-for-automated-tests'
        );

        config()->set('midtrans.is_production', false);
    }

    /**
     * Membuat booking REGULER yang memenuhi
     * seluruh persyaratan pembayaran.
     */
    private function buatBookingValid(): Booking
    {
        $user = User::create([
            'no_hp_email' => 'testing@example.com',
            'status_otp' => true,
        ]);

        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900001',
            'nik_hash' => hash(
                'sha256',
                '3576010101900001'
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
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
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

    /**
     * Membuat PaymentService dengan gateway palsu.
     * Tidak ada request HTTP ke Midtrans.
     */
    private function serviceDenganToken(int $jumlahPanggilan): PaymentService
    {
        $gateway = Mockery::mock(MidtransSnapGateway::class);

        $gateway->shouldReceive('createToken')
            ->times($jumlahPanggilan)
            ->andReturn('snap-token-testing');

        return new PaymentService($gateway);
    }

    public function test_booking_tidak_ditemukan_ditolak(): void
    {
        $service = $this->serviceDenganToken(0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Booking tidak ditemukan.'
        );

        $service->createPayment(
            '00000000-0000-0000-0000-000000000001'
        );
    }

    public function test_pembayaran_pertama_berhasil_dibuat(): void
    {
        $booking = $this->buatBookingValid();
        $service = $this->serviceDenganToken(1);

        $hasil = $service->createPayment($booking->id);

        $payment = PaymentTransaction::where(
            'booking_id',
            $booking->id
        )->firstOrFail();

        $this->assertSame('PENDING', $payment->status_pembayaran);
        $this->assertSame(0, (int) $payment->jumlah_retry);
        $this->assertSame(50000.0, (float) $payment->nominal);

        $this->assertSame(
            'snap-token-testing',
            $hasil['snap_token']
        );

        $this->assertSame($payment->id, $hasil['transaction_id']);

        $this->assertSame(
            1,
            PaymentTransaction::where(
                'booking_id',
                $booking->id
            )->count()
        );
    }

    public function test_pending_menggunakan_transaksi_yang_sama(): void
    {
        $booking = $this->buatBookingValid();
        $service = $this->serviceDenganToken(1);

        $pertama = $service->createPayment($booking->id);
        $kedua = $service->createPayment($booking->id);

        $this->assertSame(
            $pertama['transaction_id'],
            $kedua['transaction_id']
        );

        $this->assertSame(
            $pertama['snap_token'],
            $kedua['snap_token']
        );

        $this->assertSame(
            1,
            PaymentTransaction::where(
                'booking_id',
                $booking->id
            )->count()
        );
    }

    public function test_tiga_retry_diizinkan_dan_percobaan_kelima_ditolak(): void
    {
        $booking = $this->buatBookingValid();

        // Pembayaran pertama + tiga retry.
        $service = $this->serviceDenganToken(4);

        for ($retry = 0; $retry < 4; $retry++) {
            $hasil = $service->createPayment($booking->id);

            $payment = PaymentTransaction::findOrFail(
                $hasil['transaction_id']
            );

            $this->assertSame(
                $retry,
                (int) $payment->jumlah_retry
            );

            // Simulasi status akhir dari gateway.
            // Ini hanya dilakukan di database testing.
            $payment->status_pembayaran = 'FAILED';
            $payment->save();
        }

        $this->assertSame(
            4,
            PaymentTransaction::where(
                'booking_id',
                $booking->id
            )->count()
        );

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Batas maksimal 3 kali retry pembayaran'
        );

        $service->createPayment($booking->id);
    }

    public function test_booking_confirmed_tidak_boleh_dibayar_ulang(): void
    {
        $booking = $this->buatBookingValid();

        $booking->status_booking = 'CONFIRMED';
        $booking->save();

        $service = $this->serviceDenganToken(0);

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Booking tidak dapat diproses untuk pembayaran.'
        );

        $service->createPayment($booking->id);
    }

    public function test_kegagalan_gateway_tetap_menyimpan_pending(): void
    {
        $booking = $this->buatBookingValid();

        $gateway = Mockery::mock(MidtransSnapGateway::class);

        $gateway->shouldReceive('createToken')
            ->once()
            ->andThrow(
                new RuntimeException('Simulasi koneksi gagal')
            );

        $service = new PaymentService($gateway);

        try {
            $service->createPayment($booking->id);

            $this->fail(
                'Seharusnya terjadi pengecualian.'
            );
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'belum dapat dipastikan',
                $e->getMessage()
            );
        }

        $payment = PaymentTransaction::where(
            'booking_id',
            $booking->id
        )->firstOrFail();

        $this->assertSame(
            'PENDING',
            $payment->status_pembayaran
        );

        $this->assertNull($payment->snap_token);
    }

    public function test_pending_tanpa_token_tidak_membuat_transaksi_baru(): void
    {
        $booking = $this->buatBookingValid();

        $gateway = Mockery::mock(MidtransSnapGateway::class);

        $gateway->shouldNotReceive('createToken');

        $service = new PaymentService($gateway);

        PaymentTransaction::create([
            'booking_id' => $booking->id,
            'kode_bayar' => 'GOR-TEST-PENDING-NO-TOKEN',
            'metode_pembayaran' => 'MIDTRANS',
            'nominal' => 50000,
            'status_pembayaran' => 'PENDING',
            'jumlah_retry' => 0,
            'referensi_gateway' => null,
            'expired_at' => now()->addHours(24),
            'snap_token' => null,
        ]);

        try {
            $service->createPayment($booking->id);

            $this->fail('Seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'Perlu rekonsiliasi pembayaran',
                $e->getMessage()
            );
        }

        $this->assertSame(
            1,
            PaymentTransaction::where(
                'booking_id',
                $booking->id
            )->count()
        );
    }
}
