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
                di GOR A. Yani Kota Mojokerto
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

                <a href="#activity"
                    class="btn btn-outline"
                    id="btn-activity">
                    Activity Reguler
                </a>
            </div>
        </div>

        <div class="hero-image">
            <img
                src="{{ asset('storage/images/vektor-gor.png') }}"
                alt="Ilustrasi GOR A. Yani Kota Mojokerto">
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
        {{-- Tombol hanya tampil jika ada event --}}
@if(isset($events) && $events->isNotEmpty())
    <div class="event-more">
        <a href="{{ route('events.index') }}" class="btn btn-primary">
            Lihat Semua Event
        </a>
    </div>
@endif

    </div>
</section>


{{-- =====================================================
     MODAL PILIHAN VALIDASI NIK
===================================================== --}}
<div class="modal-overlay" id="modal-nik">
    <div class="modal-box">

        <button
            type="button"
            class="modal-close"
            id="close-nik"
            aria-label="Tutup">
            &times;
        </button>

        <h3>Aktivasi NIK</h3>

        <p>
            Apakah Anda termasuk warga Kota Mojokerto?
        </p>

        <div class="modal-buttons">
            <button
                type="button"
                class="btn-yes"
                id="btn-yes">
                Iya
            </button>

            <button
                type="button"
                class="btn-no"
                id="btn-no">
                Tidak
            </button>
        </div>

    </div>
</div>


{{-- =====================================================
     FORM RESERVASI WARGA KOTA MOJOKERTO
===================================================== --}}
<div class="modal-overlay" id="modal-warga">
    <div class="modal-box form-box">

        <button
            type="button"
            class="modal-close"
            id="close-warga"
            aria-label="Tutup">
            &times;
        </button>

        <h3>Reservasi Warga Kota Mojokerto</h3>

        <p>Silakan isi data kegiatan Anda.</p>

        <form>
            <div class="form-group">
                <label for="nama_kegiatan_warga">
                    Nama Kegiatan
                </label>

                <input
                    type="text"
                    id="nama_kegiatan_warga"
                    name="nama_kegiatan"
                    placeholder="Contoh: Latihan Futsal"
                    required>
            </div>

            <div class="form-group">
                <label for="tanggal_warga">
                    Hari & Tanggal
                </label>

                <input
                    type="date"
                    id="tanggal_warga"
                    name="tanggal"
                    required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="waktu_mulai_warga">
                        Dari Jam
                    </label>

                    <input
                        type="time"
                        id="waktu_mulai_warga"
                        name="waktu_mulai"
                        required>
                </div>

                <div class="form-group">
                    <label for="waktu_selesai_warga">
                        Sampai Jam
                    </label>

                    <input
                        type="time"
                        id="waktu_selesai_warga"
                        name="waktu_selesai"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label for="nik_warga">NIK</label>

                <input
                    type="text"
                    id="nik_warga"
                    name="nik"
                    maxlength="16"
                    inputmode="numeric"
                    placeholder="Masukkan NIK"
                    required>
            </div>

            <div class="form-group">
                <label for="nama_warga">Nama Lengkap</label>

                <input
                    type="text"
                    id="nama_warga"
                    name="nama"
                    placeholder="Masukkan nama lengkap"
                    required>
            </div>

            <div class="form-group">
                <label for="alamat_warga">Alamat</label>

                <textarea
                    id="alamat_warga"
                    name="alamat"
                    placeholder="Masukkan alamat"
                    required></textarea>
            </div>

            <div class="modal-buttons">
                <button
                    type="button"
                    class="btn-no"
                    id="back-warga">
                    Kembali
                </button>

                <button
                    type="button"
                    class="btn-yes">
                    Lanjutkan
                </button>
            </div>
        </form>
    </div>
</div>


