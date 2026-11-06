<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\FreeBookingConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class RegularFreeConfirmationController extends Controller
{
    public function store(Request $request, Booking $booking, FreeBookingConfirmationService $service): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user && (string) $booking->user_id === (string) $user->id, 403);
        abort_unless($booking->jenis_booking === 'REGULER', 404);

        try {
            // Seluruh aturan gratis, kepemilikan, QR dan transaksi ada di service existing.
            // Jangan memercayai kategori warga atau nominal kiriman browser.
            $service->confirm((string) $booking->id, (string) $user->id);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('reguler.booking.draft', ['booking' => $booking->id])
                ->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            return redirect()->route('reguler.booking.draft', ['booking' => $booking->id])
                ->with('error', $e->getMessage());
        }

        return redirect()->route('reguler.booking.draft', ['booking' => $booking->id])
            ->with('success', 'Booking gratis telah dikonfirmasi dan tiket telah diterbitkan.');
    }
}
