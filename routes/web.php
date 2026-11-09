<?php

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\RegularSlotController;
use App\Http\Controllers\Admin\ArGuideController;
use App\Http\Controllers\ArGuidePublicController;
use App\Http\Controllers\Api\EventController as ApiEventController;
use App\Http\Controllers\Web\EventPageController;

// PENGUNJUNG WEB AUTH (OTP existing, Laravel session; tidak mengubah UI)
Route::prefix('pengunjung/web-auth')->name('pengunjung.web-auth.')->group(function () {
    Route::post('/request-otp', [\App\Http\Controllers\Web\PengunjungWebAuthController::class, 'requestOtp'])->name('request-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Web\PengunjungWebAuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::get('/status', [\App\Http\Controllers\Web\PengunjungWebAuthController::class, 'status'])->name('status');
    Route::post('/logout', [\App\Http\Controllers\Web\PengunjungWebAuthController::class, 'logout'])->name('logout');
});

// Penghubung sesi login pengunjung ke IdentityService dan BookingService.
// Semua route tetap dilindungi CSRF oleh middleware web.
\Illuminate\Support\Facades\Route::middleware('auth:web')
    ->prefix('pengunjung/web-booking')
    ->name('pengunjung.web-booking.')
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/identities',
            [\App\Http\Controllers\Web\PengunjungWebBookingController::class, 'identities'])
            ->name('identities');
        \Illuminate\Support\Facades\Route::post('/identities',
            [\App\Http\Controllers\Web\PengunjungWebBookingController::class, 'registerIdentity'])
            ->name('identities.store');
        \Illuminate\Support\Facades\Route::post('/bookings',
            [\App\Http\Controllers\Web\PengunjungWebBookingController::class, 'createBooking'])
            ->name('bookings.store');
    });


// Halaman login pengunjung fungsional; tidak menyentuh UI event milik Daffa.
Route::get('/pengunjung/login', function (Request $request) {
    $next = (string) $request->query('next', '/events');
    $allowedPaths = [
        '/events',
        '/events/detail',
        '/event/futsal/pesan-tiket',
        '/event/futsal/pesan-tiket-luar',
        '/activity-reguler',
        '/',
        '/about',
        '/contact',
    ];

    // Hanya izinkan tujuan internal yang dikenal; cegah open redirect.
    $path = parse_url($next, PHP_URL_PATH);
    if (!str_starts_with($next, '/')
        || str_starts_with($next, '//')
        || str_contains($next, '\\')
        || preg_match('/[\x00-\x1F\x7F]/', $next)
        || !is_string($path)
        || !in_array($path, $allowedPaths, true)) {
        $next = '/events';
    }

    if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
        return redirect($next);
    }

    return view('pengunjung.login', ['next' => $next]);
})->name('pengunjung.login');


/* Frontend pengunjung SI-GORAY */
Route::get('/', function (Request $request) {
    $events = collect();

    try {
        $response = app(ApiEventController::class)->index($request);
        $payload = $response->getData(true);
        if ($response->getStatusCode() === 200 && ($payload['success'] ?? false) === true) {
            $events = collect($payload['data'] ?? [])->take(3)->map(function (array $event) {
                return (object) [
                    'id' => $event['id'] ?? '',
                    'nama_event' => $event['title'] ?? 'Event',
                    'gambar' => $event['image_url'] ?? $event['image'] ?? '',
                    'deskripsi' => $event['description'] ?? '',
                    'category' => $event['category'] ?? 'Event',
                    'remaining' => (int) ($event['remaining'] ?? 0),
                    'total' => (int) ($event['total'] ?? 0),
                    'price' => $event['price'] ?? 'Harga belum tersedia',
                ];
            });
        }
    } catch (\Throwable $e) {
        report($e);
    }
    return view('home', compact('events'));
})->name('home');

Route::view('/activity-reguler', 'activity-reguler')->name('activity.reguler');

