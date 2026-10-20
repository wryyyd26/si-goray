<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class OtpService
{
    private const OTP_EXPIRE_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Membuat OTP dan menyerahkan kode kepada
     * pengirim tepercaya melalui callback.
     *
     * Jangan mengembalikan kode OTP dalam response API.
     */
    public function issue(
        User $user,
        string $tujuan,
        callable $sendOtp
    ): OtpVerification {
        if ($tujuan !== 'LOGIN_PETUGAS') {
            throw new RuntimeException(
                'Tujuan OTP tidak diizinkan.'
            );
        }

        // Buat kode acak secara kriptografis.
        $kode = (string) random_int(100000, 999999);

        $otp = DB::transaction(function () use (
            $user,
            $tujuan,
            $kode
        ) {
            // Kunci akun agar permintaan bersamaan
            // tidak menghasilkan dua OTP aktif.
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            // OTP terbaru untuk tujuan yang sama.
            $terakhir = OtpVerification::query()
                ->where('user_id', $user->id)
                ->where('tujuan', $tujuan)
                ->latest('created_at')
                ->first();

            // Batasi permintaan OTP berulang.
            if (
                $terakhir &&
                $terakhir->created_at &&
                $terakhir->created_at->gt(
                    now()->subSeconds(
                        self::RESEND_COOLDOWN_SECONDS
                    )
                )
            ) {
                throw new RuntimeException(
                    'Tunggu sebelum meminta OTP baru.'
                );
            }

            // Nonaktifkan OTP sebelumnya.
            OtpVerification::query()
                ->where('user_id', $user->id)
                ->where('tujuan', $tujuan)
                ->where('status', 'PENDING')
                ->update([
                    'status' => 'EXPIRED',
                ]);

            return OtpVerification::create([
                'user_id' => $user->id,
                'kode_otp_hash' => Hash::make($kode),
                'tujuan' => $tujuan,
                'status' => 'PENDING',
                'percobaan_ke' => 0,
                'expired_at' => now()->addMinutes(
                    self::OTP_EXPIRE_MINUTES
                ),
            ]);
        });

        // Saluran pengiriman akan diimplementasikan
        // terpisah. Jangan gunakan mailer log
        // untuk OTP sungguhan.
        try {
            $sendOtp($user, $kode);
        } catch (\Throwable $e) {
            $otp->update([
                'status' => 'DELIVERY_FAILED',
            ]);

            throw $e;
        }

        return $otp->fresh();
    }

    /**
     * Verifikasi OTP.
     *
     * Mengembalikan true hanya untuk OTP sah,
     * belum expired, dan belum pernah digunakan.
     */
    public function verify(
        User $user,
        string $tujuan,
        string $kode
    ): bool {
        if (
            $tujuan !== 'LOGIN_PETUGAS' ||
            !preg_match('/^[0-9]{6}$/', $kode)
        ) {
            return false;
        }

        return DB::transaction(function () use (
            $user,
            $tujuan,
            $kode
        ) {
            // Urutan lock konsisten:
            // User terlebih dahulu, lalu OTP.
            User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $otp = OtpVerification::query()
                ->where('user_id', $user->id)
                ->where('tujuan', $tujuan)
                ->where('status', 'PENDING')
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                return false;
            }

            if ($otp->expired_at->lte(now())) {
                $otp->update([
                    'status' => 'EXPIRED',
                ]);

                return false;
            }

            if (
                $otp->percobaan_ke >=
                self::MAX_ATTEMPTS
            ) {
                $otp->update([
                    'status' => 'BLOCKED',
                ]);

                return false;
            }

            if (!Hash::check(
                $kode,
                $otp->kode_otp_hash
            )) {
                $percobaan = $otp->percobaan_ke + 1;

                $otp->update([
                    'percobaan_ke' => $percobaan,
                    'status' => $percobaan >=
                        self::MAX_ATTEMPTS
                        ? 'BLOCKED'
                        : 'PENDING',
                ]);

                return false;
            }

            // OTP berhasil dan tidak boleh
            // digunakan kembali.
            $otp->update([
                'status' => 'VERIFIED',
                'percobaan_ke' =>
                    $otp->percobaan_ke + 1,
            ]);

            return true;
        });
    }
}
