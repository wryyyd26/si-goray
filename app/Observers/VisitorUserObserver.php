<?php

namespace App\Observers;

use App\Models\User;
use App\Services\VisitorNotificationRecorder;

class VisitorUserObserver
{
    public function updated(User $user): void
    {
        // Jangan kirim ketika OTP baru diminta atau ketika login ulang.
        if (!$user->wasChanged('status_otp') || !$user->status_otp) {
            return;
        }

        app(VisitorNotificationRecorder::class)->record(
            (string) $user->getKey(),
            'welcome:' . $user->getKey(),
            'all',
            'Selamat Datang di SI-GORAY!',
            'Akunmu sudah diverifikasi. Sekarang kamu dapat melihat event dan memesan tiket.',
            'waving_hand'
        );
    }
}
