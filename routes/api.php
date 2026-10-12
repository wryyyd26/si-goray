<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentCallbackController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API SI-GORAY aktif.',
    ]);
});

Route::middleware('auth:sanctum')->post(
    '/bookings/{booking}/payment',
    [PaymentController::class, 'store']
);

Route::post(
    '/payments/callback',
    [PaymentCallbackController::class, 'receive']
);

Route::post('/bookings', [BookingController::class, 'store']);

Route::get('/bookings/history', [BookingController::class, 'history']);