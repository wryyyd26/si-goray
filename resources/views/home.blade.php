@extends('layouts.app')

@section('content')

@include('components.navbar')

<section class="hero-section">
    <div class="hero-container">
        <!-- BLOK KIRI: Teks dan Tombol -->
        <div class="hero-content">
            <h1>Beli Ticket Event Jadi Lebih Mudah & Praktis di GOR A.Yani Kota Mojokerto</h1>
            <p>Temukan berbagai event olahraga, seni, dan budaya. serta reservasi GOR cepat dan aman</p>

            <div class="hero-buttons">
                <a href="/events" class="btn btn-primary">Cari Event Sekarang</a>
                <a href="#activity" class="btn btn-outline" id="btn-activity">Activity Reguler</a>
            </div>
        </div>

        <!-- BLOK KANAN: Gambar Vektor -->
        <div class="hero-image">
            <!-- Ubah nama file 'vektor-gor.png' dengan nama file gambar yang Anda miliki di folder storage -->
            <img src="{{ asset('storage/images/vektor-gor.png') }}" alt="ilustrasi GOR A.Yani">
        </div>
    </div>
</section>


<section class="event-section">
    <div class="event-container">

        <!-- Judul -->
        <div class="event-heading">
            <h2>Jelajahi Event Menarik</h2>
        </div>

        <!-- Filter Kategori -->
        <div class="event-filters">
            <a href="#" class="filter-button active">Semua</a>
            <a href="#" class="filter-button">Sport</a>
            <a href="#" class="filter-button">Seni</a>
            <a href="#" class="filter-button">Hiburan</a>
        </div>

        <!-- Daftar Event -->
        @php
        $events = [
        [
        'title' => 'Futsal',
        'description' => 'Pertandingan Antar Sekolah',
        'category' => 'Sport',
        'image' => 'images/events/futsal.jpg',
        'remaining' => 24,
        'total' => 200,
        'price' => 'Gratis',
        ],
        [
        'title' => 'Futsal',
        'description' => 'Pertandingan Antar Sekolah',
        'category' => 'Sport',
        'image' => 'images/events/futsal.jpg',
        'remaining' => 24,
        'total' => 200,
        'price' => 'Gratis',
        ],
        [
        'title' => 'Futsal',
        'description' => 'Pertandingan Antar Sekolah',
        'category' => 'Sport',
        'image' => 'images/events/futsal.jpg',
        'remaining' => 24,
        'total' => 200,
        'price' => 'Gratis',
        ],
        ];
        @endphp

        <div class="event-grid">
            @foreach ($events as $event)
            <div class="event-card">

                <!-- Gambar Event -->
                <div class="event-image">
                    <img src="{{ asset($event['image']) }}"
                        alt="{{ $event['title'] }}">

                    <span class="event-category">
                        {{ $event['category'] }}
                    </span>
                </div>

                <!-- Informasi Event -->
                <div class="event-info">
                    <h3>{{ $event['title'] }}</h3>
                    <p class="event-description">
                        {{ $event['description'] }}
                    </p>

                    <!-- Kuota Tiket -->
                    <div class="event-quota">
                        <span class="quota-icon">▣</span>
                        <span>
                            Kuota Event: Sisa {{ $event['remaining'] }}
                            dari {{ $event['total'] }} Ticket
                        </span>
                    </div>

                    <div class="quota-progress">
                        <div class="quota-progress-bar"
                            style="width: {{ (($event['total'] - $event['remaining']) / $event['total']) * 100 }}%">
                        </div>
                    </div>

                    <!-- Harga -->
                    <div class="event-price">
                        <h3>{{ $event['price'] }}</h3>
                        <span>S&K</span>
                    </div>
                </div>

                <!-- Tombol Pesan -->
                <div class="event-action">
                    <a href="#" class="book-ticket">
                        <span class="ticket-icon">🎟</span>
                        Pesan Tiket
                    </a>
                </div>

            </div>
            @endforeach
        </div>

    </div>
</section>

