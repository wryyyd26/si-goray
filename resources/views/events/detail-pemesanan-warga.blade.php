@extends('layouts.app')

@section('content')
@include('components.navbar')

@php
    $detail = session('detail_pemesanan', []);

    $title = $detail['event_title'] ?? 'Detail Event';
    $description = $detail['event_description'] ?? 'Informasi event';
    $image = $detail['event_image'] ?? '';
    $date = $detail['event_date'] ?? 'Jadwal belum tersedia';
    $time = $detail['event_time'] ?? 'Waktu belum tersedia';
    $location = $detail['event_location'] ?? 'GOR A. Yani, Kota Mojokerto';
    $price = (float) ($detail['subtotal'] ?? 0);
@endphp

<main class="booking-page">
    <div class="booking-container">

        <a href="{{ url('/events') }}" class="booking-back">
            ← Kembali ke Daftar Event
        </a>

        <div class="booking-detail-card">

            <div class="booking-detail-header">
                <span class="material-symbols-rounded">check-circle</span>
                <h1>Detail Pemesanan Tiket</h1>
                <p>Warga Kota Mojokerto</p>
            </div>

            {{-- Informasi Event --}}
            <div class="booking-detail-event">
                @if ($image)
                    <img
                        src="{{ $image }}"
                        alt="Poster {{ $title }}"
                        onerror="this.onerror=null; this.style.display='none';"
                    >
                @endif

                <div>
                    <h2>{{ $title }}</h2>
                    <p>{{ $description }}</p>
                    <p>{{ $date }} · {{ $time }}</p>
                    <p>{{ $location }}</p>
                </div>
            </div>

            {{-- Data Pemesan --}}
            <div class="booking-detail-section">
                <h3>Data Pemesan</h3>

                <div class="booking-detail-row">
                    <span>Nama Lengkap</span>
                    <strong>{{ $detail['nama_lengkap'] ?? '-' }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>NIK</span>
                    <strong>{{ $detail['nik'] ?? '-' }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>Email</span>
                    <strong>{{ $detail['email'] ?? '-' }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>Nomor HP</span>
                    <strong>{{ $detail['nomor_hp'] ?? '-' }}</strong>
                </div>
            </div>

            {{-- Total Harga --}}
            <div class="booking-detail-total">
                <span>Total Harga Tiket</span>
                <strong>
                    {{ $price > 0 ? 'Rp ' . number_format($price, 0, ',', '.') : 'GRATIS' }}
                </strong>
            </div>

            <p class="booking-detail-note">
                Periksa kembali data pemesan sebelum melanjutkan.
            </p>

            <a
                href="{{ route('pembayaran.show') }}"
                class="booking-submit booking-detail-button"
            >
                Lanjut ke Pembayaran →
            </a>

        </div>
    </div>
</main>

@include('components.footer')
@endsection
