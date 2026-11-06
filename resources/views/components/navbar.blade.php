<nav class="navbar">
    <div class="navbar-container">

        <!-- LOGO -->
        <a href="{{ route('home') }}" class="navbar-brand">
            <img
                src="{{ asset('storage/images/Logo-Maja-tix.png') }}"
                alt="MAJA-TIX Logo"
                class="logo">
        </a>

        <!-- HAMBURGER -->
        <button
            class="hamburger"
            id="hamburger-btn"
            type="button"
            aria-label="Buka menu">
            &#9776;
        </button>

        <!-- MENU NAVIGASI -->
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

        <!-- BAGIAN KANAN -->
        <div class="navbar-auth" id="nav-auth">

            <!-- NOTIFIKASI -->
            <div class="notification-wrapper">

                <!-- TOMBOL PANDUAN AR -->
                <a href="{{ route('guides.index') }}" class="ar-navbar-button">
                    <span class="material-symbols-rounded">view_in_ar</span>
                </a>

                <button
                    type="button"
                    class="notification-button"
                    id="notification-btn"
                    aria-label="Buka notifikasi"
                    aria-expanded="false">

                    <span class="material-symbols-rounded">
                        notifications
                    </span>

                    <span
                        class="notification-badge"
                        id="notification-count">
                        3
                    </span>
                </button>

                <!-- DROPDOWN NOTIFIKASI -->
                <div
                    class="notification-dropdown"
                    id="notification-dropdown">

                    <!-- HEADER -->
                    <div class="notification-header">
                        <div>
                            <h3>Notifikasi</h3>
                            <p>Informasi terbaru MAJA-TIX</p>
                        </div>

                        <button
                            type="button"
                            id="mark-all-read"
                            class="mark-all-read">
                            Tandai dibaca
                        </button>
                    </div>

                    <!-- FILTER -->
                    <div class="notification-tabs">

                        <button
                            type="button"
                            class="notification-tab active"
                            data-filter="all">
                            Semua
                        </button>

                        <button
                            type="button"
                            class="notification-tab"
                            data-filter="payment">
                            Pembayaran
                        </button>

                        <button
                            type="button"
                            class="notification-tab"
                            data-filter="event">
                            Event
                        </button>

                    </div>

                    <!-- DAFTAR NOTIFIKASI -->
                    <div
                        class="notification-list"
                        id="notification-list">

                        <!-- PEMBAYARAN BERHASIL -->
                        <div
                            class="notification-item unread"
                            data-category="payment">

                            <div class="notification-icon payment-icon">
                                <span class="material-symbols-rounded">
                                    check_circle
                                </span>
                            </div>

                            <div class="notification-content">
                                <strong>Pembayaran berhasil</strong>
                                <p>
                                    Pembayaran tiket event telah berhasil
                                    diverifikasi.
                                </p>
                                <small>Baru saja</small>
                            </div>

                            <span class="unread-dot"></span>
                        </div>

                        <!-- EVENT TERBARU -->
                        <div
                            class="notification-item unread"
                            data-category="event">

                            <div class="notification-icon event-icon">
                                <span class="material-symbols-rounded">
                                    campaign
                                </span>
                            </div>

                            <div class="notification-content">
                                <strong>Event terbaru tersedia</strong>
                                <p>
                                    Event Kasti telah ditambahkan.
                                    Cek informasi event sekarang.
                                </p>
                                <small>10 menit lalu</small>
                            </div>

                            <span class="unread-dot"></span>
                        </div>

                        <!-- MENUNGGU PEMBAYARAN -->
                        <div
                            class="notification-item unread"
                            data-category="payment">

                            <div class="notification-icon pending-icon">
                                <span class="material-symbols-rounded">
                                    schedule
                                </span>
                            </div>

                            <div class="notification-content">
                                <strong>Menunggu pembayaran</strong>
                                <p>
                                    Silakan selesaikan pembayaran tiket
                                    yang telah dipesan.
                                </p>
                                <small>1 jam lalu</small>
                            </div>

                            <span class="unread-dot"></span>
                        </div>

                    </div>

                    <!-- NOTIFIKASI KOSONG -->
                    <div
                        class="notification-empty"
                        id="notification-empty"
                        hidden>
                        Tidak ada notifikasi pada kategori ini.
                    </div>

                    <!-- FOOTER -->
                    <div class="notification-footer">
                        <a href="/notifications">
                            Lihat semua notifikasi
                        </a>
                    </div>

                </div>
            </div>

            <!-- DROPDOWN AKUN -->
            <div class="account-wrapper">

                <button
                    type="button"
                    class="account-button"
                    id="account-btn"
                    aria-expanded="false">

                    <span class="material-symbols-rounded">
                        account_circle
                    </span>

                    <span class="material-symbols-rounded account-arrow">
                        expand_more
                    </span>
                </button>

                <div
                    class="account-dropdown"
                    id="account-dropdown">

                    @auth('web')
                    <div class="account-dropdown-header">
                        <strong>Akun Pengunjung</strong>
                        <small>{{ auth('web')->user()->no_hp_email }}</small>
                    </div>
                    <form id="visitor-navbar-logout-form" method="POST" action="{{ route('pengunjung.web-auth.logout') }}">
                        @csrf
                        <button type="submit" class="account-dropdown-item" style="width:100%;text-align:left;border:0;background:transparent;cursor:pointer;">
                            <span class="material-symbols-rounded">logout</span>
                            <span><strong>Logout</strong><small>Keluar dari akun pengunjung</small></span>
                        </button>
                    </form>
                    @else
                    <div class="account-dropdown-header">
                        <strong>Selamat datang!</strong>
                        <small>Masuk atau daftar menggunakan OTP email.</small>
                    </div>
                    <a href="{{ route('pengunjung.login', ['next' => request()->getRequestUri()]) }}" class="account-dropdown-item">
                        <span class="material-symbols-rounded">login</span>
                        <span><strong>Login / Daftar</strong><small>Verifikasi menggunakan email</small></span>
                    </a>
                    @endauth

                </div>
            </div>

        </div>
    </div>
