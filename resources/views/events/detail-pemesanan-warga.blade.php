@extends('layouts.app')

@section('content')
@include('components.navbar')

<main class="booking-page">
    <div class="booking-container">
        <a href="{{ url()->previous() }}" class="booking-back">
            ← Kembali
        </a>

        <div class="booking-detail-card">
            <div class="booking-detail-header">
                <span class="material-symbols-rounded">check-circle</span>
                <h1>Detail Pemesanan Tiket</h1>
                <p>Warga Kota Mojokerto</p>
            </div>

            <div class="booking-detail-event">
                <img
                    src="{{ asset('storage/images/events/futsal.jpg') }}"
                    alt="Poster Futsal">
                <div>
                    <h2>Futsal</h2>
                    <p>Pertandingan Antar Sekolah</p>
                    <p>Kamis, 27 Agustus 2026 · 15.00 WIB</p>
                    <p>GOR A. Yani</p>
                </div>
            </div>

            <div class="booking-detail-section">
                <h3>Data Pemesan</h3>

                <div class="booking-detail-row">
                    <span>Nama Lengkap</span>
                    <strong>{{ session('detail_pemesanan.nama_lengkap', '-') }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>NIK</span>
                    <strong>{{ session('detail_pemesanan.nik', '-') }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>Email</span>
                    <strong>{{ session('detail_pemesanan.email', '-') }}</strong>
                </div>

                <div class="booking-detail-row">
                    <span>Nomor HP</span>
                    <strong>{{ session('detail_pemesanan.nomor_hp', '-') }}</strong>
                </div>
            </div>

            <div class="booking-detail-total">
                <span>Total Harga Tiket</span>
                <strong>GRATIS</strong>
            </div>

            <p class="booking-detail-note">
                Periksa kembali data pemesan sebelum melanjutkan.
            </p>


            <a href="{{ route('pembayaran.show') }}"
                class="booking-submit booking-detail-button">
                Lanjut ke Pembayaran →
            </a>

        </div>
    </div>
</main>

@include('components.footer')
@endsection