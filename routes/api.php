<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\GateAccessController;
use App\Http\Controllers\Api\GateAuthController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PengunjungAuthController;
use App\Http\Controllers\Api\RegularSlotCalendarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;

/*
|--------------------------------------------------------------------------
| API SI-GORAY
|--------------------------------------------------------------------------
|
| Endpoint:
| 1. Pemeriksaan API
| 2. Booking pengunjung
| 3. Pembayaran Midtrans
| 4. Autentikasi petugas gerbang
| 5. Scan QR Ticket
| 6. Autentikasi pengunjung
| 7. Kalender slot reguler
|
*/

/*
|--------------------------------------------------------------------------
| 1. Pemeriksaan API
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
| 2. Booking Pengunjung
|--------------------------------------------------------------------------
*/

// Membuat booking.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:create',
])->post(
    '/bookings',
    [BookingController::class, 'store']
);

// Mengambil riwayat booking milik pengguna.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->get(
    '/bookings/history',
    [BookingController::class, 'history']
);

// Mengambil atau menerbitkan QR Ticket booking.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/bookings/{booking}/qr-ticket',
    [BookingController::class, 'qrTicket']
);

 // Meregenerasi QR Ticket booking.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/bookings/{booking}/qr-ticket/regenerate',
    [BookingController::class, 'regenerateQrTicket']
);

/*
|--------------------------------------------------------------------------
| 3. Pembayaran Midtrans
|--------------------------------------------------------------------------
*/

// Membuat transaksi pembayaran untuk booking milik pengguna.
Route::middleware([
    'auth:sanctum',
])->post(
    '/bookings/{booking}/payment',
    [PaymentController::class, 'store']
);

// Callback Midtrans.
// Tidak menggunakan auth:sanctum karena dipanggil oleh gateway.
// Validasi signature dan status gateway tetap dilakukan
// oleh PaymentCallbackController dan service terkait.
Route::post(
    '/payments/callback',
    [PaymentCallbackController::class, 'receive']
);

/*
|--------------------------------------------------------------------------
| 4. Autentikasi Petugas Gerbang
|--------------------------------------------------------------------------
*/

// Meminta OTP petugas.
Route::middleware([
    'throttle:3,1',
])->post(
    '/gate/auth/request-otp',
    [GateAuthController::class, 'requestOtp']
);

// Memverifikasi OTP petugas.
Route::middleware([
    'throttle:5,1',
])->post(
    '/gate/auth/verify-otp',
    [GateAuthController::class, 'verifyOtp']
);

// Logout petugas dengan token yang memiliki izin scan.
Route::middleware([
    'auth:sanctum',
    'abilities:gate:scan',
])->post(
    '/gate/auth/logout',
    [GateAuthController::class, 'logout']
);

// Memindai QR Ticket.
// Autentikasi dan kemampuan gate:scan wajib diperiksa.
Route::middleware([
    'auth:sanctum',
    'abilities:gate:scan',
])->post(
    '/gate/scan',
    [GateAccessController::class, 'scan']
);

/*
|--------------------------------------------------------------------------
| 5. Autentikasi Pengunjung
|--------------------------------------------------------------------------
*/

// Meminta OTP pengunjung.
Route::post(
    '/pengunjung/auth/request-otp',
    [PengunjungAuthController::class, 'requestOtp']
);

// Memverifikasi OTP pengunjung.
Route::post(
    '/pengunjung/auth/verify-otp',
    [PengunjungAuthController::class, 'verifyOtp']
);

// Logout pengunjung yang sudah terautentikasi.
Route::middleware([
    'auth:sanctum',
    'abilities:booking:read',
])->post(
    '/pengunjung/auth/logout',
    [PengunjungAuthController::class, 'logout']
);

/*
|--------------------------------------------------------------------------
| 6. Kalender Slot Reguler
|--------------------------------------------------------------------------
*/

// Menampilkan ketersediaan slot reguler.
Route::get(
    '/reguler/slots',
    [RegularSlotCalendarController::class, 'index']
)->name('api.reguler.slots');
