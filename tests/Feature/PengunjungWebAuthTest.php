<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

class PengunjungWebAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function userWithOtp(string $email = 'pengunjung-web-test@example.com'): User
    {
        $user = User::create(['no_hp_email' => $email, 'status_otp' => false]);
        OtpVerification::create([
            'user_id' => $user->id,
            'kode_otp_hash' => Hash::make('123456'),
            'tujuan' => 'LOGIN_PENGUNJUNG',
            'status' => 'PENDING',
            'percobaan_ke' => 0,
            'expired_at' => now()->addMinutes(5),
        ]);
        return $user;
    }

    public function test_kode_otp_valid_login_dengan_session_bukan_token(): void
    {
        $user = $this->userWithOtp();
        $this->postJson('/pengunjung/web-auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => '123456',
        ])->assertOk()->assertJsonPath('user.id', (string) $user->id)
            ->assertJsonMissingPath('access_token');
        $this->assertAuthenticatedAs($user, 'web');
        $this->getJson('/pengunjung/web-auth/status')
            ->assertOk()->assertJsonPath('authenticated', true);
        $this->assertDatabaseHas('otp_verifications', [
            'user_id' => $user->id, 'status' => 'VERIFIED',
        ]);
    }

    public function test_kode_salah_ditolak_dan_tidak_login(): void
    {
        $user = $this->userWithOtp('wrong-web-test@example.com');
        $this->postJson('/pengunjung/web-auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email,
            'kode_otp' => '654321',
        ])->assertStatus(422);
        $this->assertGuest('web');
    }

    public function test_otp_tidak_dapat_digunakan_dua_kali(): void
    {
        $user = $this->userWithOtp('repeat-web-test@example.com');
        $payload = ['no_hp_email' => $user->no_hp_email, 'kode_otp' => '123456'];
        $this->postJson('/pengunjung/web-auth/verify-otp', $payload)->assertOk();
        $this->postJson('/pengunjung/web-auth/verify-otp', $payload)->assertStatus(422);
    }

    public function test_logout_pengunjung_mempertahankan_session_admin(): void
    {
        $user = $this->userWithOtp('admin-session-web-test@example.com');
        $this->withSession(['sigoray_admin_authenticated' => true]);
        $this->postJson('/pengunjung/web-auth/verify-otp', [
            'no_hp_email' => $user->no_hp_email, 'kode_otp' => '123456',
        ])->assertOk();
        $this->postJson('/pengunjung/web-auth/logout')->assertOk();
        $this->assertGuest('web');
        $this->assertSame(true, session('sigoray_admin_authenticated'));
    }

    public function test_status_pengunjung_tanpa_login_adalah_guest(): void
    {
        $this->getJson('/pengunjung/web-auth/status')
            ->assertOk()->assertJsonPath('authenticated', false);
    }
}
