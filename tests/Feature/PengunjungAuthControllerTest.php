<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PengunjungAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatUser(): User
    {
        return User::create([
            'no_hp_email' => 'pengunjung-' . Str::uuid() . '@example.com',
            'status_otp' => false,
        ]);
    }

    private function buatOtp(User $user): string
    {
        $kode = '';

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PENGUNJUNG',
            function (User $penerima, string $otp) use (&$kode, $user): void {
                $this->assertSame($user->id, $penerima->id);
                $kode = $otp;
            }
        );

        $this->assertNotSame('', $kode);

        return $kode;
    }

    public function test_request_otp_email_tidak_valid_ditolak(): void
    {
        $this->postJson('/api/pengunjung/auth/request-otp', [
            'no_hp_email' => 'bukan-email',
        ])->assertUnprocessable()->assertJsonValidationErrors('no_hp_email');
    }

    public function test_mailer_log_tidak_boleh_mengirim_otp(): void
    {
        config(['mail.default' => 'log']);

        $this->postJson('/api/pengunjung/auth/request-otp', [
            'no_hp_email' => 'pelanggan@example.com',
        ])->assertStatus(503);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_otp_pengunjung_valid_mendapat_token_dengan_izin_terbatas(): void
    {
        $user = $this->buatUser();
        $kode = $this->buatOtp($user);

        $res = $this->postJson('/api/pengunjung/auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => $kode,
        ]);

        $res->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('message', 'Login pengunjung berhasil.')
            ->assertJsonStructure(['access_token']);

        $token = $user->tokens()->firstOrFail();
        $this->assertTrue($token->can('booking:create'));
        $this->assertTrue($token->can('booking:read'));
        $this->assertTrue($token->can('payment:create'));
        $this->assertFalse($token->can('gate:scan'));
        $this->assertTrue($user->fresh()->status_otp);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('VERIFIED', OtpVerification::firstOrFail()->status);
    }

    public function test_otp_salah_ditolak_dan_percobaan_disimpan(): void
    {
        $user = $this->buatUser();
        $kode = $this->buatOtp($user);
        $salah = $kode === '000000' ? '111111' : '000000';

        $this->postJson('/api/pengunjung/auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => $salah,
        ])->assertStatus(422);

        $this->assertSame(1, (int) OtpVerification::firstOrFail()->percobaan_ke);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_otp_petugas_tidak_bisa_dipakai_login_pengunjung(): void
    {
        $user = $this->buatUser();
        $kode = '';

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (User $u, string $otp) use (&$kode): void {
                $kode = $otp;
            }
        );

        $this->postJson('/api/pengunjung/auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => $kode,
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_otp_tidak_bisa_digunakan_dua_kali(): void
    {
        $user = $this->buatUser();
        $kode = $this->buatOtp($user);
        $payload = [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => $kode,
        ];

        $this->postJson('/api/pengunjung/auth/verify-otp', $payload)
            ->assertOk();

        $this->postJson('/api/pengunjung/auth/verify-otp', $payload)
            ->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_tanpa_login_ditolak(): void
    {
        $this->postJson('/api/pengunjung/auth/logout')->assertUnauthorized();
    }

    public function test_logout_mencabut_token(): void
    {
        $user = $this->buatUser();
        $kode = $this->buatOtp($user);

        $res = $this->postJson('/api/pengunjung/auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => $kode,
        ])->assertOk();

        $token = $res->json('access_token');

        $this->withToken($token)
            ->postJson('/api/pengunjung/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/bookings/history')
            ->assertUnauthorized();
    }
}
