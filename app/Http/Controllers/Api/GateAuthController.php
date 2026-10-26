<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GateAuthService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;

class GateAuthController extends Controller
{
    public function __construct(
        private GateAuthService $gateAuthService,
        private OtpService $otpService
    ) {
    }

    /**
     * Meminta OTP login petugas gerbang.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'no_hp_email' => ['required', 'string', 'max:255'],
        ]);

        // Jangan mengirim OTP melalui mailer log/array.
        $mailer = config('mail.default');

        if (!in_array($mailer, [
            'smtp',
            'ses',
            'mailgun',
            'postmark',
            'resend',
        ], true)) {
            return response()->json([
                'message' => 'Layanan pengiriman OTP belum tersedia.',
            ], 503);
        }

        $identity = trim($data['no_hp_email']);

        // Rate limit berdasarkan identitas akun.
        $rateKey = 'gate-otp-request:' .
            hash('sha256', strtolower($identity));

        // Maksimal 3 permintaan dalam 1 jam.
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Coba lagi nanti.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 3600);

        $user = User::query()
            ->where('no_hp_email', $identity)
            ->first();

        // OTP hanya untuk petugas aktif
        // yang memiliki alamat email.
        if (
            $user &&
            $user->petugasGerbang &&
            $user->petugasGerbang->aktif &&
            filter_var(
                $user->no_hp_email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            try {
                $this->otpService->issue(
                    $user,
                    'LOGIN_PETUGAS',
                    function (
                        User $penerima,
                        string $kode
                    ): void {
                        Mail::raw(
                            "Kode OTP login petugas SI-GORAY: {$kode}\n\n" .
                            "Kode berlaku selama 5 menit.\n" .
                            "Jangan berikan kode ini kepada siapa pun.",
                            function (Message $message) use ($penerima): void {
                                $message
                                    ->to($penerima->no_hp_email)
                                    ->subject(
                                        'Kode OTP Login Petugas SI-GORAY'
                                    );
                            }
                        );
                    }
                );
            } catch (Throwable $e) {
                // Jangan tulis OTP atau informasi sensitif ke log.
                logger()->warning(
                    'Permintaan OTP petugas tidak selesai.',
                    ['error_type' => $e::class]
                );
            }
        }

        // Respons generik agar tidak membocorkan
        // apakah akun terdaftar atau tidak.
        return response()->json([
            'message' =>
                'Jika akun petugas terdaftar dan aktif, kode OTP akan dikirim.',
        ], 202);
    }

    /**
     * Verifikasi OTP dan login petugas.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'no_hp_email' => [
                'required',
                'string',
                'max:255',
            ],
            'kode_otp' => [
                'required',
                'digits:6',
            ],
        ]);

        $identity = trim($data['no_hp_email']);

        /*
         * SECURITY HARDENING:
         * Batasi percobaan OTP berdasarkan identitas.
         *
         * Maksimal 5 percobaan dalam 15 menit
         * untuk identitas yang sama.
         */
        $rateKey = 'gate-otp-verify:' .
            hash('sha256', strtolower($identity));

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' =>
                    'Terlalu banyak percobaan login. Coba lagi nanti.',
            ], 429);
        }

        /*
         * Catat percobaan sebelum memeriksa user.
         * Identitas tidak dikenal juga dibatasi.
         */
        RateLimiter::hit($rateKey, 900);

        $user = User::query()
            ->where('no_hp_email', $identity)
            ->first();

        // Jangan ungkap apakah akun tersedia.
        if (!$user) {
            return response()->json([
                'message' =>
                    'Data login atau OTP tidak valid.',
            ], 422);
        }

        try {
            $token = $this->gateAuthService->login(
                $user,
                (string) $data['kode_otp']
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' =>
                    'Data login atau OTP tidak valid.',
            ], 422);
        }

        /*
         * Login sukses.
         * Reset penghitung percobaan akun.
         */
        RateLimiter::clear($rateKey);

        return response()->json([
            'message' => 'Login petugas berhasil.',
            'token_type' => 'Bearer',
            'access_token' => $token,
        ]);
    }

    /**
     * Logout dan cabut token yang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
