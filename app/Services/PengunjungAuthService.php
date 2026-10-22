<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengunjungAuthService
{
    public function __construct(
        private OtpService $otpService
    ) {
    }

    /**
     * Verifikasi OTP dan terbitkan token pengunjung secara atomik.
     * Jika pembuatan token gagal, OTP tidak ikut habis.
     */
    public function login(User $user, string $kodeOtp): string
    {
        $hasil = DB::transaction(function () use ($user, $kodeOtp): array {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (!$this->otpService->verify($user, 'LOGIN_PENGUNJUNG', $kodeOtp)) {
                // Jangan melempar exception di sini: percobaan salah harus tersimpan.
                return ['success' => false];
            }

            $user->status_otp = true;
            $user->email_verified_at = now();
            $user->save();

            $token = $user->createToken(
                'pengunjung-access',
                ['booking:create', 'booking:read', 'payment:create'],
                now()->addHours(8)
            )->plainTextToken;

            return [
                'success' => true,
                'token' => $token,
            ];
        });

        if (!$hasil['success']) {
            throw new RuntimeException('OTP tidak valid atau kedaluwarsa.');
        }

        return $hasil['token'];
    }
}
