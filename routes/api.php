<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\GateAccessController;
use App\Http\Controllers\Api\GateAuthController;
use App\Http\Controllers\Api\RegularSlotCalendarController;
use App\Http\Controllers\Api\EventController;   


/*
|--------------------------------------------------------------------------
| API SI-GORAY
|--------------------------------------------------------------------------
|
| API Sistem Registrasi dan Akses GOR Kota Mojokerto.
|
| Modul:
| 1. Testing API
| 2. Booking Pengunjung
| 3. Pembayaran Midtrans
| 4. Autentikasi Petugas Gerbang
| 5. Scan QR Ticket
|
*/

/*
|--------------------------------------------------------------------------
| 1. Testing API
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API SI-GORAY aktif.',
    ]);
});

/*
|--------------------------------------------------------------------------
| 2. Booking Pengunjung
|--------------------------------------------------------------------------
|
| Booking hanya dapat dilakukan oleh pengguna
| yang sudah terautentikasi menggunakan Sanctum.
|
*/

Route::middleware([
    'auth:sanctum',
    'abilities:booking:create',
])->post(
    '/bookings',
    [BookingController::class, 'store']
);

Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->get(
    '/bookings/history',
    [BookingController::class, 'history']
);

/*
|--------------------------------------------------------------------------
| 3. Pembayaran Midtrans
|--------------------------------------------------------------------------
|
| Pembayaran dilakukan berdasarkan booking.
| Callback Midtrans tetap bersifat publik,
| tetapi signature diverifikasi oleh backend.
|
*/

Route::middleware('auth:sanctum')->post(
    '/bookings/{booking}/payment',
    [PaymentController::class, 'store']
);

Route::post(
    '/payments/callback',
    [PaymentCallbackController::class, 'receive']
);

/*
|--------------------------------------------------------------------------
| 4. Autentikasi Petugas Gerbang
|--------------------------------------------------------------------------
|
| Login petugas menggunakan OTP.
| Permintaan OTP dan verifikasi OTP
| dibatasi menggunakan throttle.
|
*/

Route::post(
    '/gate/auth/request-otp',
    [GateAuthController::class, 'requestOtp']
)->middleware('throttle:3,1');

Route::post(
    '/gate/auth/verify-otp',
    [GateAuthController::class, 'verifyOtp']
)->middleware('throttle:5,1');

Route::post(
    '/gate/auth/logout',
    [GateAuthController::class, 'logout']
)->middleware([
    'auth:sanctum',
    'abilities:gate:scan',
]);

Route::get('/events', [EventController::class, 'index'])
    ->name('api.events.index');

/*
|--------------------------------------------------------------------------
| 5. Gate Access - QR Scanner
|--------------------------------------------------------------------------
|
| Endpoint hanya dapat diakses petugas
| dengan token yang memiliki kemampuan gate:scan.
|
*/

Route::middleware([
    'auth:sanctum',
    'abilities:gate:scan',
])->post(
    '/gate/scan',
    [GateAccessController::class, 'scan']
);
/* Login / registrasi pengunjung menggunakan OTP email. */
Route::post('/pengunjung/auth/request-otp',
    [\App\Http\Controllers\Api\PengunjungAuthController::class, 'requestOtp']
)->middleware('throttle:3,1');

Route::post('/pengunjung/auth/verify-otp',
    [\App\Http\Controllers\Api\PengunjungAuthController::class, 'verifyOtp']
)->middleware('throttle:5,1');

Route::post('/pengunjung/auth/logout',
    [\App\Http\Controllers\Api\PengunjungAuthController::class, 'logout']
)->middleware(['auth:sanctum', 'abilities:booking:read']);

Route::get(
    '/reguler/slots',
    [RegularSlotCalendarController::class, 'index']
)->name('api.reguler.slots');