<?php

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\RegularSlotController;


/*
|--------------------------------------------------------------------------
| Frontend Pengunjung SI-GORAY
|--------------------------------------------------------------------------
*/

// ROUTE USER
Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/events/detail', 'events.show')
    ->name('events.show');

Route::get('/event/futsal/pesan-tiket', function () {
    return view('events.event-tiket');
})->name('events.pesan-tiket');

Route::get('/event/futsal/pesan-tiket-luar', function () {
    return view('events.event-tiket-luar');
})->name('events.pesan-tiket-luar');

// FORM WARGA KOTA
Route::post('/event/futsal/pesan-tiket', function (Request $request) {
    $data = $request->validate([
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $request->session()->put('detail_pemesanan', $data);

    return redirect()->route('events.detail-warga');
})->name('events.pesan-tiket.simpan');

Route::get('/event/futsal/detail-pemesanan-warga', function () {
    abort_unless(session()->has('detail_pemesanan'), 403);

    return view('events.detail-pemesanan-warga');
})->name('events.detail-warga');


// FORM WARGA LUAR KOTA
Route::post('/event/futsal/pesan-tiket-luar', function (Request $request) {
    $data = $request->validate([
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'alamat_ktp' => ['required', 'string', 'max:1000'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $request->session()->put('detail_pemesanan', $data);

    return redirect()->route('events.detail-luar');
})->name('events.pesan-tiket-luar.simpan');

Route::get('/event/futsal/detail-pemesanan-luar', function () {
    abort_unless(session()->has('detail_pemesanan'), 403);

    return view('events.detail-pemesanan-luar');
})->name('events.detail-luar');



Route::get('/events', function (Request $request) {
    $search = trim((string) $request->query('q', ''));

    try {
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->get('http://192.168.37.242:8000/api/events', [
                'q' => $search,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'API event mengembalikan HTTP ' . $response->status()
            );
        }

        $payload = $response->json();

        if (
            !is_array($payload) ||
            !($payload['success'] ?? false)
        ) {
            throw new \RuntimeException(
                'Format respons API event tidak sesuai.'
            );
        }

        $events = collect($payload['data'] ?? [])
            ->map(function ($event) {
                return [
                    'id' => $event['id'] ?? '',
                    'title' => $event['title'] ?? 'Event',
                    'description' => $event['description'] ?? '',
                    'image' => $event['image_url']
                        ?? $event['image']
                        ?? 'images/events/futsal.jpg',
                    'category' => $event['category'] ?? 'Event',
                    'remaining' => (int) ($event['remaining'] ?? 0),
                    'total' => (int) ($event['total'] ?? 0),
                    'price' => (float) ($event['price'] ?? 0),
                ];
            });

        return view('events', [
            'events' => $events,
            'selectedCategory' => 'Semua',
            'search' => $search,
        ]);
   } catch (\Throwable $exception) {
    report($exception);

    return response(
        'Penyebab error: ' . $exception->getMessage(),
        503
    );
}
})->name('events.index');


Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// PEMBAYARAN WARGA LUAR KOTA

Route::get('/pembayaran/luar-kota', function () {
    $detail = session('detail_pemesanan', []);

    if (empty($detail)) {
        return redirect()->route('events.pesan-tiket-luar');
    }

    $subtotal = 25000;
    $biayaLayanan = 1000;
    $total = $subtotal + $biayaLayanan;

    return view('pembayaran', [
        'detail' => $detail,
        'subtotal' => $subtotal,
        'biayaLayanan' => $biayaLayanan,
        'total' => $total,
        'jenisPemesan' => 'luar-kota',
    ]);
})->name('pembayaran.luar-kota');



// PEMBAYARAN WARGA DALAM KOTA
Route::get('/pembayaran/warga-kota', function () {
    $detail = session('detail_pemesanan', []);

    if (empty($detail)) {
        return redirect()->route('events.pesan-tiket')
            ->with('error', 'Silakan lengkapi data pemesanan terlebih dahulu.');
    }

    $subtotal = (float) ($detail['subtotal']
        ?? $detail['total_pembayaran']
        ?? $detail['total_harga']
        ?? 0);

    $biayaLayanan = (float) ($detail['biaya_layanan'] ?? 0);
    $total = (float) ($detail['total_pembayaran']
        ?? ($subtotal + $biayaLayanan));

    return view('pembayaran', [
        'detail' => $detail,
        'subtotal' => $subtotal,
        'biayaLayanan' => $biayaLayanan,
        'total' => $total,
        'jenisPemesan' => 'warga-kota',
    ]);
})->name('pembayaran.show');


// KONFIRMASI PEMBAYARAN SEMENTARA
Route::post('/pembayaran/konfirmasi', function (Request $request) {
    $detail = session('detail_pemesanan', []);

    if (empty($detail)) {
        return redirect()->route('events.pesan-tiket');
    }

    $jenisPemesan = $request->input('jenis_pemesan', 'warga-kota');

    $total = $jenisPemesan === 'luar-kota'
        ? 26000
        : (float) ($detail['total_pembayaran']
            ?? $detail['total_harga']
            ?? 0);

    if ($total > 0) {
        return back()->with(
            'error',
            'Pembayaran belum dapat dikonfirmasi otomatis. Integrasi payment gateway diperlukan.'
        );
    }

    return back()->with(
        'success',
        'Pemesanan gratis siap diproses melalui backend.'
    );
})->name('pembayaran.konfirmasi');



/*
|--------------------------------------------------------------------------
| Admin SI-GORAY
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {
        return redirect()->route('admin.events.index');
    })->name('dashboard');

    Route::post('/reguler', [
    RegularSlotController::class,
    'store',
])->name('reguler.store');


    /*
    |--------------------------------------------------------------------------
    | Tiket Reguler
    |--------------------------------------------------------------------------
    |
    | Daftar slot, pengaturan jadwal, dan penyimpanan kuota.
    | Tampilan Blade tetap menggunakan file frontend yang tersedia.
    |
    */

    Route::get('/reguler', [
        RegularSlotController::class,
        'index',
    ])->name('reguler.index');

    Route::get('/reguler/{date}/edit', [
        RegularSlotController::class,
        'edit',
    ])->where('date', '\d{4}-\d{2}-\d{2}')
      ->name('reguler.edit');

    Route::put('/reguler/{date}', [
        RegularSlotController::class,
        'update',
    ])->where('date', '\d{4}-\d{2}-\d{2}')
      ->name('reguler.update');


// Edit satu slot reguler berdasarkan UUID.
Route::get(
    '/reguler/slots/{slotId}/edit',
    [RegularSlotController::class, 'editById']
)
    ->whereUuid('slotId')
    ->name('reguler.slots.edit');

// Simpan perubahan satu slot reguler berdasarkan UUID.
Route::put(
    '/reguler/slots/{slotId}',
    [RegularSlotController::class, 'updateById']
)
    ->whereUuid('slotId')
    ->name('reguler.slots.update');


    /*
    |--------------------------------------------------------------------------
    | Booking
    |--------------------------------------------------------------------------
    | Sementara menunggu integrasi controller booking Admin.
    */

    Route::get('/booking', function () {
        return redirect()->route('admin.events.index');
    })->name('booking.pemesanan');


    /*
    |--------------------------------------------------------------------------
    | Pembayaran
    |--------------------------------------------------------------------------
    | Sementara menunggu integrasi controller pembayaran Admin.
    */

    Route::get('/payments', function () {
        return redirect()->route('admin.events.index');
    })->name('payment.pembayaran');


    /*
    |--------------------------------------------------------------------------
    | Pengguna
    |--------------------------------------------------------------------------
    */

    Route::get('/users', function () {
        return redirect()->route('admin.events.index');
    })->name('user.index');


    /*
    |--------------------------------------------------------------------------
    | Saran dan Masukan
    |--------------------------------------------------------------------------
    */

    Route::get('/saran-masukan', function () {
        return redirect()->route('admin.events.index');
    })->name('saran-masukan.index');

 /*
    |--------------------------------------------------------------------------
    | event.api
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Laporan
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', function () {
        return redirect()->route('admin.events.index');
    })->name('report.index');


    /*
    |--------------------------------------------------------------------------
    | Manajemen Event
    |--------------------------------------------------------------------------
    */

    Route::get('/events', [
        AdminEventController::class,
        'index',
    ])->name('events.index');

    Route::get('/events/create', [
        AdminEventController::class,
        'create',
    ])->name('events.create');

    Route::post('/events', [
        AdminEventController::class,
        'store',
    ])->name('events.store');

    Route::get('/events/{event}/edit', [
        AdminEventController::class,
        'edit',
    ])->name('events.edit');

    Route::put('/events/{event}', [
        AdminEventController::class,
        'update',
    ])->name('events.update');

});