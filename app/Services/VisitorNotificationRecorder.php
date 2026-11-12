<?php

namespace App\Services;

use App\Models\VisitorNotification;

class VisitorNotificationRecorder
{
    /** event_key unik membuat callback/observer ulang tetap idempoten. */
    public function record(string $userId, string $key, string $category, string $title, string $message, string $icon = 'notifications'): void
    {
        VisitorNotification::query()->firstOrCreate(
            ['event_key' => $key],
            [
                'user_id' => $userId,
                'category' => $category,
                'title' => $title,
                'message' => $message,
                'icon' => $icon,
            ]
        );
    }
}
