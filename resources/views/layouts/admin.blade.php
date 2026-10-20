<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'Admin Panel')
        - SI-GORAY
    </title>

    {{-- Google Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/Logo-Si-goray.png') }}" type="image/png">

    {{-- Material Symbols --}}
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Vite Assets --}}
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])

    @stack('styles')

</head>


<body>

    <div class="admin-wrapper">

        {{-- SIDEBAR --}}

        <aside class="sidebar" id="sidebar">

            {{-- Logo --}}
            <div class="sidebar-header">

                <a href="{{ route('admin.dashboard') }}" class="brand">

                    <div class="brand-logo-custom">
                        <img src="{{ asset('images/Logo-Si-goray.png') }}" alt="Logo SI-GORAY">
                    </div>
                </a>

                {{-- Mobile close --}}
                <button class="sidebar-close" id="sidebarClose" type="button">
                    <span class="material-symbols-rounded">
                        close
                    </span>
                </button>

            </div>

            {{-- MENU --}}

            {{-- MENU --}}
            <nav class="sidebar-menu">

                {{-- Section --}}
                <div class="menu-section">
                    UTAMA
                </div>

                {{-- Dashboard --}}
                <a href="{{ route('admin.dashboard') }}"
                    class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">
                        dashboard
                    </span>
                    <span>Dashboard</span>
                </a>

                {{-- Section --}}
                <div class="menu-section">
                    TIKET & PEMESANAN
                </div>

                {{-- Tiket Reguler --}}
                <a href="{{ route('admin.reguler.index') }}"
                    class="menu-item {{ request()->routeIs('admin.reguler.*') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">
                        confirmation_number
                    </span>
                    <span>Tiket Reguler</span>
                </a>


                {{-- Event --}}
                <a href="{{ route('admin.events.index') }}"
                    class="menu-item {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">
                        storefront
                    </span>
                    <span>Tiket Event</span>
                </a>

                {{-- Kelola Pesanan --}}
                <a href="{{ route('admin.booking.pemesanan') }}"
                    class="menu-item {{ request()->routeIs('admin.booking.*') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">
                        receipt_long
                    </span>
                    <span>Kelola Pemesanan</span>
                </a>

                {{-- Kelola Pembayaran --}}
                <a href="{{ route('admin.payment.pembayaran') }}"
                    class="menu-item {{ request()->routeIs('admin.payment.*') ? 'active' : '' }}">

                    <span class="material-symbols-rounded">
                        credit_card
                    </span>
                    <span>Kelola Pembayaran</span>
                </a>


                {{-- Section --}}
                <div class="menu-section">
                    PENGGUNA
                </div>

                {{-- Kelola User --}}
                <a href="{{ route('admin.user.index') }}"
                    class="menu-item {{ request()->routeIs('admin.user.*') ? 'active' : '' }}">

                    <span class="material-symbols-rounded">
                        account_circle
                    </span>

                    <span>Kelola User</span>
                </a>


                {{-- Section --}}
                <div class="menu-section">
                    FASILITAS
                </div>

                {{-- Saran & Masukan --}}
                <a href="{{ route('admin.saran-masukan.index') }}"
                    class="menu-item {{ request()->routeIs('admin.saran-masukan.*') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">
                        mail
                    </span>
                    <span>Saran & Masukan</span>
                </a>

                {{-- Laporan --}}

                <a href="{{ route('admin.report.index') }}"
                    class="menu-item {{ request()->routeIs('admin.report.*') ? 'active' : '' }}">
                    <span class="material-symbols-rounded">bar_chart</span>
                    <span>Laporan</span>
                </a>
            </nav>

            {{-- SIDEBAR FOOTER --}}
            <div class="sidebar-footer">
                <a href="#" class="menu-item logout">
                    <span class="material-symbols-rounded">
                        logout
                    </span>
                    <span>KELUAR</span>
                </a>
            </div>

        </aside>


        {{-- Overlay untuk mobile --}}
        <div class="sidebar-overlay" id="sidebarOverlay"></div>


        {{-- MAIN AREA --}}
        <div class="main-wrapper">

            {{-- TOPBAR --}}

            <header class="topbar">

                <div class="topbar-left">

                    {{-- Mobile menu --}}
                    <button class="icon-button mobile-menu-button" id="sidebarToggle" type="button">

                        <span class="material-symbols-rounded">
                            menu
                        </span>

                    </button>


                    <div class="breadcrumb">
                        <span>
                            Admin
                        </span>

                        <span class="breadcrumb-separator">
                            /
                        </span>

                        <strong>
                            @yield('page', 'Dashboard')
                        </strong>
                    </div>
                </div>


                <div class="topbar-right">

                    {{-- Global Search --}}
                    <button class="icon-button" id="globalSearchToggle" type="button" title="Pencarian global"
                        aria-label="Buka pencarian global" aria-haspopup="dialog" aria-controls="globalSearchPanel">
                        <span class="material-symbols-rounded">search</span>
                    </button>

                    {{-- Notification --}}
                    <div class="notification-wrapper" id="notificationWrapper">

                        <button class="icon-button notification-button" id="notificationToggle" type="button"
                            title="Notifikasi" aria-label="Buka notifikasi" aria-haspopup="true" aria-expanded="false"
                            aria-controls="notificationDropdown">

                            <span class="material-symbols-rounded">notifications</span>

                            <span class="notification-count" id="notificationCount">3</span>
                        </button>

                        {{-- Dropdown Notifikasi --}}
                        <section class="notification-dropdown" id="notificationDropdown" aria-label="Daftar notifikasi"
                            hidden>

                            <div class="notification-header">
                                <div>
                                    <h3>Notifikasi</h3>
                                    <p id="notificationSummary">3 belum dibaca</p>
                                </div>

                                <button type="button" class="notification-mark-all" id="notificationMarkAll">
                                    Tandai semua dibaca
                                </button>
                            </div>

                            <div class="notification-list" id="notificationList" aria-live="polite">
                                {{-- Data dummy akan ditampilkan oleh JavaScript --}}
                            </div>

                            <a href="#" class="notification-see-all">
                                Lihat Semua Notifikasi
                                <span class="material-symbols-rounded">arrow_forward</span>
                            </a>
                        </section>

                    </div>

                    {{-- Theme --}}
                    <button class="icon-button" id="themeToggle" type="button" title="Ganti tema">
                        <span class="material-symbols-rounded" id="themeIcon">
                            dark_mode
                        </span>
                    </button>


                    {{-- Divider --}}
                    <div class="topbar-divider"></div>


                    {{-- Admin Profile --}}
                    <div class="admin-profile">

                        <div class="admin-avatar">
                            A
                        </div>

                        <div class="admin-info">

                            <strong>
                                Admin
                            </strong>

                            <span>
                                Administrator
                            </span>

                        </div>

                    </div>

                </div>

            </header>


            {{-- GLOBAL SEARCH PANEL --}}
            <div class="global-search-overlay" id="globalSearchOverlay" aria-hidden="true">
                <section class="global-search-panel" id="globalSearchPanel" role="dialog" aria-modal="true"
                    aria-labelledby="globalSearchTitle" aria-hidden="true" tabindex="-1">
                    <div class="global-search-header">
                        <div class="global-search-heading">
                            <div class="global-search-heading-icon">
                                <span class="material-symbols-rounded">search</span>
                            </div>

                            <div>
                                <h2 id="globalSearchTitle">Pencarian Global</h2>
                                <p>Cari informasi di panel admin SI-GORAY</p>
                            </div>
                        </div>

                        <button type="button" class="global-search-close" id="globalSearchClose"
                            aria-label="Tutup pencarian">
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </div>

                    <form class="global-search-form" id="globalSearchForm" role="search" autocomplete="off">
                        <span class="material-symbols-rounded">search</span>

                        <input type="search" id="globalSearchInput" name="q"
                            placeholder="Cari event, kode pemesanan, tiket reguler..."
                            aria-label="Kata kunci pencarian" maxlength="100">

                        <button type="button" id="globalSearchClear" class="global-search-clear"
                            aria-label="Hapus kata kunci" hidden>
                            <span class="material-symbols-rounded">close</span>
                        </button>

                        <kbd>ESC</kbd>
                    </form>

                    <div class="global-search-results" id="globalSearchResults" aria-live="polite"
                        aria-busy="false">
                        <div class="global-search-welcome">
                            <div class="global-search-welcome-icon">
                                <span class="material-symbols-rounded">travel_explore</span>
                            </div>

                            <h3>Cari informasi dengan cepat</h3>

                            <p>
                                Masukkan nama event, kode pemesanan, atau informasi
                                tiket yang ingin kamu temukan.
                            </p>
                        </div>
                    </div>

                    <div class="global-search-footer">
                        <span>
                            <kbd>↑</kbd>
                            <kbd>↓</kbd>
                            untuk navigasi
                        </span>

                        <span>
                            <kbd>ESC</kbd>
                            untuk menutup
                        </span>
                    </div>
                </section>
            </div>


            {{-- =====================================
            CONTENT
        ====================================== --}}

            <main class="main-content">

                @yield('content')

            </main>


        </div>

    </div>
    @stack('scripts')

</body>

</html>