// Harus di atas /events/{id}; gunakan query ?event=UUID milik backend.
Route::get('/events/detail', [EventPageController::class, 'show'])->name('events.show');
Route::get('/events/{id}', function (string $id) {
    abort_unless(\Illuminate\Support\Str::isUuid($id), 404);
    return redirect()->route('events.show', ['event' => $id]);
})->whereUuid('id')->name('events.show.legacy');

Route::get('/event/futsal/pesan-tiket', [EventPageController::class, 'formWarga'])->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)->name('events.pesan-tiket');
Route::get('/event/futsal/pesan-tiket-luar', [EventPageController::class, 'formLuar'])->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)->name('events.pesan-tiket-luar');
Route::get('/events/{id}/pesan-tiket-luar', function (string $id) {
    abort_unless(\Illuminate\Support\Str::isUuid($id), 404);
    return redirect()->route('events.pesan-tiket-luar', ['event' => $id]);
})->whereUuid('id')->name('events.pesan-tiket-luar.legacy');

// Catatan: POST berikut baru mengisi session; booking database harus lewat BookingService.
// Simpan data pemesan warga.
Route::post('/event/futsal/pesan-tiket', function (Request $request) {
    $data = $request->validate([
        'event_id' => ['required', 'uuid'],
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $response = app(\App\Http\Controllers\Api\EventController::class)->index($request);

    if ($response->getStatusCode() !== 200) {
        return back()->withInput()
            ->with('error', 'API event tidak dapat diakses.');
    }

    $payload = $response->getData(true);

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
            url('/') . '/',
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
        'tanggal_lahir' => $data['tanggal_lahir'],
        'email' => $data['email'],
        'nomor_hp' => $data['nomor_hp'],
        'subtotal' => 0,
        'biaya_layanan' => 0,
        'total_pembayaran' => 0,
    ]);

    return redirect()->route('events.detail-warga');
})->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)->name('events.pesan-tiket.simpan');


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

// FORM WARGA LUAR KOTA
Route::post('/event/futsal/pesan-tiket-luar', function (Request $request) {

    $data = $request->validate([
        'event_id' => ['required', 'uuid'],
        'nama_lengkap' => ['required', 'string', 'max:255'],
        'nik' => ['required', 'digits:16'],
        'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
        'alamat_ktp' => ['required', 'string', 'max:1000'],
        'email' => ['required', 'email', 'max:255'],
        'nomor_hp' => ['required', 'string', 'max:20'],
    ]);

    $response = app(\App\Http\Controllers\Api\EventController::class)->index($request);

    if ($response->getStatusCode() !== 200) {
        return back()->withInput()->with(
            'error',
            'API event tidak dapat diakses.'
        );
    }

    $payload = $response->getData(true);

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
        url('/') . '/',
        $image
    );

    // Ambil harga numerik dari data event API.
// Ambil harga numerik dari API.
$harga = (float) ($event['price_amount'] ?? 0);

// Biaya layanan.
$biayaLayanan = 1000;

// Hitung total pembayaran.
$total = $harga + $biayaLayanan;

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

})->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)->name('events.pesan-tiket-luar.simpan');


Route::get('/event/futsal/detail-pemesanan-luar', function () {
    abort_unless(session()->has('detail_pemesanan'), 403);

    return view('events.detail-pemesanan-luar');
})->name('events.detail-luar');




Route::get('/events', function (Request $request) {
    $search = trim((string) $request->query('q', ''));
    $category = trim((string) $request->query('category', 'Semua'));

    try {
        $response = app(ApiEventController::class)->index($request);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Gagal mengambil data event.');
        }
        $payload = $response->getData(true);
        if (($payload['success'] ?? null) !== true || !is_array($payload['data'] ?? null)) {
            throw new \RuntimeException('Format data event tidak sesuai.');
        }
        $events = collect($payload['data'])->map(function (array $event) {
            return [
                'id' => $event['id'] ?? '',
                'title' => $event['title'] ?? 'Event',
                'description' => $event['description'] ?? '',
                'image' => $event['image_url'] ?? $event['image'] ?? 'images/events/futsal.jpg',
                'category' => $event['category'] ?? 'Event',
                'remaining' => (int) ($event['remaining'] ?? 0),
                'total' => (int) ($event['total'] ?? 0),
                'price' => $event['price'] ?? null,
                'price_amount' => $event['price_amount'] ?? null,
            ];
        });
        if ($search !== '') {
            $events = $events->filter(fn (array $event) =>
                str_contains(mb_strtolower($event['title']), mb_strtolower($search))
            );
        }
        if ($category !== '' && $category !== 'Semua') {
            $events = $events->filter(fn (array $event) => $event['category'] === $category);
        }
        return view('events', [
            'events' => $events->values(),
            'selectedCategory' => $category ?: 'Semua',
            'search' => $search,
        ]);
    } catch (\Throwable $e) {
        report($e);
        return response('Daftar event sementara tidak tersedia.', 503);
    }
})->name('events.index');

