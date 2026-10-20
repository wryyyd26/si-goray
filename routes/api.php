<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\GateAccessController;
use App\Http\Controllers\Api\GateAuthController;

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

Route::middleware('auth:sanctum')->post(
    '/gate/scan',
    [GateAccessController::class, 'scan']
);

// Login petugas menggunakan OTP.
// Throttle ini merupakan pembatas awal.
// Nanti kita tambahkan pembatas per identitas akun.
Route::post(
    '/gate/auth/verify-otp',
    [GateAuthController::class, 'verifyOtp']
)->middleware('throttle:5,1');

// Logout petugas.
// Hanya token dengan kemampuan gate:scan yang boleh
// menggunakan endpoint ini.
Route::post(
    '/gate/auth/logout',
    [GateAuthController::class, 'logout']
)->middleware(['auth:sanctum', 'abilities:gate:scan']);

// Meminta OTP login petugas.
Route::post(
    '/gate/auth/request-otp',
    [GateAuthController::class, 'requestOtp']
)->middleware('throttle:3,1');

Route::post('/bookings', [BookingController::class, 'store']);

Route::get('/bookings/history', [BookingController::class, 'history']);