<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VisitorEventAnnouncementService
{
    /**
     * An event is public only when ACTIVE and has at least one upcoming schedule.
     * Unique event_key is per-event/per-user and makes repeated observer events safe.
     */
    public function announceIfPublic(string $eventId): void
    {
        $event = Event::query()->find($eventId);

        if (!$event || $event->status !== 'AKTIF') {
            return;
        }

        if (!$event->schedules()->whereDate('tanggal', '>=', today())->exists()) {
            return;
        }

        $eventName = trim((string) $event->nama_event);
        if ($eventName === '') {
            return;
        }

        // Re-evaluate eligibility immediately before sending to reduce stale announcements.
        $recorder = app(VisitorNotificationRecorder::class);
        User::query()
            ->where('status_otp', true)
            ->select('id')
            ->chunkById(200, function ($users) use ($eventId, $eventName, $recorder) {
                foreach ($users as $user) {
                    $recorder->record(
                        (string) $user->id,
                        'visitor-event-new:' . $eventId . ':' . $user->id,
                        'event',
                        'Event Baru Tersedia',
                        'Event ' . $eventName . ' sudah tersedia. Lihat jadwal dan informasi event di halaman Event.',
                        'campaign'
                    );
                }
            });
    }
}
