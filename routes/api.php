<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GateAccessController;
use App\Http\Controllers\Api\GateAuthController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PengunjungAuthController;
use App\Http\Controllers\Api\PengunjungIdentityController;
use App\Http\Controllers\Api\RegularSlotCalendarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API SI-GORAY
|--------------------------------------------------------------------------
| Booking, pembayaran, OTP, tiket QR, akses gerbang,
| event, slot reguler, dan identitas pengunjung.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Pemeriksaan API
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
| Event Publik
|--------------------------------------------------------------------------
*/

Route::get('/events', [
    EventController::class,
    'index',
])->name('api.events.index');

Route::get('/events/{event}', [
    EventController::class,
    'show',
])->name('api.events.show');

/*
|--------------------------------------------------------------------------
| Booking Pengunjung
|--------------------------------------------------------------------------
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

Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/bookings/{booking}/qr-ticket',
    [BookingController::class, 'qrTicket']
);

Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/bookings/{booking}/qr-ticket/regenerate',
    [BookingController::class, 'regenerateQrTicket']
);

/*
|--------------------------------------------------------------------------
| Pembayaran Midtrans
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
])->post(
    '/bookings/{booking}/payment',
    [PaymentController::class, 'store']
);

// Callback Midtrans menggunakan verifikasi signature gateway,
// bukan autentikasi pengguna Sanctum.
Route::post(
    '/payments/callback',
    [PaymentCallbackController::class, 'receive']
);

/*
|--------------------------------------------------------------------------
| Autentikasi Petugas Gerbang
|--------------------------------------------------------------------------
*/

Route::middleware([
    'throttle:3,1',
])->post(
    '/gate/auth/request-otp',
    [GateAuthController::class, 'requestOtp']
);

Route::middleware([
    'throttle:5,1',
])->post(
    '/gate/auth/verify-otp',
    [GateAuthController::class, 'verifyOtp']
);

Route::middleware([
    'auth:sanctum',
    'abilities:gate:scan',
])->post(
    '/gate/auth/logout',
    [GateAuthController::class, 'logout']
);

Route::middleware([
    'auth:sanctum',
    'abilities:gate:scan',
])->post(
    '/gate/scan',
    [GateAccessController::class, 'scan']
);

/*
|--------------------------------------------------------------------------
| Autentikasi Pengunjung
|--------------------------------------------------------------------------
*/

Route::post(
    '/pengunjung/auth/request-otp',
    [PengunjungAuthController::class, 'requestOtp']
);

Route::post(
    '/pengunjung/auth/verify-otp',
    [PengunjungAuthController::class, 'verifyOtp']
);

Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/pengunjung/auth/logout',
    [PengunjungAuthController::class, 'logout']
);

/*
|--------------------------------------------------------------------------
| Identitas Pengunjung
|--------------------------------------------------------------------------
*/

// Mengambil identitas milik pengguna yang login.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->get(
    '/pengunjung/identities',
    [PengunjungIdentityController::class, 'index']
)->name('api.pengunjung.identities.index');

// Mendaftarkan identitas baru.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:create',
])->post(
    '/pengunjung/identities',
    [PengunjungIdentityController::class, 'store']
)->name('api.pengunjung.identities.store');

/*
|--------------------------------------------------------------------------
| Kalender Slot Reguler
|--------------------------------------------------------------------------
*/

Route::get(
    '/reguler/slots',
    [RegularSlotCalendarController::class, 'index']
)->name('api.reguler.slots');
