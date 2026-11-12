<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GateAuthService
{
    public function __construct(
        private OtpService $otpService
    ) {
    }

    /**
     * Verifikasi OTP petugas dan terbitkan token dalam transaksi yang sama.
     * Kegagalan verifikasi dikembalikan dari closure agar pencatatan
     * percobaan OTP salah tetap ter-commit.
     */
    public function login(User $user, string $kodeOtp): string
    {
        $result = DB::transaction(function () use ($user, $kodeOtp): array {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $petugas = $lockedUser->petugasGerbang;

            if (!$petugas || !$petugas->aktif) {
                return [
                    'success' => false,
                    'reason' => 'PETUGAS_TIDAK_AKTIF',
                ];
            }

            $valid = $this->otpService->verify(
                $lockedUser,
                'LOGIN_PETUGAS',
                $kodeOtp
            );

            if (!$valid) {
                return [
                    'success' => false,
                    'reason' => 'OTP_TIDAK_VALID',
                ];
            }

            $token = $lockedUser->createToken(
                'gate-access',
                ['gate:scan'],
                now()->addHours(8)
            )->plainTextToken;

            return [
                'success' => true,
                'token' => $token,
            ];
        });

        if (!$result['success']) {
            $message = match ($result['reason']) {
                'PETUGAS_TIDAK_AKTIF' => 'Akun bukan petugas gerbang aktif.',
                'OTP_TIDAK_VALID' => 'OTP tidak valid atau sudah kedaluwarsa.',
                default => 'Autentikasi petugas gagal.',
            };

            throw new RuntimeException($message);
        }

        return $result['token'];
    }
}