{{-- =====================================================
     FORM RESERVASI NON-WARGA KOTA MOJOKERTO
===================================================== --}}
<div class="modal-overlay" id="modal-non-warga">
    <div class="modal-box form-box">

        <button
            type="button"
            class="modal-close"
            id="close-non-warga"
            aria-label="Tutup">
            &times;
        </button>

        <h3>Reservasi Non-Warga</h3>

        <p>Silakan isi data kegiatan Anda.</p>

        <form>
            <div class="form-group">
                <label for="nama_kegiatan_non_warga">
                    Nama Kegiatan
                </label>

                <input
                    type="text"
                    id="nama_kegiatan_non_warga"
                    name="nama_kegiatan"
                    placeholder="Contoh: Turnamen Futsal"
                    required>
            </div>

            <div class="form-group">
                <label for="tanggal_non_warga">
                    Hari & Tanggal
                </label>

                <input
                    type="date"
                    id="tanggal_non_warga"
                    name="tanggal"
                    required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="waktu_mulai_non_warga">
                        Dari Jam
                    </label>

                    <input
                        type="time"
                        id="waktu_mulai_non_warga"
                        name="waktu_mulai"
                        required>
                </div>

                <div class="form-group">
                    <label for="waktu_selesai_non_warga">
                        Sampai Jam
                    </label>

                    <input
                        type="time"
                        id="waktu_selesai_non_warga"
                        name="waktu_selesai"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label for="nik_non_warga">NIK</label>

                <input
                    type="text"
                    id="nik_non_warga"
                    name="nik"
                    maxlength="16"
                    inputmode="numeric"
                    placeholder="Masukkan NIK"
                    required>
            </div>

            <div class="form-group">
                <label for="nama_non_warga">Nama Lengkap</label>

                <input
                    type="text"
                    id="nama_non_warga"
                    name="nama"
                    placeholder="Masukkan nama lengkap"
                    required>
            </div>

            <div class="form-group">
                <label for="asal">Kota/Kabupaten Asal</label>

                <input
                    type="text"
                    id="asal"
                    name="asal"
                    placeholder="Contoh: Kabupaten Mojokerto"
                    required>
            </div>

            <div class="modal-buttons">
                <button
                    type="button"
                    class="btn-no"
                    id="back-non-warga">
                    Kembali
                </button>

                <button
                    type="button"
                    class="btn-yes">
                    Lanjutkan
                </button>
            </div>
        </form>
    </div>
</div>


@include('components.footer')

{{-- =====================================================
     JAVASCRIPT MODAL
===================================================== --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const btnActivity = document.getElementById('btn-activity');

        const modalNik = document.getElementById('modal-nik');
        const modalWarga = document.getElementById('modal-warga');
        const modalNonWarga = document.getElementById('modal-non-warga');

        const btnYes = document.getElementById('btn-yes');
        const btnNo = document.getElementById('btn-no');

        const backWarga = document.getElementById('back-warga');
        const backNonWarga = document.getElementById('back-non-warga');

        const closeNik = document.getElementById('close-nik');
        const closeWarga = document.getElementById('close-warga');
        const closeNonWarga = document.getElementById('close-non-warga');

        // Buka modal validasi NIK.
        if (btnActivity && modalNik) {
            btnActivity.addEventListener('click', function(event) {
                event.preventDefault();
                modalNik.classList.add('show');
            });
        }

        // Pilihan warga.
        if (btnYes && modalNik && modalWarga) {
            btnYes.addEventListener('click', function() {
                modalNik.classList.remove('show');
                modalWarga.classList.add('show');
            });
        }

        // Pilihan non-warga.
        if (btnNo && modalNik && modalNonWarga) {
            btnNo.addEventListener('click', function() {
                modalNik.classList.remove('show');
                modalNonWarga.classList.add('show');
            });
        }

        // Tutup modal.
        if (closeNik && modalNik) {
            closeNik.addEventListener('click', function() {
                modalNik.classList.remove('show');
            });
        }

        if (closeWarga && modalWarga) {
            closeWarga.addEventListener('click', function() {
                modalWarga.classList.remove('show');
            });
        }

        if (closeNonWarga && modalNonWarga) {
            closeNonWarga.addEventListener('click', function() {
                modalNonWarga.classList.remove('show');
            });
        }

        // Kembali ke pilihan validasi NIK.
        if (backWarga && modalWarga && modalNik) {
            backWarga.addEventListener('click', function() {
                modalWarga.classList.remove('show');
                modalNik.classList.add('show');
            });
        }

        if (backNonWarga && modalNonWarga && modalNik) {
            backNonWarga.addEventListener('click', function() {
                modalNonWarga.classList.remove('show');
                modalNik.classList.add('show');
            });
        }

        // Tutup modal saat area luar kotak diklik.
        [modalNik, modalWarga, modalNonWarga].forEach(function(modal) {
            if (!modal) {
                return;
            }

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });

    });
</script>

@endsection