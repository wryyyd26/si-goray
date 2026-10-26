<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GateOtpRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Database testing saja.
        // Pastikan phpunit.xml sudah menggunakan
        // gor_reservation_testing.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new RuntimeException(
                'Database pengujian tidak sesuai.'
            );
        }
    }

    private function rateKey(string $identity): string
    {
        return 'gate-otp-verify:' .
            hash(
                'sha256',
                strtolower(trim($identity))
            );
    }

    private function kirimVerifikasi(
        string $identity,
        int $nomorIp
    ) {
        // Simulasi IP berbeda pada setiap request.
        $this->withServerVariables([
            'REMOTE_ADDR' => '10.200.1.' . $nomorIp,
        ]);

        return $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $identity,
                'kode_otp' => '123456',
            ]
        );
    }

    /**
     * TEST 1
     * Lima percobaan diizinkan.
     * Percobaan keenam ditolak meski IP berubah.
     */
    public function test_identitas_sama_dibatasi_meski_ip_berbeda(): void
    {
        $identity = 'rate-' .
            Str::uuid() .
            '@example.com';

        $key = $this->rateKey($identity);

        RateLimiter::clear($key);

        // Lima percobaan pertama ditolak
        // karena akun tidak dikenal (422).
        for ($i = 1; $i <= 5; $i++) {
            $this->kirimVerifikasi(
                $identity,
                $i
            )->assertStatus(422);
        }

        $this->assertSame(
            5,
            RateLimiter::attempts($key)
        );

        // Permintaan keenam harus dibatasi
        // berdasarkan identitas, bukan IP.
        $this->kirimVerifikasi(
            $identity,
            6
        )
            ->assertStatus(429)
            ->assertJsonPath(
                'message',
                'Terlalu banyak percobaan login. Coba lagi nanti.'
            );

        // Permintaan keenam tidak boleh
        // menambah jumlah percobaan.
        $this->assertSame(
            5,
            RateLimiter::attempts($key)
        );

        RateLimiter::clear($key);
    }

    /**
     * TEST 2
     * Identitas lain mempunyai penghitung sendiri.
     */
    public function test_identitas_lain_tidak_ikut_terblokir(): void
    {
        $identityA = 'akun-a-' .
            Str::uuid() .
            '@example.com';

        $identityB = 'akun-b-' .
            Str::uuid() .
            '@example.com';

        $keyA = $this->rateKey($identityA);
        $keyB = $this->rateKey($identityB);

        RateLimiter::clear($keyA);
        RateLimiter::clear($keyB);

        // Habiskan lima kesempatan identitas A.
        for ($i = 1; $i <= 5; $i++) {
            $this->kirimVerifikasi(
                $identityA,
                $i + 20
            )->assertStatus(422);
        }

        // A harus dibatasi.
        $this->kirimVerifikasi(
            $identityA,
            26
        )->assertStatus(429);

        // B masih boleh melakukan verifikasi.
        // Karena akun tidak ditemukan,
        // hasilnya 422, bukan 429.
        $this->kirimVerifikasi(
            $identityB,
            27
        )->assertStatus(422);

        $this->assertSame(
            5,
            RateLimiter::attempts($keyA)
        );

        $this->assertSame(
            1,
            RateLimiter::attempts($keyB)
        );

        RateLimiter::clear($keyA);
        RateLimiter::clear($keyB);
    }

    /**
     * TEST 3
     * Payload tidak valid tidak boleh diproses
     * sebagai percobaan verifikasi OTP.
     */
    public function test_payload_tidak_valid_tidak_menambah_counter_identitas(): void
    {
        $identity = 'invalid-' .
            Str::uuid() .
            '@example.com';

        $key = $this->rateKey($identity);

        RateLimiter::clear($key);

        $this->withServerVariables([
            'REMOTE_ADDR' => '10.200.2.50',
        ]);

        $this->postJson(
            '/api/gate/auth/verify-otp',
            [
                'no_hp_email' => $identity,
                'kode_otp' => '12',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'kode_otp',
            ]);

        $this->assertSame(
            0,
            RateLimiter::attempts($key)
        );

        RateLimiter::clear($key);
    }
}
