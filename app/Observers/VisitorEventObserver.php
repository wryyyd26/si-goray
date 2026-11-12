<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\VisitorEventAnnouncementService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class VisitorEventObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Event $event): void
    {
        app(VisitorEventAnnouncementService::class)->announceIfPublic((string) $event->id);
    }

    public function updated(Event $event): void
    {
        if ($event->wasChanged('status')) {
            app(VisitorEventAnnouncementService::class)->announceIfPublic((string) $event->id);
        }
    }
}
