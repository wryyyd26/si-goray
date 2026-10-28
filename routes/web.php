<?php
use App\Http\Controllers\Admin\RegularSlotController;
use App\Http\Controllers\Admin\EventController as AdminEventController;

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
    
});

Route::get('/events', function () {
    $events = [
        [
            'title' => 'Futsal',
            'description' => 'Pertandingan Antar Sekolah',
            'category' => 'Sport',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 24,
            'total' => 200,
            'price' => 'Gratis',
        ],
        [
            'title' => 'Kompetisi Badminton',
            'description' => 'Turnamen Badminton Terbuka',
            'category' => 'Sport',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 40,
            'total' => 150,
            'price' => 'Rp25.000',
        ],
        [
            'title' => 'Festival Musik',
            'description' => 'Pentas Musik dan Kreativitas',
            'category' => 'Hiburan',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 75,
            'total' => 300,
            'price' => 'Rp50.000',
        ],
        [
            'title' => 'Pentas Seni',
            'description' => 'Pertunjukan Seni dan Budaya',
            'category' => 'Seni',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 60,
            'total' => 200,
            'price' => 'Gratis',
        ],
        [
            'title' => 'Basket 3x3',
            'description' => 'Kompetisi Basket Antar Tim',
            'category' => 'Sport',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 18,
            'total' => 100,
            'price' => 'Rp20.000',
        ],
        [
            'title' => 'Festival Dance',
            'description' => 'Kompetisi Tari Modern',
            'category' => 'Seni',
            'image' => 'images/events/futsal.jpg',
            'remaining' => 90,
            'total' => 250,
            'price' => 'Rp15.000',
        ],
    ];

    $category = request('category', 'Semua');
    $search = request('q', '');

    $filteredEvents = collect($events)->filter(function ($event) use ($category, $search) {
        $matchCategory = $category === 'Semua'
            || $event['category'] === $category;

        $matchSearch = $search === ''
            || stripos($event['title'], $search) !== false
            || stripos($event['description'], $search) !== false;

        return $matchCategory && $matchSearch;
    });

    return view('events', [
        'events' => $filteredEvents,
        'selectedCategory' => $category,
        'search' => $search,
    ]);
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    return view('contact');
});

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