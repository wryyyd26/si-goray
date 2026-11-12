<?php

use App\Http\Controllers\Web\VisitorNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])
    ->prefix('pengunjung/notifications')
    ->name('pengunjung.notifications.')
    ->group(function (): void {
        Route::get('/', [VisitorNotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [VisitorNotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notification}/read', [VisitorNotificationController::class, 'markRead'])
            ->whereUuid('notification')->name('read');
    });
