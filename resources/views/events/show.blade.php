@extends('layouts.app')

@section('content')

@include('components.navbar')

<main class="event-detail-page">

    {{-- DETAIL EVENT --}}
    <section class="event-detail">

        {{-- POSTER EVENT --}}
        <div class="event-main-image">
            <img
                src="{{ asset('storage/images/events/futsal.jpg') }}"
                alt="Poster Kompetisi Olahraga Antar Sekolah">
        </div>

        {{-- PANEL TIKET --}}
        <aside class="event-ticket-panel">

            <div class="ticket-summary">
                <h2>Futsal</h2>
                <p>Pertandingan Antar Sekolah</p>

                <ul class="event-meta">
                    <li>
                        <span class="material-symbols-rounded">calendar_month</span>
                        <span>Kamis, 27 Agustus 2026</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">schedule</span>
                        <span>15.00 WIB</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">location_on</span>
                        <span>GOR A. Yani</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">confirmation_number</span>
                        <span>Kuota Event: Sisa 24 dari 200 Tiket</span>
                    </li>
                </ul>

                <div class="ticket-quota">
                    <span></span>
                </div>

                {{-- HARGA TIKET WARGA --}}
                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="citizen-button active"
                        data-category="warga"
                        aria-pressed="true">
                        Gratis
                    </button>

                    <span>NIK Kota Mojokerto</span>
                </div>

                {{-- HARGA TIKET LUAR KOTA --}}
                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="non-citizen-button"
                        data-category="luar"
                        aria-pressed="false">
                        Rp25.000
                    </button>

                    <span>NIK Luar Kota Mojokerto</span>
                </div>
            </div>

            {{-- PEMILIHAN KATEGORI --}}
            <div class="ticket-bottom">

                <img
                    src="{{ asset('storage/images/Logo-Si-goray.png') }}" alt="Logo SI-GORAY" class="ticket-brand-logo">

                <strong>PESAN TIKET EVENT</strong>

                <button
                    type="button"
                    class="ticket-category-button active"
                    data-category="warga"
                    aria-pressed="true">
                    Warga Kota Mojokerto
                </button>

                <button
                    type="button"
                    class="ticket-category-button"
                    data-category="luar"
                    aria-pressed="false">
                    Luar Warga Kota Mojokerto
                </button>

                <small>Pilih Sesuai NIK Wilayah</small>

            </div>

        </aside>

    </section>

    {{-- DESKRIPSI EVENT --}}
    <section class="event-description">

        <h2>Deskripsi Event</h2>

        <p>
            Tunjukkan Dukunganmu! Jadilah Saksi Sejarah Rivalitas dan
            Sportivitas Pelajar Terbaik Mojokerto!
        </p>

        <p>
            Siapkan gemuruh dan semangatmu! Kompetisi Olahraga Antar
            Sekolah kembali hadir di GOR A. Yani Kota Mojokerto.
            Pertandingan futsal paling bergengsi tahun ini akan
            mempertemukan tim-tim tangguh dari berbagai SMP dan
            SMA/SMK se-Kota Mojokerto. Mereka siap bertarung
            habis-habisan di lapangan demi membawa pulang piala
            kehormatan dan mengharumkan nama sekolah mereka!
        </p>

    </section>

</main>

{{-- JAVASCRIPT PEMILIHAN KATEGORI TIKET --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryButtons = document.querySelectorAll(
            '.event-detail-page [data-category]'
        );

        categoryButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const selectedCategory = this.dataset.category;

                categoryButtons.forEach(function(item) {
                    const isActive =
                        item.dataset.category === selectedCategory;

                    item.classList.toggle('active', isActive);
                    item.setAttribute('aria-pressed', String(isActive));
                });
            });
        });
    });
</script>

@include('components.footer')

@endsection