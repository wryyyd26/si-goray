<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\AdminNotificationRecorder;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingAdminNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Booking $booking): void
    {
        app(AdminNotificationRecorder::class)->record(
            'booking:' . $booking->id . ':created',
            'BOOKING_NEW',
            'Pemesanan Baru',
            'Booking ' . strtoupper((string) $booking->jenis_booking) . ' baru dibuat. Status awal: DRAFT.',
            'receipt_long',
            route('admin.booking.show', ['booking' => $booking->id])
        );
    }
}
