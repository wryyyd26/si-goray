@extends('layouts.app')

@section('content')
@include('components.navbar')

@php
    $title = $event['title'] ?? 'Detail Event';
    $description = $event['description'] ?? '';
    $image = $event['image'] ?? $event['image_url'] ?? '';
    $remaining = max(0, (int) ($event['remaining'] ?? 0));
    $total = max(0, (int) ($event['total'] ?? 0));

    $schedules = $event['schedules'] ?? [];
    $schedule = $schedules[0] ?? [];

    $date = $schedule['date'] ?? null;
    $startTime = $schedule['start_time'] ?? null;
    $endTime = $schedule['end_time'] ?? null;

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
        $time = 'Waktu belum tersedia';
    }

    if (str_starts_with($image, 'http://localhost:8000/')) {
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

<main class="booking-page">
    <div class="booking-container">

        {{-- Header --}}
        <div class="booking-header">
            <a
    href="{{ request('id')
        ? route('events.show', ['id' => request('id')])
        : url('/events') }}"
    class="booking-back"
>
    ← Kembali ke Detail Event
</a>

            <h1>Form Pembelian Tiket</h1>
            <p>Lengkapi data diri untuk memesan tiket {{ $title }}.</p>
        </div>

        <div class="booking-content">

            {{-- Informasi Event --}}
            <section class="booking-event-info">
                <img
                    src="{{ $image ?: asset('images/events/futsal.jpg') }}"
                    alt="Poster {{ $title }}"
                    onerror="this.onerror=null; this.src='{{ asset('images/events/futsal.jpg') }}';"
                >

                <div class="booking-event-text">
                    <h2>{{ $title }}</h2>
                    <p>{{ $description ?: 'Informasi event' }}</p>
                    <p>📅 {{ $date }}</p>
                    <p>🕒 {{ $time }}</p>
                    <p>📍 {{ $event['location'] ?? 'GOR A. Yani, Kota Mojokerto' }}</p>
                    <p>🎟️ Sisa {{ $remaining }} dari {{ $total }} tiket</p>

                    <div class="booking-price">
                        Tiket Warga Kota Mojokerto
                        <strong>GRATIS</strong>
                    </div>
                </div>
            </section>

            {{-- Form Pemesan --}}
            <section class="booking-form-section">
                <h2>Data Pemesan</h2>
                <p>Pastikan data yang kamu masukkan benar.</p>

                @if (session('error'))
                    <div class="booking-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="booking-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($remaining > 0)
                    <form
                        action="{{ route('events.pesan-tiket.simpan') }}"
                        method="POST"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="event_id"
                            value="{{ $event['id'] ?? '' }}"
                        >

                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                value="{{ old('nama_lengkap') }}"
                                placeholder="Masukkan nama sesuai identitas"
                                autocomplete="name"
                                maxlength="255"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="nik">NIK</label>
                            <input
                                type="text"
                                id="nik"
                                name="nik"
                                value="{{ old('nik') }}"
                                placeholder="Masukkan 16 digit NIK"
                                inputmode="numeric"
                                minlength="16"
                                maxlength="16"
                                pattern="[0-9]{16}"
                                required
                            >
                            <small>NIK harus terdiri dari 16 digit.</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="contoh@email.com"
                                autocomplete="email"
                                maxlength="255"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="nomor_hp">Nomor HP</label>
                            <input
                                type="tel"
                                id="nomor_hp"
                                name="nomor_hp"
                                value="{{ old('nomor_hp') }}"
                                placeholder="Contoh: 081234567890"
                                autocomplete="tel"
                                maxlength="20"
                                required
                            >
                        </div>

                        <button type="submit" class="booking-submit">
                            Lanjutkan Pemesanan
                        </button>
                    </form>
                @else
                    <div class="booking-error">
                        Maaf, tiket untuk event ini sudah habis atau
                        kuota belum tersedia.
                    </div>
                @endif
            </section>

        </div>
    </div>
</main>

@include('components.footer')
@endsection