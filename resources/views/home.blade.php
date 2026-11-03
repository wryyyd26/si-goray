
@extends('layouts.app')

@section('content')

@include('components.navbar')

{{-- =====================================================
     HERO SECTION
===================================================== --}}
<section class="hero-section">
    <div class="hero-container">

        <div class="hero-content">
            <h1>
                Beli Tiket Event Jadi Lebih Mudah & Praktis
                di GOR Seni Majapahit Kota Mojokerto
            </h1>

            <p>
                Temukan berbagai event olahraga, seni, dan budaya,
                serta nikmati reservasi GOR yang cepat dan aman.
            </p>

            <div class="hero-buttons">
                <a href="{{ route('events.index') }}"
                    class="btn btn-primary">
                    Cari Event Sekarang
                </a>

                {{-- Tombol menuju halaman Activity Reguler --}}
                <a href="{{ route('activity.reguler') }}"
                    class="btn btn-outline">
                    Activity Reguler
                </a>
            </div>
        </div>

        <div class="hero-image">
            <img
                src="{{ asset('storage/images/Vector-Gor.png') }}"
                alt="Ilustrasi GOR Seni Majapahit Kota Mojokerto">
        </div>

    </div>
</section>


{{-- =====================================================
     DAFTAR EVENT DARI API
===================================================== --}}
<section class="event-section">
    <div class="event-container">

        <div class="event-heading">
            <h2>Jelajahi Event Menarik</h2>
        </div>

        {{-- Filter kategori --}}
        <div class="event-filters">
            <a href="{{ route('events.index') }}"
                class="filter-button active">
                Semua
            </a>

            <a href="{{ route('events.index', ['category' => 'Sport']) }}"
                class="filter-button">
                Sport
            </a>

            <a href="{{ route('events.index', ['category' => 'Seni']) }}"
                class="filter-button">
                Seni
            </a>

            <a href="{{ route('events.index', ['category' => 'Hiburan']) }}"
                class="filter-button">
                Hiburan
            </a>
        </div>

        {{-- Kartu event --}}
        <div class="event-grid">

            @forelse (($events ?? collect()) as $event)

                @php
                    $total = max(0, (int) ($event->total ?? 0));

                    $remaining = max(
                        0,
                        min((int) ($event->remaining ?? 0), $total)
                    );

                    $usedPercentage = $total > 0
                        ? (($total - $remaining) / $total) * 100
                        : 0;
                @endphp

                <article class="event-card">

                    {{-- Gambar dan kategori --}}
                    <div class="event-image">
                        @if (!empty($event->gambar))
                            <img
                                src="{{ $event->gambar }}"
                                alt="{{ $event->nama_event ?? 'Event' }}"
                                loading="lazy">
                        @else
                            <img
                                src="{{ asset('images/events/futsal.jpg') }}"
                                alt="Gambar event">
                        @endif

                        <span class="event-category">
                            {{ $event->category ?? 'Event' }}
                        </span>
                    </div>

                    {{-- Informasi event --}}
                    <div class="event-info">
                        <h3>
                            {{ $event->nama_event ?? 'Event' }}
                        </h3>

                        <p class="event-description">
                            {{ $event->deskripsi ?? 'Belum ada deskripsi event.' }}
                        </p>

                        {{-- Kuota tiket --}}
                        <div class="event-quota">
                            <span class="quota-icon">▣</span>

                            <span>
                                Kuota Event: Sisa {{ $remaining }}
                                dari {{ $total }} tiket
                            </span>
                        </div>

                        {{-- Progress kuota --}}
                        <div class="quota-progress">
                            <div
                                class="quota-progress-bar"
                                style="width: {{ $usedPercentage }}%">
                            </div>
                        </div>

                        {{-- Harga tiket --}}
                        <div class="event-price">
                            <h3>
                                {{ $event->price ?? 'Harga belum tersedia' }}
                            </h3>

                            <span>S&K</span>
                        </div>
                    </div>

                    {{-- Tombol lihat event --}}
                    <div class="event-action">
                        <a
                            href="{{ route('events.index') }}"
                            class="book-ticket">
                            <span class="ticket-icon">🎟</span>
                            Lihat Event
                        </a>
                    </div>

                </article>

            @empty
                <div class="event-empty">
                    <p>Belum ada event yang tersedia saat ini.</p>
                </div>
            @endforelse

        </div>

        {{-- Tombol menuju seluruh event --}}
        @if(isset($events) && $events->isNotEmpty())
            <div class="event-more">
                <a href="{{ route('events.index') }}" class="btn btn-primary">
                    Lihat Semua Event
                </a>
            </div>
        @endif

    </div>
</section>


@include('components.footer')

@endsection
