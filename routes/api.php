<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API SI-GORAY aktif.',
    ]);
});

Route::post('/bookings', [BookingController::class, 'store']);

Route::get('/bookings/history', [BookingController::class, 'history']);