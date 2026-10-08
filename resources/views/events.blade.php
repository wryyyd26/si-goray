
@extends('layouts.app')

@section('content')
    @include('components.navbar')

    <main class="events-page">
        <div class="events-page-container">

            <!-- Header Event -->
            <div class="events-page-heading">
                <div>
                    <span class="events-eyebrow">SI-GORAY EVENT</span>
                    <h1>Jelajahi Semua Event</h1>
                    <p>
                        Temukan dan ikuti berbagai event menarik
                        sesuai minat dan olahraga favoritmu.
                    </p>
                </div>
            </div>

            <!-- Pencarian -->
            <form action="{{ url('/events') }}" method="GET" class="events-search">
                <span class="events-search-icon">⌕</span>

                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari nama event..."
                    aria-label="Cari nama event"
                >

                <input
                    type="hidden"
                    name="category"
                    value="{{ $selectedCategory }}"
                >

                <button type="submit">Cari Event</button>
            </form>

            <!-- Filter Kategori -->
            <div class="events-filter-heading">
                <h2>Kategori Event</h2>
                <span>{{ $events->count() }} event ditemukan</span>
            </div>

            <div class="events-page-filters">
                @foreach (['Semua', 'Sport', 'Seni', 'Hiburan'] as $category)
                    <a
                        href="{{ url('/events') }}?category={{ urlencode($category) }}&q={{ urlencode($search) }}"
                        class="events-filter {{ $selectedCategory === $category ? 'active' : '' }}"
                    >
                        {{ $category }}
                    </a>
                @endforeach
            </div>

            <!-- Daftar Event -->
            <div class="event-grid">
                @forelse ($events as $event)
                    <div class="event-card">

                        <div class="event-image">
                            <img
                                src="{{ asset($event['image']) }}"
                                alt="{{ $event['title'] }}"
                            >

                            <span class="event-category">
                                {{ $event['category'] }}
                            </span>
                        </div>

                        <div class="event-info">
                            <h3>{{ $event['title'] }}</h3>

                            <p class="event-description">
                                {{ $event['description'] }}
                            </p>

                            <div class="event-quota">
                                <span class="quota-icon">▣</span>
                                <span>
                                    Kuota Event: Sisa {{ $event['remaining'] }}
                                    dari {{ $event['total'] }} Ticket
                                </span>
                            </div>

                            <div class="quota-progress">
                                <div
                                    class="quota-progress-bar"
                                    style="width: {{ (($event['total'] - $event['remaining']) / $event['total']) * 100 }}%"
                                ></div>
                            </div>

                            <div class="event-price">
                                <h3>{{ $event['price'] }}</h3>
                                <span>S&K</span>
                            </div>
                        </div>

                        <div class="event-action">
                            <a href="#" class="book-ticket">
                                <span class="ticket-icon">🎟</span>
                                Pesan Tiket
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="events-empty">
                        <h3>Event tidak ditemukan</h3>
                        <p>
                            Coba gunakan kata kunci atau kategori
                            yang berbeda.
                        </p>
                        <a href="{{ url('/events') }}">Lihat Semua Event</a>
                    </div>
                @endforelse
            </div>

        </div>
    </main>
@endsection