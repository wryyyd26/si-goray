<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WebBookingPaymentController extends Controller
{
    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($request->user('web') && (string) $booking->user_id === (string) $request->user('web')->id, 403);
        abort_unless(in_array($booking->jenis_booking, ['REGULER', 'EVENT'], true), 404);
    }

    private function draftUrl(Booking $booking): string
    {
        return $booking->jenis_booking === 'REGULER'
            ? route('reguler.booking.draft', ['booking' => $booking->id])
            : route('events.booking.draft', ['booking' => $booking->id]);
    }

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $booking->refresh()->load(['tariff', 'regularSlot', 'eventSchedule.event', 'paymentTransactions']);
        if ($booking->status_booking !== 'DRAFT') {
            return redirect()->to($this->draftUrl($booking));
        }
        abort_unless($booking->retribusi_diperlukan, 404);
        abort_unless($booking->tariff && (float) $booking->tariff->nominal > 0, 409);
        return view('web-booking-payment', [
            'booking' => $booking,
            'amount' => (int) $booking->tariff->nominal,
            'draftUrl' => $this->draftUrl($booking),
        ]);
    }

    public function start(Request $request, Booking $booking, PaymentService $payments): View|RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $booking->refresh();
        if ($booking->status_booking !== 'DRAFT' || !$booking->retribusi_diperlukan) {
            return redirect()->to($this->draftUrl($booking))->with('error', 'Booking ini tidak dapat dibayar.');
        }
        $clientKey = config('midtrans.client_key');
        if (!is_string($clientKey) || $clientKey === '') {
            report(new RuntimeException('MIDTRANS_CLIENT_KEY belum dikonfigurasi.'));
            return back()->with('error', 'Layanan pembayaran belum tersedia.');
        }
        try {
            $transaction = $payments->createPayment((string) $booking->id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Pembayaran belum dapat dimulai. Periksa status transaksi atau hubungi petugas.');
        }
        if (!is_string($transaction['snap_token'] ?? null) || $transaction['snap_token'] === '') {
            return back()->with('error', 'Token pembayaran belum tersedia.');
        }
        return view('web-booking-snap', [
            'booking' => $booking,
            'snapToken' => $transaction['snap_token'],
            'clientKey' => $clientKey,
            'isProduction' => (bool) config('midtrans.is_production', false),
            'draftUrl' => $this->draftUrl($booking),
            'paymentUrl' => route('booking.payment.show', ['booking' => $booking->id]),
        ]);
    }
}
