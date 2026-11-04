
@extends('layouts.app')

@section('content')
@include('components.navbar')

@php
    $detail = session('detail_pemesanan', []);
    $subtotal = (float) ($detail['subtotal'] ?? 0);
    $biayaLayanan = (float) ($detail['biaya_layanan'] ?? 1000);
    $total = (float) ($detail['total_pembayaran'] ?? ($subtotal + $biayaLayanan));
@endphp

<main class="booking-page">
    <div class="booking-container">

        <a href="{{ url()->previous() }}" class="booking-back">
            ← Kembali
        </a>

        <div class="booking-detail-card">

            <div class="booking-detail-header">
                <span class="material-symbols-rounded">check-circle</span>
                <h1>Detail Pemesanan Tiket</h1>
                <p>Warga Luar Kota Mojokerto</p>
            </div>

            {{-- Informasi event dari API --}}
            <div class="booking-detail-event">

                @if (!empty($detail['event_image']))
                    <img
                        src="{{ $detail['event_image'] }}"
                        alt="{{ $detail['event_title'] ?? 'Poster event' }}">
                @endif

                <div>
                    <h2>{{ $detail['event_title'] ?? 'Event' }}</h2>

                    <p>
                        {{ $detail['event_description'] ?? '' }}
                    </p>

                    <p>
                        {{ $detail['event_date'] ?? 'Jadwal belum tersedia' }}
                        ·
                        {{ $detail['event_time'] ?? 'Waktu belum tersedia' }}
                    </p>

                    <p>
                        {{ $detail['event_location'] ?? 'Lokasi belum tersedia' }}
                    </p>
                </div>

            </div>

            {{-- Data pemesan --}}
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
                    <span>Alamat sesuai KTP</span>
                    <strong>{{ $detail['alamat_ktp'] ?? '-' }}</strong>
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

            {{-- Rincian pembayaran --}}
            <div class="booking-detail-section">
                <h3>Rincian Pembayaran</h3>

                <div class="booking-detail-row">
                    <span>Harga Tiket</span>
                    <strong>
                        Rp{{ number_format($subtotal, 0, ',', '.') }}
                    </strong>
                </div>

                <div class="booking-detail-row">
                    <span>Biaya Layanan</span>
                    <strong>
                        Rp{{ number_format($biayaLayanan, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="booking-detail-total">
                <span>Total Pembayaran</span>
                <strong>
                    Rp{{ number_format($total, 0, ',', '.') }}
                </strong>
            </div>

            <p class="booking-detail-note">
                Periksa kembali data pemesan sebelum melanjutkan ke pembayaran.
            </p>

            <a href="{{ route('pembayaran.luar-kota') }}"
               class="booking-submit booking-detail-button">
                Lanjut ke Pembayaran →
            </a>

        </div>
    </div>
</main>

@include('components.footer')
@endsection
