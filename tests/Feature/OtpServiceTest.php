<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Perhatian: RefreshDatabase dieksekusi di parent::setUp().
        // Pastikan phpunit.xml menunjuk gor_reservation_testing SEBELUM tes dimulai.
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatUser(): User
    {
        return User::create([
            'no_hp_email' => 'otp-' . Str::uuid() . '@example.com',
            'status_otp' => false,
        ]);
    }

    /**
     * Mensimulasikan pengiriman OTP tanpa email, SMS, ataupun log.
     * Kode hanya disimpan dalam variabel lokal pengujian.
     */
    private function terbitkan(User $user, ?string &$kode): OtpVerification
    {
        return app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (User $penerima, string $otp) use (&$kode, $user): void {
                $this->assertSame($user->id, $penerima->id);
                $kode = $otp;
            }
        );
    }

    public function test_otp_benar_berhasil_dan_hash_tidak_menyimpan_kode_asli(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $otp = $this->terbitkan($user, $kode);

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $kode);
        $this->assertNotSame($kode, $otp->kode_otp_hash);
        $this->assertTrue(Hash::check($kode, $otp->kode_otp_hash));
        $this->assertArrayNotHasKey('kode_otp_hash', $otp->toArray());

        $this->assertTrue(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
        $this->assertSame('VERIFIED', $otp->fresh()->status);
    }

    public function test_otp_salah_dihitung_dan_otp_benar_masih_bisa_digunakan(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $otp = $this->terbitkan($user, $kode);
        $salah = $kode === '000000' ? '111111' : '000000';

        $this->assertFalse(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $salah));
        $this->assertSame(1, (int) $otp->fresh()->percobaan_ke);
        $this->assertSame('PENDING', $otp->fresh()->status);
        $this->assertTrue(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
    }

    public function test_lima_kode_salah_memblokir_otp(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $otp = $this->terbitkan($user, $kode);
        $salah = $kode === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $salah));
        }

        $this->assertSame('BLOCKED', $otp->fresh()->status);
        $this->assertSame(5, (int) $otp->fresh()->percobaan_ke);
        $this->assertFalse(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
    }

    public function test_otp_kedaluwarsa_ditolak(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $otp = $this->terbitkan($user, $kode);

        $this->travel(6)->minutes();

        $this->assertFalse(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
        $this->assertSame('EXPIRED', $otp->fresh()->status);
    }

    public function test_otp_yang_sudah_diverifikasi_tidak_bisa_dipakai_dua_kali(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $otp = $this->terbitkan($user, $kode);

        $this->assertTrue(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
        $this->assertFalse(app(OtpService::class)->verify($user, 'LOGIN_PETUGAS', $kode));
        $this->assertSame('VERIFIED', $otp->fresh()->status);
    }

    public function test_permintaan_otp_kedua_dalam_60_detik_ditolak(): void
    {
        $user = $this->buatUser();
        $kode = null;
        $this->terbitkan($user, $kode);

        $pengirimanKedua = false;
        try {
            app(OtpService::class)->issue(
                $user,
                'LOGIN_PETUGAS',
                function () use (&$pengirimanKedua): void {
                    $pengirimanKedua = true;
                }
            );
            $this->fail('Permintaan OTP kedua seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('Tunggu sebelum meminta OTP baru.', $e->getMessage());
        }

        $this->assertFalse($pengirimanKedua);
        $this->assertDatabaseCount('otp_verifications', 1);
    }

    public function test_kegagalan_pengiriman_mengubah_status_otp(): void
    {
        $user = $this->buatUser();

        try {
            app(OtpService::class)->issue(
                $user,
                'LOGIN_PETUGAS',
                function (): void {
                    throw new RuntimeException('Pengiriman simulasi gagal.');
                }
            );
            $this->fail('Kegagalan pengiriman seharusnya diteruskan.');
        } catch (RuntimeException $e) {
            $this->assertSame('Pengiriman simulasi gagal.', $e->getMessage());
        }

        $this->assertDatabaseHas('otp_verifications', [
            'user_id' => $user->id,
            'status' => 'DELIVERY_FAILED',
        ]);
    }

    public function test_tujuan_otp_selain_login_petugas_ditolak(): void
    {
        $user = $this->buatUser();

        $this->assertFalse(app(OtpService::class)->verify($user, 'RESET_PASSWORD', '123456'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tujuan OTP tidak diizinkan.');

        app(OtpService::class)->issue($user, 'RESET_PASSWORD', function (): void {});
    }
}
