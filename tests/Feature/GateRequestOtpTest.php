<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\PetugasGerbang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GateRequestOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wajib pastikan phpunit.xml menggunakan
        // database khusus gor_reservation_testing.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new RuntimeException(
                'Database pengujian tidak sesuai.'
            );
        }

        // SMTP hanya disimulasikan.
        // Mail akan di-mock pada setiap test yang
        // membutuhkan pengiriman.
        config()->set('mail.default', 'smtp');

        // Gunakan alamat IP berbeda antar-test
        // agar limiter HTTP tidak saling memengaruhi.
        $this->withServerVariables([
            'REMOTE_ADDR' => '10.100.' .
                random_int(1, 254) . '.' .
                random_int(1, 254),
        ]);
    }

    private function buatUser(
        ?string $identitas = null
    ): User {
        return User::create([
            'no_hp_email' => $identitas
                ?? 'otp-request-' .
                    Str::uuid() .
                    '@example.com',
            'status_otp' => true,
        ]);
    }

    private function buatPetugas(
        User $user,
        bool $aktif = true
    ): PetugasGerbang {
        $petugas = new PetugasGerbang();

        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas Request OTP';
        $petugas->username = 'gate_' .
            Str::lower(Str::random(12));
        $petugas->no_hp = null;
        $petugas->aktif = $aktif;

        $petugas->save();

        return $petugas;
    }

    private function requestOtp(
        string $identitas
    ) {
        return $this->postJson(
            '/api/gate/auth/request-otp',
            [
                'no_hp_email' => $identitas,
            ]
        );
    }

    /**
     * TEST 1
     * Mailer log tidak boleh digunakan.
     */
    public function test_mailer_log_ditolak(): void
    {
        config()->set('mail.default', 'log');

        $user = $this->buatUser();
        $this->buatPetugas($user);

        Mail::shouldReceive('raw')->never();

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'Layanan pengiriman OTP belum tersedia.'
            );

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 2
     * Akun yang tidak dikenal tidak boleh
     * menyebabkan OTP dibuat.
     */
    public function test_akun_tidak_dikenal_mendapat_respons_generik(): void
    {
        Mail::shouldReceive('raw')->never();

        $identitas = 'tidak-ada-' .
            Str::uuid() .
            '@example.com';

        $this->requestOtp($identitas)
            ->assertStatus(202)
            ->assertJsonPath(
                'message',
                'Jika akun petugas terdaftar dan aktif, kode OTP akan dikirim.'
            );

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 3
     * User biasa tidak boleh meminta OTP petugas.
     */
    public function test_user_biasa_tidak_mendapat_otp(): void
    {
        $user = $this->buatUser();

        Mail::shouldReceive('raw')->never();

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 4
     * Petugas nonaktif tidak boleh menerima OTP.
     */
    public function test_petugas_nonaktif_tidak_mendapat_otp(): void
    {
        $user = $this->buatUser();

        $this->buatPetugas($user, false);

        Mail::shouldReceive('raw')->never();

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 5
     * Nomor telepon tidak boleh diperlakukan
     * sebagai alamat email.
     */
    public function test_nomor_hp_tidak_dikirim_melalui_email(): void
    {
        $user = $this->buatUser(
            '081234567890'
        );

        $this->buatPetugas($user);

        Mail::shouldReceive('raw')->never();

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 6
     * Petugas aktif bisa meminta OTP.
     * Kode asli tidak boleh disimpan di database.
     */
    public function test_petugas_aktif_berhasil_meminta_otp(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        $isiEmail = null;

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (
                $isi,
                $callback
            ) use (&$isiEmail) {
                $isiEmail = $isi;

                return is_string($isi)
                    && is_callable($callback);
            });

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202)
            ->assertJsonPath(
                'message',
                'Jika akun petugas terdaftar dan aktif, kode OTP akan dikirim.'
            );

        $this->assertDatabaseCount(
            'otp_verifications',
            1
        );

        $otp = OtpVerification::query()
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $otp->user_id
        );

        $this->assertSame(
            'LOGIN_PETUGAS',
            $otp->tujuan
        );

        $this->assertSame(
            'PENDING',
            $otp->status
        );

        $this->assertSame(
            0,
            (int) $otp->percobaan_ke
        );

        $this->assertTrue(
            $otp->expired_at->isFuture()
        );

        $this->assertNotNull($isiEmail);

        preg_match(
            '/\b[0-9]{6}\b/',
            $isiEmail,
            $hasil
        );

        $this->assertNotEmpty($hasil);

        $kodeAsli = $hasil[0];

        $this->assertNotSame(
            $kodeAsli,
            $otp->kode_otp_hash
        );

        $this->assertTrue(
            Hash::check(
                $kodeAsli,
                $otp->kode_otp_hash
            )
        );
    }

    /**
     * TEST 7
     * Meminta OTP lagi sebelum 60 detik
     * tidak boleh membuat OTP kedua.
     */
    public function test_permintaaan_otp_kedua_terlalu_cepat_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        // Email hanya boleh dikirim pada
        // permintaan pertama.
        Mail::shouldReceive('raw')
            ->once();

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            1
        );

        // Permintaan kedua mendapat respons
        // generik tetapi tidak membuat OTP baru.
        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            1
        );
    }

    /**
     * TEST 8
     * Pembatasan jumlah permintaan OTP.
     */
    public function test_permintaaan_keempat_terkena_rate_limit(): void
    {
        $identitas = 'rate-limit-' .
            Str::uuid() .
            '@example.com';

        // Gunakan akun yang tidak ada agar
        // tidak perlu melakukan pengiriman.
        Mail::shouldReceive('raw')->never();

        $this->requestOtp($identitas)
            ->assertStatus(202);

        $this->requestOtp($identitas)
            ->assertStatus(202);

        $this->requestOtp($identitas)
            ->assertStatus(202);

        $this->requestOtp($identitas)
            ->assertStatus(429);

        $this->assertDatabaseCount(
            'otp_verifications',
            0
        );
    }

    /**
     * TEST 9
     * Jika pengiriman email gagal,
     * OTP tidak boleh tetap berstatus PENDING.
     */
    public function test_pengiriman_gagal_mengubah_status_otp(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Simulasi email gagal'
                )
            );

        $this->requestOtp($user->no_hp_email)
            ->assertStatus(202);

        $this->assertDatabaseCount(
            'otp_verifications',
            1
        );

        $otp = OtpVerification::query()
            ->firstOrFail();

        $this->assertSame(
            'DELIVERY_FAILED',
            $otp->status
        );
    }
}
