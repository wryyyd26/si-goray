<?php

namespace App\Observers;

use App\Models\QrTicket;
use App\Services\VisitorNotificationRecorder;

class VisitorQrObserver
{
    public function created(QrTicket $qr): void
    {
        if ($qr->status_qr !== 'AKTIF') {
            return;
        }

        $booking = $qr->booking;
        if (!$booking || !$booking->user_id) {
            return;
        }

        app(VisitorNotificationRecorder::class)->record(
            (string) $booking->user_id,
            'ticket-ready:' . $qr->getKey(),
            'event',
            'Tiket QR Tersedia',
            'Tiket QR untuk pemesananmu sudah diterbitkan. Lihat melalui Pesanan Saya.',
            'confirmation_number'
        );
    }
}
