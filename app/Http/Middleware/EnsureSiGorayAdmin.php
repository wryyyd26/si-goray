<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiGorayAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('sigoray_admin.username', '');
        $passwordHash = (string) config('sigoray_admin.password_hash', '');
        $session = $request->session();
        $minutes = max(1, (int) config('sigoray_admin.session_minutes', 120));

        $authenticated = $username !== ''
            && $passwordHash !== ''
            && $session->get('sigoray_admin_authenticated') === true
            && hash_equals($username, (string) $session->get('sigoray_admin_username', ''))
            && hash_equals(
                hash('sha256', $passwordHash),
                (string) $session->get('sigoray_admin_password_version', '')
            )
            && is_numeric($session->get('sigoray_admin_since'))
            && (int) $session->get('sigoray_admin_since') <= now()->timestamp
            && (now()->timestamp - (int) $session->get('sigoray_admin_since')) < $minutes * 60;

        if (! $authenticated) {
            $session->forget([
                'sigoray_admin_authenticated',
                'sigoray_admin_username',
                'sigoray_admin_password_version',
                'sigoray_admin_since',
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Autentikasi Admin diperlukan.'], 401);
            }

            // Jangan mengarahkan POST/PUT/DELETE ke halaman login.
            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                abort(403, 'Autentikasi Admin diperlukan.');
            }

            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
