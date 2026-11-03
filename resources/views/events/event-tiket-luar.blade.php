@extends('layouts.app')

@section('content')
@include('components.navbar')

<main class="booking-page">
    <div class="booking-container">

        {{-- HEADER FORM --}}
        <div class="booking-header">
            <a href="{{ route('events.show') }}" class="booking-back">
                ← Kembali ke Detail Event
            </a>

            <h1>Form Pembelian Tiket</h1>
            <p>Lengkapi data diri untuk memesan tiket Futsal.</p>
        </div>

        <div class="booking-content">

            {{-- INFORMASI EVENT --}}
            <section class="booking-event-info">
                <img
                    src="{{ asset('storage/images/events/futsal.jpg') }}"
                    alt="Poster Kompetisi Olahraga Antar Sekolah"
                >

                <div class="booking-event-text">
                    <h2>Futsal</h2>
                    <p>Pertandingan Antar Sekolah</p>

                    <p>
                        <span class="material-symbols-rounded">calendar_month</span>
                        Kamis, 27 Agustus 2026
                    </p>

                    <p>
                        <span class="material-symbols-rounded">schedule</span>
                        15.00 WIB
                    </p>

                    <p>
                        <span class="material-symbols-rounded">location_on</span>
                        GOR A. Yani
                    </p>

                    <div class="booking-price">
                        <span>Tiket Warga Luar Kota Mojokerto</span>
                        <strong>Rp25.000</strong>
                    </div>
                </div>
            </section>

            {{-- FORM PEMESANAN --}}
            <section class="booking-form-section">
                <h2>Form Pemesanan Tiket</h2>
                <p>Lengkapi data diri sesuai identitas KTP.</p>

                {{-- PESAN VALIDASI --}}
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

                    {{-- NAMA LENGKAP --}}
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input
                            type="text"
                            id="nama_lengkap"
                            name="nama_lengkap"
                            value="{{ old('nama_lengkap') }}"
                            placeholder="Nama Lengkap"
                            autocomplete="name"
                            required
                        >
                    </div>

                    {{-- NIK --}}
                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input
                            type="text"
                            id="nik"
                            name="nik"
                            value="{{ old('nik') }}"
                            placeholder="NIK (KTP)"
                            inputmode="numeric"
                            minlength="16"
                            maxlength="16"
                            pattern="[0-9]{16}"
                            title="NIK harus terdiri dari 16 digit angka."
                            required
                        >
                    </div>

                    {{-- ALAMAT KTP --}}
                    <div class="form-group">
                        <label for="alamat_ktp">Alamat sesuai KTP</label>
                        <textarea
                            id="alamat_ktp"
                            name="alamat_ktp"
                            placeholder="Alamat sesuai KTP"
                            rows="3"
                            required
                        >{{ old('alamat_ktp') }}</textarea>
                    </div>

                    {{-- EMAIL --}}
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Email"
                            autocomplete="email"
                            required
                        >
                    </div>

                    {{-- NOMOR HP --}}
                    <div class="form-group">
                        <label for="nomor_hp">Nomor HP</label>
                        <input
                            type="tel"
                            id="nomor_hp"
                            name="nomor_hp"
                            value="{{ old('nomor_hp') }}"
                            placeholder="Nomor Handphone"
                            autocomplete="tel"
                            required
                        >
                    </div>

                    {{-- TOMBOL PESAN --}}
                    <button type="submit" class="booking-submit">
                        Pesan Ticket
                    </button>
                </form>
            </section>

        </div>
    </div>
</main>

@include('components.footer')
@endsection
