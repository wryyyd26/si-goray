<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\PetugasGerbang;
use App\Models\User;
use App\Services\GateAuthService;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Tests\TestCase;

class GateAuthRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase berjalan sebelum guard ini.
        // Pastikan phpunit.xml hanya mengarah
        // ke database gor_reservation_testing.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new RuntimeException(
                'Database pengujian tidak sesuai.'
            );
        }
    }

    public function test_gagal_membuat_token_tidak_menghabiskan_otp(): void
    {
        // 1. Siapkan akun khusus pengujian.
        $user = User::create([
            'no_hp_email' =>
                'rollback-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        // 2. Hubungkan dengan petugas aktif.
        $petugas = new PetugasGerbang();
        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas Rollback Test';
        $petugas->username =
            'rollback_' . Str::lower(Str::random(10));
        $petugas->aktif = true;
        $petugas->save();

        // 3. Buat OTP dan tangkap kode dalam memori.
        $kode = null;

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (User $penerima, string $otp)
                use (&$kode): void {
                $kode = $otp;
            }
        );

        $this->assertNotNull($kode);

        $otpRecord = OtpVerification::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(
            'PENDING',
            $otpRecord->status
        );

        // 4. Simulasikan kegagalan penyimpanan
        // token ketika Eloquent membuat token Sanctum.
        Event::listen(
            'eloquent.creating: ' .
                PersonalAccessToken::class,
            function (): void {
                throw new RuntimeException(
                    'Simulasi kegagalan penerbitan token'
                );
            }
        );

        // 5. Login seharusnya gagal karena
        // penerbitan token sengaja digagalkan.
        $exceptionTerjadi = false;

        try {
            app(GateAuthService::class)->login(
                $user,
                $kode
            );
        } catch (RuntimeException $e) {
            $exceptionTerjadi = true;

            $this->assertSame(
                'Simulasi kegagalan penerbitan token',
                $e->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionTerjadi,
            'Penerbitan token harus gagal.'
        );

        // 6. Transaksi harus rollback.
        // OTP tidak boleh habis.
        $otpRecord->refresh();

        $this->assertSame(
            'PENDING',
            $otpRecord->status
        );

        $this->assertSame(
            0,
            (int) $otpRecord->percobaan_ke
        );

        // 7. Tidak boleh ada token yang tersimpan.
        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}
