<?php

namespace App\Observers;

use App\Models\SaranMasukan;
use App\Services\AdminNotificationRecorder;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class FeedbackAdminNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(SaranMasukan $feedback): void
    {
        app(AdminNotificationRecorder::class)->record(
            'feedback:' . $feedback->id . ':created',
            'FEEDBACK_NEW',
            'Saran dan Masukan Baru',
            'Ada pesan pengunjung baru pada halaman Kontak Kami.',
            'mail',
            route('admin.saran-masukan.detail', ['saranMasukan' => $feedback->id])
        );
    }
}
