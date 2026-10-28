@extends('layouts.app')

@section('content')
@include('components.navbar')

<main class="booking-page">
    <div class="booking-container">
        <div class="booking-header">
            <a href="{{ route('events.show') }}" class="booking-back">
                ← Kembali ke Detail Event
            </a>

            <h1>Form Pembelian Tiket</h1>
            <p>Lengkapi data diri untuk memesan tiket Futsal.</p>
        </div>

        <div class="booking-content">
            <section class="booking-event-info">
                <img
                    src="{{ asset('storage/images/events/futsal.jpg') }}"
                    alt="Poster Event Futsal"
                >

                <div class="booking-event-text">
                    <h2>Futsal</h2>
                    <p>Pertandingan Antar Sekolah</p>
                    <p>📅 Kamis, 27 Agustus 2026</p>
                    <p>🕒 15.00 WIB</p>
                    <p>📍 GOR A. Yani</p>
                    <div class="booking-price">
                        Tiket Warga Kota Mojokerto
                        <strong>GRATIS</strong>
                    </div>
                </div>
            </section>

            <section class="booking-form-section">
                <h2>Data Pemesan</h2>
                <p>Pastikan data yang kamu masukkan benar.</p>

                @if ($errors->any())
                    <div class="booking-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="#" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input
                            type="text"
                            id="nama_lengkap"
                            name="nama_lengkap"
                            value="{{ old('nama_lengkap') }}"
                            placeholder="Masukkan nama sesuai identitas"
                            autocomplete="name"
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
                            required
                        >
                    </div>

                    <button type="submit" class="booking-submit">
                        Lanjutkan Pemesanan
                    </button>
                </form>
            </section>
        </div>
    </div>
</main>
@include('components.footer')
@endsection