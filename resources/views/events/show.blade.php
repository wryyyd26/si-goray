@extends('layouts.app')

@section('content')

@include('components.navbar')

@php
    $title = $event['title'] ?? 'Detail Event';
    $description = $event['description'] ?? 'Belum ada deskripsi event.';
    $image = $event['image'] ?? $event['image_url'] ?? '';
    $category = $event['category'] ?? 'Event';
    $remaining = max(0, (int) ($event['remaining'] ?? 0));
    $total = max(0, (int) ($event['total'] ?? 0));
    $price = $event['price'] ?? 'Gratis';

    $schedules = $event['schedules'] ?? [];
    $schedule = $schedules[0] ?? [];

    $date = $schedule['date'] ?? null;
    $startTime = $schedule['start_time'] ?? null;
    $endTime = $schedule['end_time'] ?? null;

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

    $dateLabel = $date
        ? \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y')
        : 'Jadwal belum tersedia';

    $timeLabel = $startTime
        ? substr($startTime, 0, 5) . ' WIB'
        : 'Waktu belum tersedia';

    if ($startTime && $endTime) {
        $timeLabel = substr($startTime, 0, 5)
            . '–' . substr($endTime, 0, 5) . ' WIB';
    }

    $used = max(0, $total - $remaining);
    $progress = $total > 0 ? min(100, ($used / $total) * 100) : 0;
@endphp

<main class="event-detail-page">

    {{-- DETAIL EVENT --}}
    <section class="event-detail">

        {{-- POSTER EVENT --}}
        <div class="event-main-image">
            <img
                src="{{ $image ?: asset('images/events/futsal.jpg') }}"
                alt="Poster {{ $title }}"
                onerror="this.onerror=null; this.src='{{ asset('images/events/futsal.jpg') }}';"
            >
        </div>

        {{-- PANEL TIKET --}}
        <aside class="event-ticket-panel">

            <div class="ticket-summary">

                <span class="events-eyebrow">{{ $category }}</span>

                <h2>{{ $title }}</h2>

                <p>{{ $description }}</p>

                <ul class="event-meta">
                    <li>
                        <span class="material-symbols-rounded">calendar_month</span>
                        <span>{{ $dateLabel }}</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">schedule</span>
                        <span>{{ $timeLabel }}</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">location_on</span>
                        <span>GOR A. Yani, Kota Mojokerto</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">confirmation_number</span>
                        <span>Kuota tersisa: {{ $remaining }} dari {{ $total }} tiket</span>
                    </li>
                </ul>

                <div
                    class="ticket-quota"
                    role="progressbar"
                    aria-label="Kuota tiket terpakai"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ (int) round($progress) }}"
                >
                    <span style="width: {{ $progress }}%"></span>
                </div>

                {{-- HARGA TIKET --}}
                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="citizen-button active"
                        data-category="warga"
                        aria-pressed="true"
                    >
                        Gratis
                    </button>

                    <span>NIK Kota Mojokerto</span>
                </div>

                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="non-citizen-button"
                        data-category="luar"
                        aria-pressed="false"
                    >
                        {{ $price }}
                    </button>

                    <span>NIK Luar Kota Mojokerto</span>
                </div>

                <small>
                    Harga tiket dapat berbeda sesuai kategori pengguna.
                </small>

            </div>

            {{-- PEMILIHAN KATEGORI --}}
            <div class="ticket-bottom">

                <img
                    src="{{ asset('storage/images/Logo-Si-goray.png') }}"
                    alt="Logo SI-GORAY"
                    class="ticket-brand-logo"
                >

                <strong>PESAN TIKET EVENT</strong>

                @if ($remaining > 0)
                    <a
                        href="{{ route('events.pesan-tiket', ['id' => $event['id'] ?? '']) }}"
                        class="ticket-category-button active"
                        id="ticket-warga"
                    >
                        Warga Kota Mojokerto
                    </a>

                    <a
                        href="{{ route('events.pesan-tiket-luar', ['id' => $event['id'] ?? '']) }}"
                        class="ticket-category-button"
                        id="ticket-luar"
                    >
                        Luar Warga Kota Mojokerto
                    </a>
                @else
                    <span class="ticket-category-button disabled">
                        Tiket Habis
                    </span>
                @endif

                <small>Pilih sesuai NIK wilayah</small>

            </div>

        </aside>

    </section>

    {{-- DESKRIPSI EVENT --}}
    <section class="event-description">

        <h2>Deskripsi Event</h2>

        <p>{{ $description }}</p>

    </section>

</main>

{{-- JAVASCRIPT PEMILIHAN KATEGORI --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryButtons = document.querySelectorAll(
        '.event-detail-page [data-category]'
    );

    const wargaLink = document.getElementById('ticket-warga');
    const luarLink = document.getElementById('ticket-luar');

    categoryButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const selectedCategory = this.dataset.category;

            categoryButtons.forEach(function (item) {
                const isActive = item.dataset.category === selectedCategory;

                item.classList.toggle('active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });

            if (wargaLink && luarLink) {
                wargaLink.classList.toggle('active', selectedCategory === 'warga');
                luarLink.classList.toggle('active', selectedCategory === 'luar');
            }
        });
    });
});
</script>

@include('components.footer')

@endsection