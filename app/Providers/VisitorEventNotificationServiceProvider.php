<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Observers\VisitorEventObserver;
use App\Observers\VisitorEventScheduleObserver;
use Illuminate\Support\ServiceProvider;

class VisitorEventNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::observe(VisitorEventObserver::class);
        EventSchedule::observe(VisitorEventScheduleObserver::class);
    }
}
