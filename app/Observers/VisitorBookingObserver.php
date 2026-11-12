<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\VisitorNotificationRecorder;

class VisitorBookingObserver
{
    public function created(Booking $booking): void
    {
        if (!$booking->user_id || $booking->status_booking !== 'DRAFT') {
            return;
        }

        app(VisitorNotificationRecorder::class)->record(
            (string) $booking->user_id,
            'booking-created:' . $booking->getKey(),
            'payment',
            'Pemesanan Berhasil Dibuat',
            'Pemesananmu tercatat. Periksa status dan instruksi selanjutnya melalui Pesanan Saya.',
            'event_note'
        );
    }
}
