
@extends('layouts.app')

@section('content')
    @include('components.navbar')

    @php
        $daftarEvent = collect($events ?? []);
        $kataKunci = $search ?? request('q', '');
        $kategoriAktif = $selectedCategory ?? request('category', 'Semua');

        // Kategori bawaan dan kategori yang diterima dari API.
        $kategoriTersedia = collect(['Semua', 'Sport', 'Seni', 'Hiburan', 'Event'])
            ->merge(
                $daftarEvent
                    ->pluck('category')
                    ->filter()
                    ->map(fn ($kategori) => trim($kategori))
            )
            ->unique()
            ->values();
    @endphp

    <main class="events-page">
        <div class="events-page-container">

            {{-- Header --}}
            <section class="events-page-heading">
                <div>
                    <span class="events-eyebrow">SI-GORAY EVENT</span>
                    <h1>Jelajahi Semua Event</h1>
                    <p>
                        Temukan dan ikuti berbagai event menarik
                        sesuai minat dan olahraga favoritmu.
                    </p>
                </div>
            </section>

            {{-- Pencarian --}}
            <form
                action="{{ route('events.index') }}"
                method="GET"
                class="events-search"
            >
                <span class="events-search-icon" aria-hidden="true">⌕</span>

                <input
                    type="search"
                    name="q"
                    value="{{ $kataKunci }}"
                    placeholder="Cari nama event..."
                    aria-label="Cari nama event"
                >

                <input
                    type="hidden"
                    name="category"
                    value="{{ $kategoriAktif }}"
                >

                <button type="submit">Cari Event</button>
            </form>

            {{-- Filter Kategori --}}
            <section class="events-filter-section">
                <div class="events-filter-heading">
                    <h2>Kategori Event</h2>
                    <span>{{ $daftarEvent->count() }} event ditemukan</span>
                </div>

                <div class="events-page-filters">
                    @foreach ($kategoriTersedia as $kategori)
                        <a
                            href="{{ route('events.index', [
                                'category' => $kategori,
                                'q' => $kataKunci,
                            ]) }}"
                            class="events-filter {{ $kategoriAktif === $kategori ? 'active' : '' }}"
                            @if ($kategoriAktif === $kategori)
                                aria-current="page"
                            @endif
                        >
                            {{ $kategori }}
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Daftar Event --}}
            <section class="events-list-section">
                <div class="event-grid">
                    @forelse ($daftarEvent as $event)
                        @php
                            $id = $event['id'] ?? null;
                            $title = $event['title'] ?? 'Event';
                            $description = $event['description'] ?? '';
                            $image = $event['image'] ?? $event['image_url'] ?? '';
                            $category = $event['category'] ?? 'Event';

                            $remaining = max(
                                0,
                                (int) ($event['remaining'] ?? 0)
                            );

                            $total = max(
                                0,
                                (int) ($event['total'] ?? 0)
                            );

                            $used = max(0, $total - $remaining);

                            $progress = $total > 0
                                ? min(100, ($used / $total) * 100)
                                : 0;

                            // Gunakan harga tampilan dari API.
                            // price_amount merupakan nilai numerik jika dibutuhkan.
                            $price = $event['price'] ?? null;

                            if ($price === null || $price === '') {
                                $priceAmount = $event['price_amount'] ?? null;

                                $price = $priceAmount !== null
                                    ? (
                                        (float) $priceAmount <= 0
                                            ? 'Gratis'
                                            : 'Rp ' . number_format(
                                                (float) $priceAmount,
                                                0,
                                                ',',
                                                '.'
                                            )
                                    )
                                    : 'Harga belum tersedia';
                            }

                            // Ubah alamat gambar dari localhost ke IP backend.
                            if (is_string($image) && $image !== '') {
                                $image = str_replace(
                                    [
                                        'http://localhost:8000/',
                                        'http://127.0.0.1:8000/',
                                    ],
                                    'http://192.168.37.247:8000/',
                                    $image
                                );

                                // Untuk gambar relatif, gunakan URL backend.
                                if (
                                    !str_starts_with($image, 'http://') &&
                                    !str_starts_with($image, 'https://') &&
                                    !str_starts_with($image, '/')
                                ) {
                                    $image = 'http://192.168.37.247:8000/'
                                        . ltrim($image, '/');
                                }
                            }
                        @endphp

                        <article class="event-card">
                            {{-- Gambar Event --}}
                            <div class="event-image">
                                @if ($image)
                                    <img
                                        src="{{ $image }}"
                                        alt="Gambar {{ $title }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ asset('images/events/futsal.jpg') }}';"
                                    >
                                @else
                                    <img
                                        src="{{ asset('images/events/futsal.jpg') }}"
                                        alt="Gambar {{ $title }}"
                                        loading="lazy"
                                    >
                                @endif

                                <span class="event-category">
                                    {{ $category }}
                                </span>
                            </div>

                            {{-- Informasi Event --}}
                            <div class="event-info">
                                <h3>{{ $title }}</h3>

                                <p class="event-description">
                                    {{ $description ?: 'Belum ada deskripsi event.' }}
                                </p>

                                <div class="event-quota">
                                    <span
                                        class="quota-icon"
                                        aria-hidden="true"
                                    >▣</span>

                                    <span>
                                        Kuota tersisa: {{ $remaining }}
                                        dari {{ $total }} tiket
                                    </span>
                                </div>

                                <div
                                    class="quota-progress"
                                    role="progressbar"
                                    aria-label="Persentase kuota tiket terpakai"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="{{ (int) round($progress) }}"
                                >
                                    <div
                                        class="quota-progress-bar"
                                        style="width: {{ $progress }}%"
                                    ></div>
                                </div>

                                <div class="event-price">
                                    <h3>{{ $price }}</h3>
                                    <span>Harga tiket</span>
                                </div>
                            </div>

                            {{-- Tombol Pemesanan --}}
                            <div class="event-action">
                                @if ($remaining > 0 && $id)
                                    <a
                                        href="{{ route('events.show', ['id' => $id]) }}"
                                        class="book-ticket"
                                    >
                                        <span
                                            class="ticket-icon"
                                            aria-hidden="true"
                                        >🎟</span>
                                        Pesan Tiket
                                    </a>
                                @else
                                    <span
                                        class="book-ticket disabled"
                                        aria-disabled="true"
                                    >
                                        {{ $remaining > 0 ? 'Event tidak tersedia' : 'Tiket Habis' }}
                                    </span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="events-empty">
                            <h3>Event tidak ditemukan</h3>
                            <p>
                                Coba gunakan kata kunci atau kategori
                                yang berbeda.
                            </p>

                            <a href="{{ route('events.index') }}">
                                Lihat Semua Event
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>

        </div>
    </main>

    @include('components.footer')
@endsection