Route::get('/about', function () {
    return view('about');
})->name('about');
Route::post('/contact', [\App\Http\Controllers\Web\ContactMessageController::class, 'store'])->middleware('throttle:5,1')->name('contact.submit');


Route::post('/contact', [\App\Http\Controllers\Web\ContactMessageController::class, 'store'])->middleware('throttle:5,1')->name('contact.submit');

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

// Panduan publik SI-GORAY
Route::prefix('panduan-ar')
    ->name('guides.')
    ->group(function () {
        Route::get('/', [ArGuidePublicController::class, 'index'])
            ->name('index');

        Route::get('/{slug}', [ArGuidePublicController::class, 'show'])
            ->name('show');
    });


/*
|--------------------------------------------------------------------------
| Admin SI-GORAY
|--------------------------------------------------------------------------
*/

// Login Admin menggunakan sesi terpisah dari OTP pengunjung/petugas.
Route::get('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLogin'])
    ->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureSiGorayAdmin::class)->group(function () {

    Route::post('/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', [
    \App\Http\Controllers\Admin\AdminDashboardController::class,
    'index',
])->name('dashboard');

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

    // Generate enam sesi 2 jam (08:00–20:00) untuk bulan yang dipilih.
    Route::post('/reguler/generate-bulanan', [
        \App\Http\Controllers\Admin\MonthlyRegularSlotController::class,
        'store',
    ])->name('reguler.generate-bulanan');

        // Atur kapasitas tribun dan kuota 6 sesi per tanggal.
    Route::post('/reguler/kuota-harian', [
        \App\Http\Controllers\Admin\DailyRegularQuotaController::class,
        'store',
    ])->name('reguler.kuota-harian.store');

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

    // Tarif reguler: sudah berada dalam prefix "admin" dan name "admin.".
    // Catatan: otorisasi Admin perlu diterapkan setelah mekanisme login Admin siap.
    Route::put('/reguler/tarif', [\App\Http\Controllers\Admin\RegularTariffController::class, 'update'])
        ->name('reguler.tarif.update');



    /*
    |--------------------------------------------------------------------------
    | Booking
    |--------------------------------------------------------------------------
    | Sementara menunggu integrasi controller booking Admin.
    */

    Route::get('/booking', [
    \App\Http\Controllers\Admin\BookingManagementController::class,
    'index',
])->name('booking.pemesanan');

Route::get('/booking/{booking}', [
    \App\Http\Controllers\Admin\BookingManagementController::class,
    'show',
])->whereUuid('booking')->name('booking.show');


    /*
    |--------------------------------------------------------------------------
    | Pembayaran
    |--------------------------------------------------------------------------
    | Sementara menunggu integrasi controller pembayaran Admin.
    */


Route::get('/payments', [
    \App\Http\Controllers\Admin\PaymentManagementController::class,
    'index',
])->name('payment.pembayaran');

Route::get('/payments/{payment}', [
    \App\Http\Controllers\Admin\PaymentManagementController::class,
    'show',
])->whereUuid('payment')->name('payment.show');



    /*
    |--------------------------------------------------------------------------
    | Pengguna
    |--------------------------------------------------------------------------
    */


Route::get('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('user.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'show'])->whereUuid('user')->name('user.detail');



    /*
    |--------------------------------------------------------------------------
    | Saran dan Masukan
    |--------------------------------------------------------------------------
    */

Route::get(
    '/saran-masukan',
    [\App\Http\Controllers\Admin\AdminSaranMasukanController::class, 'index']
)->name('saran-masukan.index');

Route::get(
    '/saran-masukan/{saranMasukan}',
    [\App\Http\Controllers\Admin\AdminSaranMasukanController::class, 'detail']
)->name('saran-masukan.detail');

Route::patch(
    '/saran-masukan/{saranMasukan}/status',
    [\App\Http\Controllers\Admin\AdminSaranMasukanController::class, 'updateStatus']
)->name('saran-masukan.update-status');

Route::delete(
    '/saran-masukan/{saranMasukan}',
    [\App\Http\Controllers\Admin\AdminSaranMasukanController::class, 'destroy']
)->name('saran-masukan.destroy');


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

   Route::get('/reports', [
    \App\Http\Controllers\Admin\AdminReportController::class,
    'index'
])->name('report.index');


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

// Adapter fungsional form Daffa -> identitas -> BookingService (status tetap DRAFT).
Route::post('/pengunjung/form-booking/warga', [\App\Http\Controllers\Web\EventFormBookingController::class, 'storeWarga'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->name('events.booking.submit.warga');
Route::post('/pengunjung/form-booking/luar', [\App\Http\Controllers\Web\EventFormBookingController::class, 'storeLuar'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->name('events.booking.submit.luar');
Route::get('/pengunjung/booking/{booking}/draft', [\App\Http\Controllers\Web\EventFormBookingController::class, 'draft'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('events.booking.draft');
// SI-GORAY: bridge form booking reguler (session web).
Route::post('/pengunjung/form-booking/reguler', [\App\Http\Controllers\Web\RegularFormBookingController::class, 'store'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->name('reguler.booking.store');
Route::get('/pengunjung/booking-reguler/{booking}', [\App\Http\Controllers\Web\RegularFormBookingController::class, 'draft'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->name('reguler.booking.draft');

// SI-GORAY: Konfirmasi gratis booking REGULER (session web, CSRF, kepemilikan di controller+service).
Route::post('/pengunjung/booking-reguler/{booking}/confirm-free', [\App\Http\Controllers\Web\RegularFreeConfirmationController::class, 'store'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')
    ->name('reguler.booking.confirm-free');

// SI-GORAY: gambar QR reguler aman, dibaca dari QR_TICKETS (bukan generate tiket baru).
Route::get('/pengunjung/booking-reguler/{booking}/qr.svg', [\App\Http\Controllers\Web\RegularQrImageController::class, 'show'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('reguler.booking.qr-image');


// SI-GORAY: checkout Midtrans untuk sesi web pengunjung (bukan API Sanctum).
Route::get('/pengunjung/booking/{booking}/payment', [\App\Http\Controllers\Web\WebBookingPaymentController::class, 'show'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('booking.payment.show');
Route::post('/pengunjung/booking/{booking}/payment', [\App\Http\Controllers\Web\WebBookingPaymentController::class, 'start'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('booking.payment.start');
Route::post('/pengunjung/booking/{booking}/confirm-event-free', [\App\Http\Controllers\Web\EventFreeConfirmationController::class, 'store'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('events.booking.confirm-free');
Route::get('/pengunjung/booking/{booking}/qr.svg', [\App\Http\Controllers\Web\EventQrImageController::class, 'show'])
    ->middleware(\App\Http\Middleware\EnsureVisitorBookingLogin::class)
    ->whereUuid('booking')->name('events.booking.qr-image');


// Rekonsiliasi server-side pembayaran dari sesi web pengunjung (CSRF + throttling).
Route::post('/pengunjung/booking/{booking}/payment/sync',
    [\App\Http\Controllers\Web\WebPaymentReconciliationController::class, 'sync'])
    ->middleware([\App\Http\Middleware\EnsureVisitorBookingLogin::class, 'throttle:3,1'])
    ->whereUuid('booking')->name('booking.payment.sync');
