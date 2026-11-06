<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mewajibkan sesi web pengunjung pada halaman/form pemesanan Event.
 * Tidak memberikan hak Admin atau mengubah kontrak API Sanctum.
 */
class EnsureVisitorBookingLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        $eventId = $request->isMethod('GET')
            ? $request->query('event')
            : $request->input('event_id');

        $isOutside = $request->is('event/futsal/pesan-tiket-luar');
        $formRoute = $isOutside ? 'events.pesan-tiket-luar' : 'events.pesan-tiket';
        $params = [];

        if (is_string($eventId) && Str::isUuid($eventId)) {
            $params['event'] = $eventId;
        }

        // Hanya path internal yang diizinkan oleh halaman login (allowlist).
        $returnPath = route($formRoute, $params, false);

        return redirect()->route('pengunjung.login', ['next' => $returnPath]);
    }
}
