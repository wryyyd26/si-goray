@extends('layouts.app')

@section('content')
@include('components.navbar')

<main class="about-page">

    <!-- Hero Tentang Kami -->
    <section class="about-hero">
        <div class="about-container about-hero-content">
            <div class="about-hero-text">
                <span class="about-label">TENTANG SI-GORAY</span>
                <h1>Kenali SI-GORAY Lebih Dekat</h1>
                <p>
                    SI-GORAY adalah platform resmi untuk pembelian e-ticket dan reservasi fasilitas di GOR A. Yani, Kota Mojokerto. Kami hadir untuk memberikan akses kegiatan olahraga, seni, dan budaya yang cepat, aman, dan transparan dalam satu genggaman.
                </p>
                <a href="/events" class="about-button">
                    Jelajahi Event
                </a>
            </div>

            <div class="about-hero-image">
                <img
                    src="{{ asset('images/about-event.jpg') }}"
                    alt="Kegiatan event SI-GORAY">
            </div>
        </div>
    </section>

    <!-- Tentang Platform -->
    <section class="about-intro">
        <div class="about-container about-intro-content">
            <div class="about-section-image">
                <img
                    src="{{ asset('images/about-platform.jpg') }}"
                    alt="Platform digital SI-GORAY">
            </div>

            <div class="about-section-text">
                <span class="about-label">SI-GORAY</span>
                <h2>Satu Platform untuk Berbagai Event</h2>
                <p>
                    SI-GORAY hadir sebagai sarana digital untuk
                    menghubungkan penyelenggara event dengan masyarakat.
                    Melalui platform ini, pengguna dapat melihat
                    informasi kegiatan, mengetahui detail event,
                    dan melakukan pemesanan tiket dengan lebih mudah.
                </p>
                <p>
                    Kami berupaya menghadirkan pengalaman reservasi
                    event yang informatif, praktis, dan mudah diakses
                    melalui berbagai perangkat.
                </p>
            </div>
        </div>
    </section>

    <!-- Layanan -->
    <section class="about-services">
        <div class="about-container">
            <div class="about-section-heading">
                <span class="about-label">LAYANAN KAMI</span>
                <h2>Apa yang Bisa Kamu Lakukan?</h2>
                <p>
                    Berbagai kemudahan untuk menemukan dan mengikuti
                    event dalam satu platform.
                </p>
            </div>

            <div class="about-service-grid">
                <div class="about-service-card">
                    <div class="about-service-icon">⌕</div>
                    <h3>Jelajahi Event</h3>
                    <p>
                        Temukan berbagai event berdasarkan kategori
                        dan informasi kegiatan yang tersedia.
                    </p>
                </div>

                <div class="about-service-card">
                    <div class="about-service-icon">🎟</div>
                    <h3>Reservasi Tiket</h3>
                    <p>
                        Akses proses pemesanan tiket event melalui
                        platform secara praktis.
                    </p>
                </div>

                <div class="about-service-card">
                    <div class="about-service-icon">📍</div>
                    <h3>Informasi Lokasi</h3>
                    <p>
                        Ketahui lokasi dan informasi pelaksanaan
                        event untuk membantu persiapan kunjungan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Ajakan -->
    <section class="about-cta">
        <div class="about-container">
            <h2>Temukan Event Favoritmu</h2>
            <p>
                Mulai jelajahi berbagai kegiatan menarik bersama SI-GORAY.
            </p>
            <a href="/events" class="about-button">
                Lihat Semua Event
            </a>
        </div>
    </section>

</main>
@endsection