<!-- SEKSYEN TEMPAHAN (RESERVASI) -->
<section class="reservation-section">
    <div class="reservation-container">
        <div class="reservation-content">
            <h2>Ingin Reservasi GOR A.Yani ?</h2>
            <p>Cocok untuk turnament, pameran, konser atau acara besar anda. Hubungi kami untuk lebih lanjut.</p>
            <a href="/contact" class="btn-hubungi">Hubungi Kami</a>
        </div>
        <div class="reservation-image">
            <!-- Ganti dengan nama fail vektor ilustrasi anda -->
            <img src="{{ asset('storage/images/VECTOR-1.png') }}" alt="Ilustrasi Reservasi">
        </div>
    </div>
</section>

<!-- (FOOTER) -->
<footer class="footer-section">
    <div class="footer-container">
        <!-- Kolum 1: Logo & Info -->
        <div class="footer-col">
            <img src="{{ asset('storage/images/Logo-Si-goray.png') }}" alt="SI-GORAY Logo" class="footer-logo">
            <p class="footer-text">Sistem Informasi E-Ticket<br>GOR A.Yani</p>
            <div class="social-icons">
                <a href="#" class="sosmed-link" aria-label="Facebook">
                    <i class="fi fi-brands-facebook"></i>
                </a>
                <a href="#" class="sosmed-link" aria-label="YouTube">
                    <i class="fi fi-brands-youtube"></i>
                </a>
                <a href="#" class="sosmed-link" aria-label="Instagram">
                    <i class="fi fi-brands-instagram"></i>
                </a>
            </div>
        </div>

        <!-- Kolum 2: Kontak -->
        <div class="footer-col">
            <h3>Kontak Kami</h3>
            <ul class="contact-list">
                <li><span class="material-symbols-outlined">mail</span> Email: sigoray@gmail.com</li>
                <li><span class="material-symbols-outlined">call</span> Telephone: (0321) 08xxxxxx</li>
                <li><span class="material-symbols-outlined">chat</span> Whatsapp: 08xxxxxx</li>
            </ul>
        </div>

        <!-- Kolum 3: Alamat -->
        <div class="footer-col">
            <h3>Alamat Kami</h3>
            <p>Jl. Gajah Mada No.149,<br>Mergelo, Balongsari, Kec.<br>Magersari, Kota Mojokerto,<br>Jawa Timur 61314</p>
        </div>

        <!-- Kolum 4: Peta -->
        <div class="footer-col map-col">
            <!-- Gunakan imej peta tangkapan skrin atau iframe Google Maps -->
            <img src="{{ asset('storage/images/peta-mojokerto.png') }}" alt="Peta Lokasi" class="map-image">
        </div>
    </div>

    <div class="footer-bottom">
        <p>Copyright sigoray.go.id 2026</p>
    </div>
</footer>

<!-- ========================================== -->
<!-- KODE POP-UP MODAL (Letakkan di bawah section) -->
<!-- ========================================== -->
<div class="modal-overlay" id="modal-nik">
    <div class="modal-box">
        <h3>Aktivasi NIK</h3>
        <p>Apakah Anda Termasuk NIK Warga Kota Mojokerto?</p>

        <div class="modal-buttons">
            <button class="btn-yes" id="btn-yes">Iya</button>
            <button class="btn-no" id="btn-no">Tidak</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnActivity = document.getElementById('btn-activity');
        const modalNik = document.getElementById('modal-nik');
        const btnYes = document.getElementById('btn-yes');
        const btnNo = document.getElementById('btn-no');

        // Membuka Pop-up saat tombol diklik
        if (btnActivity) {
            btnActivity.addEventListener('click', function(event) {
                event.preventDefault(); // Mencegah halaman melompat ke atas
                modalNik.classList.add('show');
            });
        }

        // Fungsi untuk menutup Pop-up
        function closeModal() {
            modalNik.classList.remove('show');

            // Opsional: Anda bisa menambahkan logika lanjutan di sini nanti, 
            // misalnya mengarahkan pengguna (redirect) jika mereka klik "Iya"
        }

        // Menutup pop-up ketika tombol Iya atau Tidak ditekan
        if (btnYes) btnYes.addEventListener('click', closeModal);
        if (btnNo) btnNo.addEventListener('click', closeModal);
    });
</script>

@endsection