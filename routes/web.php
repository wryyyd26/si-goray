<?php

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\RegularSlotController;
use Illuminate\Support\Facades\Http;



/*
|--------------------------------------------------------------------------
| Frontend Pengunjung SI-GORAY
|--------------------------------------------------------------------------
*/

// ROUTE USER

 // ROUTE USER - HOME MENGAMBIL DATA EVENT DARI API BACKEND
Route::get('/', function () {
    $events = collect();

    try {
        $response = Http::timeout(10)
            ->get('http://192.168.37.247:8000/api/events');

        if ($response->successful()) {
            // Hapus UTF-8 BOM dari awal respons API.
            $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());

            $payload = json_decode($body, true);

            if (
                is_array($payload) &&
                ($payload['success'] ?? false) === true &&
                is_array($payload['data'] ?? null)
            ) {
                $events = collect($payload['data'])
                    ->take(3)
                    ->map(function ($event) {
                        $image = $event['image_url']
                            ?? $event['image']
                            ?? '';

                        // Gunakan alamat backend agar gambar bisa diakses.
                        if (str_starts_with($image, 'http://localhost:8000/')) {
                            $image = str_replace(
                                'http://localhost:8000/',
                                'http://192.168.37.247:8000/',
                                $image
                            );
                        }

                        return (object) [
                            'id' => $event['id'] ?? '',
                            'nama_event' => $event['title'] ?? 'Event',
                            'gambar' => $image,
                            'deskripsi' => $event['description'] ?? '',
                            'category' => $event['category'] ?? 'Event',
                            'remaining' => (int) ($event['remaining'] ?? 0),
                            'total' => (int) ($event['total'] ?? 0),
                            'price' => $event['price'] ?? 'Gratis',
                        ];
                    });
            }
        }
    } catch (\Throwable $e) {
        report($e);
    }

    return view('home', compact('events'));
})->name('home');



Route::get('/events/{id}', function ($id) {
    $response = Http::timeout(10)
        ->get('http://192.168.37.247:8000/api/events');

    if (!$response->successful()) {
        abort(502, 'API event tidak dapat diakses.');
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
        abort(502, 'Format respons API tidak valid.');
    }

    $event = collect($payload['data'])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $id
    );

    if (!$event) {
        abort(404, 'Event tidak ditemukan.');
    }

    $image = $event['image_url'] ?? $event['image'] ?? '';

    if (str_starts_with($image, 'http://localhost:8000/')) {
        $image = str_replace(
            'http://localhost:8000/',
            'http://192.168.37.247:8000/',
            $image
        );
    }

    $event['image'] = $image;

    return view('events.show', compact('event'));
})->name('events.show');

Route::get('/event/futsal/pesan-tiket', function () {
    return view('events.event-tiket');
})->name('events.pesan-tiket');


Route::get('/event/futsal/pesan-tiket', function (Request $request) {
    $id = $request->query('id');

    if (!$id) {
        return redirect('/events')
            ->with('error', 'Silakan pilih event terlebih dahulu.');
    }

    $response = Http::timeout(10)
        ->get('http://192.168.37.247:8000/api/events');

    if (!$response->successful()) {
        abort(502, 'Server API event tidak dapat diakses.');
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
        abort(502, 'Data event dari API tidak valid.');
    }

    $event = collect($payload['data'])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $id
    );

    if (!$event) {
        abort(404, 'Event tidak ditemukan.');
    }

    $event['image'] = $event['image_url'] ?? $event['image'] ?? '';

    if (str_starts_with($event['image'], 'http://localhost:8000/')) {
        $event['image'] = str_replace(
            'http://localhost:8000/',
            'http://192.168.37.247:8000/',
            $event['image']
        );
    }

    return view('events.event-tiket', compact('event'));
})->name('events.pesan-tiket');


/*
|--------------------------------------------------------------------------
| FORM PEMESANAN TIKET WARGA
|--------------------------------------------------------------------------
*/

