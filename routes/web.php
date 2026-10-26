<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('home');
    
});

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

