@extends('layouts.app')

@section('content')

@include('components.navbar')

@php
    $title = $event['title'] ?? 'Detail Event';
    $image = $event['image'] ?? $event['image_url'] ?? '';
    $category = $event['category'] ?? 'Event';
    $description = $event['description'] ?? 'Belum ada deskripsi event.';

    $remaining = max(0, (int) ($event['remaining'] ?? 0));
    $total = max(0, (int) ($event['total'] ?? 0));

    $schedules = $event['schedules'] ?? [];
    $schedule = $schedules[0] ?? [];

    $date = $schedule['date'] ?? $event['date'] ?? null;
    $startTime = $schedule['start_time'] ?? null;
    $endTime = $schedule['end_time'] ?? null;
    $time = $event['time'] ?? null;
    $location = $event['location'] ?? 'GOR A. Yani, Kota Mojokerto';
    $price = $event['price'] ?? 'Harga belum tersedia';

    if ($date) {
        $date = \Illuminate\Support\Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F Y');
    } else {
        $date = 'Jadwal belum tersedia';
    }

    if ($startTime) {
        $time = substr($startTime, 0, 5);

        if ($endTime) {
            $time .= '–' . substr($endTime, 0, 5);
        }

        $time .= ' WIB';
    } else {
        $time = $time ?: 'Waktu belum tersedia';
    }

    if ($image && str_starts_with($image, 'http://localhost:8000/')) {
        $image = str_replace(
            'http://localhost:8000/',
            'http://192.168.37.247:8000/',
            $image
        );
    } elseif ($image
        && !str_starts_with($image, 'http://')
        && !str_starts_with($image, 'https://')) {
        $image = asset($image);
    }
@endphp

<section class="detail-event-section">
    <div class="detail-event-container">

        {{-- Tombol Kembali --}}
        <a href="{{ url('/events') }}" class="back-event">
            ← Kembali ke Daftar Event
        </a>

        <div class="detail-event-card">

            {{-- Gambar Event --}}
            <div class="detail-event-image">
                <img
                    src="{{ $image ?: asset('images/events/futsal.jpg') }}"
                    alt="Poster {{ $title }}"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='{{ asset('images/events/futsal.jpg') }}';"
                >

                <span class="event-category">
                    {{ $category }}
                </span>
            </div>

            {{-- Informasi Event --}}
            <div class="detail-event-info">

                <span class="events-eyebrow">SI-GORAY EVENT</span>

                <h1>{{ $title }}</h1>

                <p class="detail-description">
                    {{ $description }}
                </p>

                {{-- Informasi Event --}}
                <div class="detail-event-list">

                    <div class="detail-item">
                        <span aria-hidden="true">📅</span>
                        <div>
                            <strong>Tanggal Event</strong>
                            <p>{{ $date }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span aria-hidden="true">🕒</span>
                        <div>
                            <strong>Waktu</strong>
                            <p>{{ $time }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span aria-hidden="true">📍</span>
                        <div>
                            <strong>Lokasi</strong>
                            <p>{{ $location }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span aria-hidden="true">🎟️</span>
                        <div>
                            <strong>Sisa Tiket</strong>
                            <p>
                                {{ $remaining }} dari {{ $total }} tiket
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Harga dan Pemesanan --}}
                <div class="detail-event-price">
                    <div>
                        <span>Harga Tiket</span>
                        <h2>{{ $price }}</h2>
                    </div>

                    @if ($remaining > 0)
                        <a
                            href="{{ route('events.show', ['id' => $event['id'] ?? '']) }}"
                            class="detail-book-button"
                        >
                            Pesan Tiket Sekarang
                        </a>
                    @else
                        <span class="detail-book-button disabled"
                              aria-disabled="true">
                            Tiket Habis
                        </span>
                    @endif
                </div>

            </div>
        </div>
    </div>
</section>

@include('components.footer')

@endsection
