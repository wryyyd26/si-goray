<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\PetugasGerbang;
use App\Models\User;
use App\Services\GateAuthService;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GateAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // RefreshDatabase dieksekusi oleh parent::setUp().
        // Pastikan phpunit.xml SUDAH menunjuk database testing sebelum tes dimulai.
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatUser(): User
    {
        return User::create([
            'no_hp_email' => 'gate-auth-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function buatPetugas(User $user, bool $aktif = true): PetugasGerbang
    {
        // Set properti langsung agar tes tidak bergantung pada mass assignment.
        $petugas = new PetugasGerbang();
        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas Tes OTP';
        $petugas->username = 'gate_' . Str::lower(Str::random(12));
        $petugas->no_hp = null;
        $petugas->aktif = $aktif;
        $petugas->save();

        return $petugas;
    }

    private function kirimOtpPalsu(User $user): string
    {
        $kodeTerkirim = null;

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (User $penerima, string $kode) use (&$kodeTerkirim, $user): void {
                $this->assertSame($user->id, $penerima->id);
                $kodeTerkirim = $kode;
            }
        );

        $this->assertNotNull($kodeTerkirim);

        return $kodeTerkirim;
    }

    public function test_petugas_aktif_dengan_otp_benar_mendapat_token_gate_scan(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        $kode = $this->kirimOtpPalsu($user);

        $tokenPlain = app(GateAuthService::class)->login($user, $kode);

        $this->assertNotEmpty($tokenPlain);
        $this->assertStringContainsString('|', $tokenPlain);

        $token = $user->tokens()->firstOrFail();
        $this->assertTrue($token->can('gate:scan'));
        $this->assertFalse($token->can('booking:write'));
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
        $this->assertSame('VERIFIED', OtpVerification::query()->firstOrFail()->status);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_user_biasa_tidak_dapat_login_meski_memiliki_otp_benar(): void
    {
        $user = $this->buatUser();
        $kode = $this->kirimOtpPalsu($user);

        try {
            app(GateAuthService::class)->login($user, $kode);
            $this->fail('Login user biasa seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('Akun bukan petugas gerbang aktif.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame('PENDING', OtpVerification::query()->firstOrFail()->status);
    }

    public function test_petugas_nonaktif_tidak_mendapat_token(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user, false);
        $kode = $this->kirimOtpPalsu($user);

        try {
            app(GateAuthService::class)->login($user, $kode);
            $this->fail('Login petugas nonaktif seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('Akun bukan petugas gerbang aktif.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_otp_salah_ditolak_tanpa_membuat_token(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        $kode = $this->kirimOtpPalsu($user);
        $kodeSalah = $kode === '000000' ? '111111' : '000000';

        try {
            app(GateAuthService::class)->login($user, $kodeSalah);
            $this->fail('OTP salah seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('OTP tidak valid atau sudah kedaluwarsa.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Penting: exception di GateAuthService mungkin membatalkan transaksi
        // yang juga mencakup pencatatan percobaan OTP. Assertion ini mendeteksinya.
        $this->assertSame(1, OtpVerification::query()->firstOrFail()->percobaan_ke);
    }

    public function test_otp_kedaluwarsa_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        $kode = $this->kirimOtpPalsu($user);
        OtpVerification::query()->firstOrFail()->update(['expired_at' => now()->subMinute()]);

        try {
            app(GateAuthService::class)->login($user, $kode);
            $this->fail('OTP expired seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('OTP tidak valid atau sudah kedaluwarsa.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_otp_yang_sudah_dipakai_tidak_bisa_login_lagi(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        $kode = $this->kirimOtpPalsu($user);

        app(GateAuthService::class)->login($user, $kode);

        try {
            app(GateAuthService::class)->login($user, $kode);
            $this->fail('OTP tidak boleh dipakai dua kali.');
        } catch (RuntimeException $e) {
            $this->assertSame('OTP tidak valid atau sudah kedaluwarsa.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_tanpa_otp_aktif_login_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        try {
            app(GateAuthService::class)->login($user, '123456');
            $this->fail('Login tanpa OTP seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertSame('OTP tidak valid atau sudah kedaluwarsa.', $e->getMessage());
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
