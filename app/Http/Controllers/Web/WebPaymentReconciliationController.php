<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MidtransCallbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class WebPaymentReconciliationController extends Controller
{
    public function sync(Request $request, Booking $booking, MidtransCallbackService $service): RedirectResponse
    {
        abort_unless($request->user('web') &&
            (string) $request->user('web')->id === (string) $booking->user_id, 403);
        abort_unless(in_array($booking->jenis_booking, ['REGULER', 'EVENT'], true), 404);
        abort_unless($booking->retribusi_diperlukan, 404);

        $draftRoute = $booking->jenis_booking === 'REGULER'
            ? 'reguler.booking.draft' : 'events.booking.draft';
        $draftUrl = route($draftRoute, ['booking' => $booking->id]);

        if ($booking->status_booking === 'CONFIRMED') {
            return redirect()->to($draftUrl);
        }

        if ($booking->status_booking !== 'DRAFT') {
            return redirect()->to($draftUrl)->with('error', 'Status booking perlu pemeriksaan petugas.');
        }

        $payment = $booking->paymentTransactions()
            ->where('status_pembayaran', 'PENDING')
            ->orderByDesc('created_at')
            ->first();

        if (!$payment || !$payment->snap_token) {
            return redirect()->to($draftUrl)->with('error', 'Transaksi pembayaran tidak ditemukan atau belum siap.');
        }

        try {
            // Order ID berasal dari DB milik booking ini, bukan input dari browser.
            $result = $service->reconcile((string) $payment->kode_bayar);
        } catch (Throwable $e) {
            report($e);
            return redirect()->to($draftUrl)->with('error', 'Status Midtrans belum dapat diverifikasi. Coba kembali atau hubungi petugas.');
        }

        if (!empty($result['requires_review'])) {
            return redirect()->to($draftUrl)->with('error', 'Transaksi perlu pemeriksaan petugas sebelum tiket digunakan.');
        }

        if (($result['status'] ?? null) === 'SUCCESS') {
            return redirect()->to($draftUrl)->with('success', 'Pembayaran berhasil diverifikasi oleh Midtrans. Tiket sudah dikonfirmasi.');
        }

        return redirect()->to($draftUrl)->with('error', 'Pembayaran belum dinyatakan berhasil oleh Midtrans. Status: '.($result['status'] ?? 'PENDING').'.');
    }
}
