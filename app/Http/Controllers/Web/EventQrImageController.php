<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\QrSvgRenderer;
use App\Services\QrTicketService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventQrImageController extends Controller
{
    public function show(Request $request, Booking $booking, QrTicketService $qrTickets, QrSvgRenderer $renderer): Response
    {
        $user = $request->user('web');
        abort_unless($user && (string) $booking->user_id === (string) $user->id, 403);
        abort_unless($booking->jenis_booking === 'EVENT', 404);
        abort_unless($booking->status_booking === 'CONFIRMED', 404);
        $ticket = $booking->activeQrTicket()->first();
        if (!$ticket || !$ticket->waktu_expired || $ticket->waktu_expired->lte(now())) {
            abort(410, 'Tiket QR tidak aktif atau kedaluwarsa.');
        }
        $check = $qrTickets->validate((string) $ticket->payload_signed);
        if (!($check['valid'] ?? false)) {
            abort(410, 'Tiket QR tidak dapat digunakan.');
        }
        return response($renderer->render((string) $ticket->payload_signed), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
