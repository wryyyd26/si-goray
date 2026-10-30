<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        $expectedUsername = (string) config('sigoray_admin.username', '');
        $passwordHash = (string) config('sigoray_admin.password_hash', '');

        // Fail closed jika kredensial belum dikonfigurasi.
        if ($expectedUsername === '' || $passwordHash === '') {
            abort(503, 'Login Admin belum dikonfigurasi.');
        }

        $key = 'sigoray-admin-login:' . hash('sha256', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'login' => 'Terlalu banyak percobaan. Coba lagi nanti.',
            ])->setStatusCode(429);
        }

        // Periksa hash walau username salah, untuk mengurangi perbedaan waktu respons.
        $passwordValid = Hash::check($data['password'], $passwordHash);
        $usernameValid = hash_equals($expectedUsername, $data['username']);

        if (! $usernameValid || ! $passwordValid) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'Username atau password Admin tidak valid.',
            ]);
        }

        RateLimiter::clear($key);

        // Regenerasi ID sesi melindungi dari session fixation.
        $request->session()->regenerate();
        $request->session()->put([
            'sigoray_admin_authenticated' => true,
            'sigoray_admin_username' => $expectedUsername,
            'sigoray_admin_password_version' => hash('sha256', $passwordHash),
            'sigoray_admin_since' => now()->timestamp,
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        // Tidak menghapus data login pengunjung/petugas dari session yang sama.
        $request->session()->forget([
            'sigoray_admin_authenticated',
            'sigoray_admin_username',
            'sigoray_admin_password_version',
            'sigoray_admin_since',
        ]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
