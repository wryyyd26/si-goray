<?php

namespace App\Observers;

use App\Models\EventSchedule;
use App\Services\VisitorEventAnnouncementService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class VisitorEventScheduleObserver implements ShouldHandleEventsAfterCommit
{
    public function created(EventSchedule $schedule): void
    {
        app(VisitorEventAnnouncementService::class)->announceIfPublic((string) $schedule->event_id);
    }

    public function updated(EventSchedule $schedule): void
    {
        if ($schedule->wasChanged(['tanggal', 'event_id'])) {
            app(VisitorEventAnnouncementService::class)->announceIfPublic((string) $schedule->event_id);
        }
    }
}
