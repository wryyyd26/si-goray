<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\SaranMasukan;
use App\Observers\BookingAdminNotificationObserver;
use App\Observers\FeedbackAdminNotificationObserver;
use App\Observers\PaymentAdminNotificationObserver;
use Illuminate\Support\ServiceProvider;

class AdminNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Booking::observe(BookingAdminNotificationObserver::class);
        PaymentTransaction::observe(PaymentAdminNotificationObserver::class);
        SaranMasukan::observe(FeedbackAdminNotificationObserver::class);
    }
}
