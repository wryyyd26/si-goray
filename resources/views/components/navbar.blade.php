<nav class="navbar">
    <div class="navbar-container">
        <!-- BLOK 1: KIRI (Logo) -->
        <a href="/" class="navbar-brand">
            <img src="{{ asset('storage/images/Logo-Si-goray.png') }}" alt="SI-GORAY Logo" class="logo">
        </a>

        <!-- TAMBAHAN: Tombol Garis Tiga (Hamburger) -->
        <button class="hamburger" id="hamburger-btn">
            &#9776; <!-- Ini adalah kode HTML untuk ikon 3 garis -->
        </button>

        <!-- BLOK 2: TENGAH (Menu Navigasi) -->
    
<!-- BLOK 2: TENGAH (Menu Navigasi) -->
<div class="navbar-menu" id="nav-menu">
    <a href="/"
       class="nav-link {{ request()->is('/') ? 'active' : '' }}">
        Beranda
    </a>

    <a href="/events"
       class="nav-link {{ request()->is('events*') ? 'active' : '' }}">
        Event
    </a>

    <a href="/about"
       class="nav-link {{ request()->is('about*') ? 'active' : '' }}">
        Tentang Kami
    </a>

    <a href="/contact"
       class="nav-link {{ request()->is('contact*') ? 'active' : '' }}">
        Kontak Kami
    </a>
</div>

        <!-- BLOK 3: KANAN (Daftar & Login) -->
        <div class="navbar-auth"id="nav-auth">
            <a href="/register" class="nav-link">Daftar</a>
            <a href="/login" class="nav-link">Login</a>
        </div>
    </div>

<script>
    // Menangkap elemen HTML berdasarkan ID
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const navMenu = document.getElementById('nav-menu');
    const navAuth = document.getElementById('nav-auth');

    // Memberikan perintah ketika tombol garis tiga diklik
    hamburgerBtn.addEventListener('click', function() {
        // Toggle: Tambahkan class 'show' jika belum ada, atau hapus jika sudah ada
        navMenu.classList.toggle('show');
        navAuth.classList.toggle('show');
    });
</script>

</nav>

