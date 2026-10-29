@extends('layouts.app')

@section('content')
    @include('components.navbar')

    @php
        $eventId = $event['id'] ?? '';
        $namaEvent = $event['title'] ?? 'Event';
        $deskripsiEvent = $event['description'] ?? 'Belum ada deskripsi event.';
        $gambarEvent = $event['image'] ?? $event['image_url'] ?? '';
        $kategoriEvent = $event['category'] ?? 'Event';
        $jadwal = $event['schedules'][0] ?? [];

        $tanggalEvent = $jadwal['date'] ?? null;
        $waktuEvent = $jadwal['start_time'] ?? null;

        $hargaEvent = $event['price'] ?? null;

        if ($hargaEvent === null || $hargaEvent === '') {
            $hargaAmount = (float) ($event['price_amount'] ?? 0);

            $hargaEvent = $hargaAmount > 0
                ? 'Rp ' . number_format($hargaAmount, 0, ',', '.')
                : 'Harga belum tersedia';
        }

        if ($gambarEvent && !str_starts_with($gambarEvent, 'http')) {
            $gambarEvent = 'http://192.168.37.247:8000/'
                . ltrim($gambarEvent, '/');
        }
    @endphp

    <main class="booking-page">
        <div class="booking-container">

            {{-- HEADER FORM --}}
            <div class="booking-header">
                <a
                    href="{{ route('events.show', ['id' => $eventId]) }}"
                    class="booking-back"
                >
                    ← Kembali ke Detail Event
                </a>

                <h1>Form Pembelian Tiket</h1>
                <p>Lengkapi data diri sesuai identitas KTP.</p>
            </div>

            <div class="booking-content">

                {{-- INFORMASI EVENT --}}
                <section class="booking-event-info">
                    <img
                        src="{{ $gambarEvent ?: asset('images/events/futsal.jpg') }}"
                        alt="Poster {{ $namaEvent }}"
                        onerror="this.onerror=null;this.src='{{ asset('images/events/futsal.jpg') }}';"
                    >

                    <div class="booking-event-text">
                        <h2>{{ $namaEvent }}</h2>
                        <p>{{ $deskripsiEvent }}</p>

                        <p>
                            <span class="material-symbols-rounded">category</span>
                            {{ $kategoriEvent }}
                        </p>

                        <p>
                            <span class="material-symbols-rounded">calendar_month</span>
                            @if ($tanggalEvent)
                                {{ \Illuminate\Support\Carbon::parse($tanggalEvent)->translatedFormat('l, d F Y') }}
                            @else
                                Jadwal belum tersedia
                            @endif
                        </p>

                        <p>
                            <span class="material-symbols-rounded">schedule</span>
                            {{ $waktuEvent ? substr($waktuEvent, 0, 5) . ' WIB' : 'Waktu belum tersedia' }}
                        </p>

                        <p>
                            <span class="material-symbols-rounded">location_on</span>
                            {{ $event['location'] ?? 'GOR A. Yani, Kota Mojokerto' }}
                        </p>

                        <div class="booking-price">
                            <span>Tiket Warga Luar Kota Mojokerto</span>
                            <strong>{{ $hargaEvent }}</strong>
                        </div>
                    </div>
                </section>

                {{-- FORM PEMESANAN --}}
                <section class="booking-form-section">
                    <h2>Form Pemesanan Tiket</h2>
                    <p>Isi data sesuai identitas diri yang valid.</p>

                    {{-- PESAN ERROR --}}
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

                    <form
                        action="{{ route('events.pesan-tiket-luar.simpan') }}"
                        method="POST"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="event_id"
                            value="{{ $eventId }}"
                        >

                        {{-- NAMA LENGKAP --}}
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                value="{{ old('nama_lengkap') }}"
                                placeholder="Nama lengkap sesuai KTP"
                                autocomplete="name"
                                maxlength="255"
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
                                placeholder="Masukkan 16 digit NIK"
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
                                placeholder="Masukkan alamat sesuai KTP"
                                rows="3"
                                maxlength="1000"
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
                                placeholder="nama@email.com"
                                autocomplete="email"
                                maxlength="255"
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
                                placeholder="Contoh: 081234567890"
                                autocomplete="tel"
                                maxlength="20"
                                required
                            >
                        </div>

                        {{-- TOMBOL PESAN --}}
                        <button type="submit" class="booking-submit">
                            Pesan Tiket
                        </button>
                    </form>
                </section>

            </div>
        </div>
    </main>

    @include('components.footer')
@endsection