// Tampilkan form berdasarkan ID event dari API.
Route::get('/event/futsal/pesan-tiket', function (Request $request) {
    $id = $request->query('id');

    if (!$id) {
        return redirect()->route('events.index')
            ->with('error', 'Silakan pilih event terlebih dahulu.');
    }

    $response = Http::timeout(10)
        ->get('http://192.168.37.247:8000/api/events');

    if (!$response->successful()) {
        abort(502, 'API event tidak dapat diakses.');
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
        abort(502, 'Format data event tidak valid.');
    }

    $event = collect($payload['data'])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $id
    );

    if (!$event) {
        abort(404, 'Event tidak ditemukan.');
    }

    $event['image'] = $event['image_url'] ?? $event['image'] ?? '';

    if (str_starts_with($event['image'], 'http://localhost:8000/')) {
        $event['image'] = str_replace(
            'http://localhost:8000/',
            'http://192.168.37.247:8000/',
            $event['image']
        );
    }

    return view('events.event-tiket', compact('event'));
})->name('events.pesan-tiket');


// Simpan data pemesan warga.
Route::post('/event/futsal/pesan-tiket', function (Request $request) {
    $data = $request->validate([
        'event_id' => ['required', 'string', 'max:100'],
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $response = Http::timeout(10)
        ->get('http://192.168.37.247:8000/api/events');

    if (!$response->successful()) {
        return back()->withInput()
            ->with('error', 'API event tidak dapat diakses.');
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    $event = collect($payload['data'] ?? [])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $data['event_id']
    );

    if (!$event) {
        return back()->withInput()
            ->with('error', 'Event tidak ditemukan.');
    }

    if ((int) ($event['remaining'] ?? 0) <= 0) {
        return back()->withInput()
            ->with('error', 'Maaf, tiket event ini sudah habis.');
    }

    $schedule = $event['schedules'][0] ?? [];

    $image = $event['image_url'] ?? $event['image'] ?? '';

    if (str_starts_with($image, 'http://localhost:8000/')) {
        $image = str_replace(
            'http://localhost:8000/',
            'http://192.168.37.247:8000/',
            $image
        );
    }

    $request->session()->put('detail_pemesanan', [
        'event_id' => $event['id'],
        'event_title' => $event['title'] ?? 'Event',
        'event_description' => $event['description'] ?? '',
        'event_image' => $image,
        'event_date' => $schedule['date'] ?? 'Jadwal belum tersedia',
        'event_time' => $schedule['start_time'] ?? 'Waktu belum tersedia',
        'event_location' => $event['location'] ?? 'GOR A. Yani, Kota Mojokerto',
        'nama_lengkap' => $data['nama_lengkap'],
        'nik' => $data['nik'],
        'email' => $data['email'],
        'nomor_hp' => $data['nomor_hp'],
        'subtotal' => 0,
        'biaya_layanan' => 0,
        'total_pembayaran' => 0,
    ]);

    return redirect()->route('events.detail-warga');
})->name('events.pesan-tiket.simpan');


// Halaman detail pemesanan warga.
Route::get('/event/futsal/detail-pemesanan-warga', function () {
    abort_unless(session()->has('detail_pemesanan'), 403);

    return view('events.detail-pemesanan-warga');
})->name('events.detail-warga');


/*
|--------------------------------------------------------------------------
| FORM PEMESANAN TIKET LUAR KOTA
|--------------------------------------------------------------------------
*/

Route::get('/events/{id}/pesan-tiket-luar', function ($id) {
    $response = Http::timeout(10)
        ->get('http://192.168.37.247:8000/api/events');

    if (!$response->successful()) {
        abort(502, 'API event tidak dapat diakses.');
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    if (!is_array($payload) || !is_array($payload['data'] ?? null)) {
        abort(502, 'Format data event tidak valid.');
    }

    $event = collect($payload['data'])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $id
    );

    if (!$event) {
        abort(404, 'Event tidak ditemukan.');
    }

    if ((int) ($event['remaining'] ?? 0) <= 0) {
        abort(410, 'Tiket event sudah habis.');
    }

    $image = $event['image_url'] ?? $event['image'] ?? '';

    $event['image'] = str_replace(
        [
            'http://localhost:8000/',
            'http://127.0.0.1:8000/',
        ],
        'http://192.168.37.247:8000/',
        $image
    );

   return view('events.event-tiket-luar', compact('event'));
})->name('events.pesan-tiket-luar');


// FORM WARGA LUAR KOTA

 // FORM WARGA LUAR KOTA
Route::post('/event/futsal/pesan-tiket-luar', function (Request $request) {

    $data = $request->validate([
        'event_id' => ['required', 'string', 'max:100'],
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'alamat_ktp' => ['required', 'string', 'max:1000'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $response = Http::timeout(10)->get(
        'http://192.168.37.247:8000/api/events'
    );

    if (!$response->successful()) {
        return back()->withInput()->with(
            'error',
            'API event tidak dapat diakses.'
        );
    }

    $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->body());
    $payload = json_decode($body, true);

    $event = collect($payload['data'] ?? [])->first(
        fn ($item) => (string) ($item['id'] ?? '') === (string) $data['event_id']
    );

    if (!$event) {
        return back()->withInput()->with(
            'error',
            'Event tidak ditemukan.'
        );
    }

    if ((int) ($event['remaining'] ?? 0) <= 0) {
        return back()->withInput()->with(
            'error',
            'Tiket event sudah habis.'
        );
    }

    $schedule = $event['schedules'][0] ?? [];

    $image = $event['image_url'] ?? $event['image'] ?? '';

    $image = str_replace(
        'http://localhost:8000/',
        'http://192.168.37.247:8000/',
        $image
    );

    // Ambil harga numerik dari data event API.
$subtotal = (float) ($pemesan['subtotal'] ?? $subtotal ?? 0);

$biayaLayanan = (float) ($pemesan['biaya_layanan'] ?? $biayaLayanan ?? 0);

$total = $subtotal + $biayaLayanan;
       
$request->session()->put('detail_pemesanan', [
    'event_id' => $event['id'],
    'event_title' => $event['title'] ?? 'Event',
    'event_description' => $event['description'] ?? '',
    'event_image' => $image,
    'event_date' => $schedule['date'] ?? 'Jadwal belum tersedia',
    'event_time' => $schedule['start_time'] ?? 'Waktu belum tersedia',
    'event_location' => $event['location'] ?? 'GOR A. Yani, Kota Mojokerto',

    // Simpan harga numerik dari database/API.
    'event_price' => $event['price'] ?? 'Harga belum tersedia',
    'event_price_amount' => $harga,

    'nama_lengkap' => $data['nama_lengkap'],
    'nik' => $data['nik'],
    'alamat_ktp' => $data['alamat_ktp'],
    'email' => $data['email'],
    'nomor_hp' => $data['nomor_hp'],

    'subtotal' => $harga,
    'biaya_layanan' => $biayaLayanan,
    'total_pembayaran' => $total,
    'jenis_pemesan' => 'luar-kota',
]);

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
            ->get('http://192.168.37.247:8000/api/events', [
                'q' => $search,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'API event mengembalikan HTTP ' . $response->status()
            );
        }

      $body = $response->body();

// Hapus UTF-8 BOM jika ada di awal respons.
$body = preg_replace('/^\xEF\xBB\xBF/', '', $body);

$payload = json_decode($body, true);

if (!is_array($payload)) {
    throw new \RuntimeException(
        'Respons API bukan JSON yang valid. HTTP: '
        . $response->status()
        . '. Isi respons: '
        . substr($response->body(), 0, 1000)
    );
}

if (($payload['success'] ?? null) !== true) {
    throw new \RuntimeException(
        'Nilai success tidak sesuai. Isi respons: '
        . substr(
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            0,
            1000
        )
    );
}

if (!isset($payload['data']) || !is_array($payload['data'])) {
    throw new \RuntimeException(
        'Properti data tidak ada atau bukan array.'
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

   $subtotal = (float) ($detail['subtotal'] ?? $detail['event_price_amount'] ?? 0);

$biayaLayanan = (float) ($detail['biaya_layanan'] ?? 1000);

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

Route::post('/pembayaran/midtrans', function (Request $request) {

    $bookingId = session('booking_id');

    if (!$bookingId) {
        return back()->with(
            'error',
            'Booking belum tersedia.'
        );
    }

    $response = Http::withToken(session('token'))
        ->post(
            url("/api/bookings/{$bookingId}/payment")
        );

    if (!$response->successful()) {
        return back()->with(
            'error',
            'Gagal membuat transaksi.'
        );
    }

    $snapToken = data_get(
        $response->json(),
        'data.snap_token'
    );

    session([
        'snap_token' => $snapToken,
    ]);

    return back();

})->name('pembayaran.midtrans');

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