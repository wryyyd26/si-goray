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
     * Login petugas menggunakan OTP.
     *
     * OTP dan token diproses dalam satu transaksi
     * agar keduanya tetap konsisten.
     */
    public function login(
        User $user,
        string $kodeOtp
    ): string {
        $result = DB::transaction(function () use (
            $user,
            $kodeOtp
        ) {
            // Kunci akun agar dua login bersamaan
            // diproses secara berurutan.
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            // Pastikan user terkait dengan
            // petugas gerbang yang masih aktif.
            $petugas = $user->petugasGerbang;

            if (!$petugas || !$petugas->aktif) {
                return [
                    'success' => false,
                    'reason' => 'PETUGAS_TIDAK_AKTIF',
                ];
            }

            // Verifikasi OTP.
            // OtpService mencatat percobaan gagal.
            $valid = $this->otpService->verify(
                $user,
                'LOGIN_PETUGAS',
                $kodeOtp
            );

            if (!$valid) {
                // Jangan lempar exception di sini.
                // Percobaan OTP salah harus di-COMMIT.
                return [
                    'success' => false,
                    'reason' => 'OTP_TIDAK_VALID',
                ];
            }

            // Penerbitan token berlangsung dalam
            // transaksi yang sama dengan verifikasi OTP.
            //
            // Jika createToken() melempar exception,
            // transaksi rollback dan OTP tidak habis.
            $token = $user->createToken(
                'gate-access',
                ['gate:scan'],
                now()->addHours(8)
            )->plainTextToken;

            return [
                'success' => true,
                'token' => $token,
            ];
        });

        // Exception hanya untuk kegagalan login biasa.
        // Dilempar sesudah transaksi COMMIT agar
        // percobaan OTP salah tidak dibatalkan.
        if (!$result['success']) {
            throw new RuntimeException(
                $result['reason'] === 'OTP_TIDAK_VALID'
                    ? 'OTP tidak valid atau sudah kedaluwarsa.'
                    : 'Akun bukan petugas gerbang aktif.'
            );
        }

        return $result['token'];
    }
}
