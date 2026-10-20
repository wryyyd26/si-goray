<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\PetugasGerbang;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GateAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Pastikan phpunit.xml dan hak akses MySQL
        // membatasi pengujian ke database testing.
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException(
                'Database pengujian tidak sesuai.'
            );
        }
    }

    private function buatUser(): User
    {
        return User::create([
            'no_hp_email' => 'gate-api-auth-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function buatPetugas(
        User $user,
        bool $aktif = true
    ): PetugasGerbang {
        $petugas = new PetugasGerbang();

        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas API OTP';
        $petugas->username = 'gate_' . Str::lower(Str::random(12));
        $petugas->no_hp = null;
        $petugas->aktif = $aktif;

        $petugas->save();

        return $petugas;
    }

    private function buatOtp(User $user): string
    {
        $kode = null;

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (
                User $penerima,
                string $otp
            ) use (&$kode, $user): void {
                $this->assertSame(
                    $user->id,
                    $penerima->id
                );

                $kode = $otp;
            }
        );

        $this->assertNotNull($kode);

        return $kode;
    }

    public function test_otp_benar_menghasilkan_token_gate_scan(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        $kode = $this->buatOtp($user);

        $response = $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $user->no_hp_email,
                'kode_otp' => $kode,
            ]
        );

        $response->assertOk()
            ->assertJsonPath(
                'token_type',
                'Bearer'
            )
            ->assertJsonPath(
                'message',
                'Login petugas berhasil.'
            )
            ->assertJsonStructure([
                'access_token',
            ]);

        $tokenPlain = $response->json('access_token');

        $this->assertIsString($tokenPlain);
        $this->assertNotEmpty($tokenPlain);

        $token = $user->tokens()->firstOrFail();

        $this->assertTrue(
            $token->can('gate:scan')
        );

        $this->assertFalse(
            $token->can('booking:write')
        );

        $this->assertSame(
            'VERIFIED',
            OtpVerification::query()
                ->firstOrFail()
                ->status
        );
    }

    public function test_otp_salah_ditolak_dan_percobaan_bertambah(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        $kode = $this->buatOtp($user);

        $salah = $kode === '000000'
            ? '111111'
            : '000000';

        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $user->no_hp_email,
                'kode_otp' => $salah,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data login atau OTP tidak valid.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        $this->assertSame(
            1,
            OtpVerification::query()
                ->firstOrFail()
                ->percobaan_ke
        );
    }

    public function test_identitas_tidak_dikenal_ditolak_tanpa_membocorkan_akun(): void
    {
        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' =>
                    'not-found-' . Str::uuid() . '@example.com',
                'kode_otp' => '123456',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data login atau OTP tidak valid.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_user_biasa_ditolak_meski_otp_benar(): void
    {
        $user = $this->buatUser();

        $kode = $this->buatOtp($user);

        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $user->no_hp_email,
                'kode_otp' => $kode,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data login atau OTP tidak valid.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_petugas_nonaktif_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user, false);

        $kode = $this->buatOtp($user);

        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $user->no_hp_email,
                'kode_otp' => $kode,
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data login atau OTP tidak valid.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_payload_tidak_valid_ditolak(): void
    {
        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => '',
                'kode_otp' => '12',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'no_hp_email',
                'kode_otp',
            ]);
    }

    public function test_logout_tanpa_token_ditolak(): void
    {
        $this->postJson(
            '/api/gate/auth/logout'
        )->assertUnauthorized();
    }

    public function test_logout_mencabut_token_dan_token_lama_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);

        $kode = $this->buatOtp($user);

        // 1. Login menggunakan OTP yang benar.
        $login = $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $user->no_hp_email,
                'kode_otp' => $kode,
            ]
        )->assertOk();

        $tokenPlain = $login->json('access_token');

        $this->assertIsString($tokenPlain);
        $this->assertNotEmpty($tokenPlain);

        // 2. Pastikan token tersimpan.
        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        // 3. Logout menggunakan token tersebut.
        $this->withToken($tokenPlain)
            ->postJson('/api/gate/auth/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logout berhasil.'
            );

        // 4. Pastikan token sudah dihapus.
        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        // 5. Bersihkan guard autentikasi yang mungkin
        //    tersimpan dari request sebelumnya.
        Auth::forgetGuards();

        // 6. Gunakan kembali token yang sudah dicabut.
        //    Request harus ditolak dengan HTTP 401,
        //    sebelum pemrosesan payload scan.
        $this->withToken($tokenPlain)
            ->postJson(
                '/api/gate/scan',
                [
                    'qr_payload' => 'invalid',
                ]
            )
            ->assertUnauthorized();
    }
}
