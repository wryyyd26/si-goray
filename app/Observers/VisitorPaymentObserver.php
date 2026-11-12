<?php

namespace App\Observers;

use App\Models\PaymentTransaction;
use App\Services\VisitorNotificationRecorder;

class VisitorPaymentObserver
{
    public function updated(PaymentTransaction $payment): void
    {
        if (!$payment->wasChanged('status_pembayaran') || $payment->status_pembayaran !== 'SUCCESS') {
            return;
        }

        $booking = $payment->booking;
        if (!$booking || !$booking->user_id) {
            return;
        }

        app(VisitorNotificationRecorder::class)->record(
            (string) $booking->user_id,
            'payment-success:' . $payment->getKey(),
            'payment',
            'Pembayaran Berhasil',
            'Pembayaran pesananmu telah terverifikasi. Periksa status tiket pada Pesanan Saya.',
            'check_circle'
        );
    }
}
