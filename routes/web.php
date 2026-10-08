<?php

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