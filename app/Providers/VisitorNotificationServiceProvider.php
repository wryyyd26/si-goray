<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\QrTicket;
use App\Models\User;
use App\Observers\VisitorBookingObserver;
use App\Observers\VisitorPaymentObserver;
use App\Observers\VisitorQrObserver;
use App\Observers\VisitorUserObserver;
use Illuminate\Support\ServiceProvider;

class VisitorNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(VisitorUserObserver::class);
        Booking::observe(VisitorBookingObserver::class);
        PaymentTransaction::observe(VisitorPaymentObserver::class);
        QrTicket::observe(VisitorQrObserver::class);
    }
}
