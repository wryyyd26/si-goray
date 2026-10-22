<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\PengunjungAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;

class PengunjungAuthController extends Controller
{
    public function __construct(
        private PengunjungAuthService $authService,
        private OtpService $otpService
    ) {
    }

    /**
     * Registrasi/login pengunjung melalui kode OTP email.
     * Nomor HP menyusul setelah ada provider SMS yang tepercaya.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'no_hp_email' => ['required', 'email:rfc', 'max:255'],
        ]);

        // OTP sungguhan tidak boleh dikirim via mailer log/array.
        if (!in_array(config('mail.default'), [
            'smtp', 'ses', 'mailgun', 'postmark', 'resend',
        ], true)) {
            return response()->json([
                'message' => 'Layanan pengiriman OTP belum tersedia.',
            ], 503);
        }

        $email = strtolower(trim($data['no_hp_email']));
        $rateKey = 'pengunjung-otp-request:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Coba lagi nanti.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 3600);

        // firstOrCreate menghindari akun ganda jika email sudah pernah didaftarkan.
        $user = User::query()->firstOrCreate(
            ['no_hp_email' => $email],
            ['status_otp' => false]
        );

        try {
            $this->otpService->issue(
                $user,
                'LOGIN_PENGUNJUNG',
                function (User $penerima, string $kode): void {
                    Mail::raw(
                        "Kode OTP SI-GORAY: {$kode}\n\n" .
                        "Berlaku 5 menit. Jangan bagikan kode ini.",
                        function (Message $message) use ($penerima): void {
                            $message->to($penerima->no_hp_email)
                                ->subject('Kode OTP Pengunjung SI-GORAY');
                        }
                    );
                }
            );
        } catch (Throwable $e) {
            // Jangan log alamat email atau kode OTP.
            logger()->warning(
                'Pengiriman OTP pengunjung gagal.',
                ['error_type' => $e::class]
            );
        }

        return response()->json([
            'message' => 'Jika alamat email valid, kode OTP akan dikirim.',
        ], 202);
    }

    /**
     * Validasi OTP dan login/registrasi pengunjung.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'no_hp_email' => ['required', 'email:rfc', 'max:255'],
            'kode_otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($data['no_hp_email']));
        $rateKey = 'pengunjung-otp-verify:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 900);

        $user = User::query()
            ->where('no_hp_email', $email)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Data login atau OTP tidak valid.',
            ], 422);
        }

        try {
            $token = $this->authService->login(
                $user,
                (string) $data['kode_otp']
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Data login atau OTP tidak valid.',
            ], 422);
        }

        RateLimiter::clear($rateKey);

        return response()->json([
            'message' => 'Login pengunjung berhasil.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'no_hp_email' => $user->no_hp_email,
            ],
        ]);
    }

    /**
     * Cabut hanya token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout pengunjung berhasil.',
        ]);
    }
}