</nav>

<!-- JAVASCRIPT NAVBAR -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const hamburger = document.getElementById('hamburger-btn');
        const navMenu = document.getElementById('nav-menu');
        const navAuth = document.getElementById('nav-auth');

        const notificationBtn = document.getElementById('notification-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationCount = document.getElementById('notification-count');

        const accountBtn = document.getElementById('account-btn');
        const accountDropdown = document.getElementById('account-dropdown');

        const tabs = document.querySelectorAll('.notification-tab');
        const items = document.querySelectorAll('.notification-item');

        const markAllRead = document.getElementById('mark-all-read');
        const emptyMessage = document.getElementById('notification-empty');

        // HAMBURGER MENU
        hamburger?.addEventListener('click', function() {
            navMenu?.classList.toggle('show');
            navAuth?.classList.toggle('show');
        });

        // UPDATE JUMLAH NOTIFIKASI BELUM DIBACA
        function updateNotificationCount() {
            if (!notificationCount) return;

            const unread = [...items].filter(function(item) {
                return item.classList.contains('unread');
            }).length;

            notificationCount.textContent = unread;
            notificationCount.classList.toggle('hidden', unread === 0);
        }

        // TUTUP DROPDOWN
        function closeDropdowns() {
            notificationDropdown?.classList.remove('show');
            notificationBtn?.setAttribute('aria-expanded', 'false');

            accountDropdown?.classList.remove('show');
            accountBtn?.classList.remove('active');
            accountBtn?.setAttribute('aria-expanded', 'false');
        }

        // DROPDOWN NOTIFIKASI
        notificationBtn?.addEventListener('click', function(event) {
            event.stopPropagation();

            const isOpen = notificationDropdown.classList.toggle('show');

            notificationBtn.setAttribute('aria-expanded', isOpen);

            accountDropdown?.classList.remove('show');
            accountBtn?.classList.remove('active');
            accountBtn?.setAttribute('aria-expanded', 'false');
        });

        // DROPDOWN AKUN
        accountBtn?.addEventListener('click', function(event) {
            event.stopPropagation();

            const isOpen = accountDropdown.classList.toggle('show');

            accountBtn.classList.toggle('active', isOpen);
            accountBtn.setAttribute('aria-expanded', isOpen);

            notificationDropdown?.classList.remove('show');
            notificationBtn?.setAttribute('aria-expanded', 'false');
        });

        // FILTER NOTIFIKASI
        function filterNotifications(filter) {

            tabs.forEach(function(tab) {
                tab.classList.toggle(
                    'active',
                    tab.dataset.filter === filter
                );
            });

            items.forEach(function(item) {
                const match =
                    filter === 'all' ||
                    item.dataset.category === filter;

                item.hidden = !match;
            });

            const hasVisibleItem = [...items].some(function(item) {
                return !item.hidden;
            });

            if (emptyMessage) {
                emptyMessage.hidden = hasVisibleItem;
            }
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                filterNotifications(tab.dataset.filter);
            });
        });

        // TANDAI SEMUA DIBACA
        markAllRead?.addEventListener('click', function() {
            items.forEach(function(item) {
                item.classList.remove('unread');
            });

            updateNotificationCount();
        });

        // TANDAI SATU NOTIFIKASI DIBACA
        items.forEach(function(item) {
            item.addEventListener('click', function() {
                item.classList.remove('unread');
                updateNotificationCount();
            });
        });

        // KLIK DI LUAR DROPDOWN
        document.addEventListener('click', function(event) {

            if (
                !notificationDropdown?.contains(event.target) &&
                !notificationBtn?.contains(event.target) &&
                !accountDropdown?.contains(event.target) &&
                !accountBtn?.contains(event.target)
            ) {
                closeDropdowns();
            }
        });

        // TUTUP DENGAN TOMBOL ESCAPE
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDropdowns();
            }
        });

        // Logout pengunjung: kirim POST + CSRF, tanpa memengaruhi session Admin.
        const visitorLogoutForm = document.getElementById('visitor-navbar-logout-form');
        visitorLogoutForm?.addEventListener('submit', async function(event) {
            event.preventDefault();
            const button = visitorLogoutForm.querySelector('button[type="submit"]');
            if (button) button.disabled = true;
            try {
                const response = await fetch(visitorLogoutForm.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': visitorLogoutForm.querySelector('input[name="_token"]').value
                    }
                });
                if (!response.ok) throw new Error('Logout gagal. Silakan coba lagi.');
                window.location.assign(@json(route('home')));
            } catch (error) {
                if (button) button.disabled = false;
                alert(error.message);
            }
        });

        // KONDISI AWAL
        filterNotifications('all');
        updateNotificationCount();

    });
</script>
