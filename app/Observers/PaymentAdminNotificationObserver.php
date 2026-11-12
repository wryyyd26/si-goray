<?php

namespace App\Observers;

use App\Models\PaymentTransaction;
use App\Services\AdminNotificationRecorder;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PaymentAdminNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(PaymentTransaction $payment): void
    {
        if (! $payment->wasChanged('status_pembayaran') || $payment->status_pembayaran !== 'SUCCESS') {
            return;
        }

        app(AdminNotificationRecorder::class)->record(
            'payment:' . $payment->id . ':success',
            'PAYMENT_SUCCESS',
            'Pembayaran Berhasil',
            'Pembayaran untuk booking ' . $payment->booking_id . ' telah berhasil.',
            'credit_card',
            route('admin.payment.show', ['payment' => $payment->id])
        );
    }
}
