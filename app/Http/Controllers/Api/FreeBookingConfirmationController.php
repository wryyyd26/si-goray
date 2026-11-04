<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\FreeBookingConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class FreeBookingConfirmationController extends Controller
{
    public function store(
        Request $request,
        Booking $booking,
        FreeBookingConfirmationService $service
    ): JsonResponse {
        // Kepemilikan divalidasi sebelum service dijalankan dan di dalam transaksi.
        if ((string) $booking->user_id !== (string) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
            ], 403);
        }

        try {
            $ticket = $service->confirm(
                (string) $booking->id,
                (string) $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking gratis berhasil dikonfirmasi.',
            'data' => [
                'booking_id' => (string) $booking->id,
                'status_booking' => 'CONFIRMED',
                'qr_ticket_id' => (string) $ticket->id,
                'qr_payload' => $ticket->payload_signed,
                'status_qr' => $ticket->status_qr,
                'waktu_expired' => $ticket->waktu_expired?->toISOString(),
            ],
        ]);
    }
}
