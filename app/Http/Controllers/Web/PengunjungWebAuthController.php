<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\PengunjungAuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class PengunjungWebAuthController extends Controller
{
    /** Gunakan pengiriman OTP yang sama dengan API, bukan membuat sistem OTP kedua. */
    public function requestOtp(Request $request, PengunjungAuthController $api): JsonResponse
    {
        return $api->requestOtp($request);
    }

    /** Membuat login session web, tanpa mengirim access token ke browser. */
    public function verifyOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'no_hp_email' => ['required', 'email:rfc', 'max:255'],
            'kode_otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($data['no_hp_email']));
        // Key sama dengan verifikasi API agar pembatasan tidak dapat dilewati lintas endpoint.
        $rateKey = 'pengunjung-otp-verify:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Coba lagi nanti.'], 429);
        }

        RateLimiter::hit($rateKey, 900);
        $user = User::query()->where('no_hp_email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Data login atau OTP tidak valid.'], 422);
        }

        $verified = DB::transaction(function () use ($user, $data, $otpService): bool {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if (!$otpService->verify($user, 'LOGIN_PENGUNJUNG', (string) $data['kode_otp'])) {
                return false;
            }

            $user->status_otp = true;
            $user->email_verified_at = now();
            $user->save();
            return true;
        });

        if (!$verified) {
            return response()->json(['message' => 'Data login atau OTP tidak valid.'], 422);
        }

        // Session guard tidak memberikan akses Admin: Admin memakai flag session terpisah.
        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
        RateLimiter::clear($rateKey);

        return response()->json([
            'message' => 'Login pengunjung berhasil.',
            'user' => ['id' => (string) $user->id, 'no_hp_email' => $user->no_hp_email],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        return response()->json([
            'authenticated' => $user !== null,
            'user' => $user ? [
                'id' => (string) $user->id,
                'no_hp_email' => $user->no_hp_email,
            ] : null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Jangan invalidate() seluruh session karena Admin menggunakan session yang sama
        // tetapi dengan key autentikasi berbeda.
        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout pengunjung berhasil.']);
    }
}
