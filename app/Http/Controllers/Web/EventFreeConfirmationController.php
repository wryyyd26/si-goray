<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\FreeBookingConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class EventFreeConfirmationController extends Controller
{
    public function store(Request $request, Booking $booking, FreeBookingConfirmationService $service): RedirectResponse
    {
        abort_unless($request->user('web') && (string) $booking->user_id === (string) $request->user('web')->id, 403);
        abort_unless($booking->jenis_booking === 'EVENT', 404);
        try {
            $service->confirm((string) $booking->id, (string) $request->user('web')->id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            report($e);
            return back()->with('error', 'Konfirmasi perlu pemeriksaan petugas.');
        }
        return redirect()->route('events.booking.draft', ['booking' => $booking->id])->with('success', 'Tiket gratis telah dikonfirmasi.');
    }
}
