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

Route::get('/', function () {
    return view('home');
})->name('home');


Route::view('/events/detail', 'events.show')
    ->name('events.show');

Route::get('/events', function (Request $request) {


    $search = trim((string) $request->query('q', ''));

    $query = Event::query()
        ->with([
            'schedules' => function ($query) {
                $query->whereDate('tanggal', '>=', today())
                    ->orderBy('tanggal');
            },
        ])
        ->where('status', 'AKTIF');

    if ($search !== '') {
        $query->where(function ($query) use ($search) {
            $query->where(
                'nama_event',
                'like',
                "%{$search}%"
            )->orWhere(
                'deskripsi',
                'like',
                "%{$search}%"
            );
        });
    }

    $events = $query
        ->orderBy('nama_event')
        ->get()
        ->map(function ($event) {

            $total = $event->schedules->sum('kapasitas');

            $used = $event->schedules->sum(
                'kuota_terpakai'
            );

            return [
                'id' => $event->id,
                'title' => $event->nama_event,
                'description' => $event->deskripsi,
                'image' => $event->gambar
                    ?: 'images/events/futsal.jpg',
                'category' => 'Event',
                'remaining' => max(0, $total - $used),
                'total' => $total,
                'price' => 'Tarif sesuai ketentuan',
            ];
        });

    return view('events', [
        'events' => $events,
        'selectedCategory' => 'Semua',
        'search' => $search,
    ]);

})->name('events.index');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');


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
