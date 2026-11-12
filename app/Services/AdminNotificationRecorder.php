<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminNotificationRecorder
{
    /** Kunci unik memastikan notifikasi tidak terulang akibat event/callback berulang. */
    public function record(
        string $eventKey,
        string $type,
        string $title,
        string $message,
        string $icon,
        string $url
    ): void {
        DB::table('admin_notifications')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'event_key' => $eventKey,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'url' => $url,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
