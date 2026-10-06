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
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        local_activity
                    </span>
                    <span>Tiket Reguler</span>
                </a>

                {{-- Tiket Event --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        storefront
                    </span>
                    <span>Tiket Event</span>
                </a>

                {{-- Kelola Pesanan --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        receipt_long
                    </span>
                    <span>Kelola Pesanan</span>
                </a>

                {{-- Kelola Pembayaran --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        credit_card
                    </span>
                    <span>Kelola Pembayaran</span>
                </a>


                {{-- Section --}}
                <div class="menu-section">
                    PENGGUNA
                </div>

                {{-- Kelola Notifikasi --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        notifications
                    </span>
                    <span>Kelola Notifikasi</span>
                </a>

                {{-- Kelola User --}}
                <a href="#" class="menu-item">
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
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        mail
                    </span>
                    <span>Saran & Masukan</span>
                </a>

                {{-- Laporan --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        bar_chart
                    </span>
                    <span>Laporan</span>
                </a>


                {{-- Section --}}
                <div class="menu-section">
                    PENGATURAN
                </div>

                {{-- Manajemen Admin --}}
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">
                        settings
                    </span>
                    <span>Manajemen Admin</span>
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


                    {{-- Search --}}
                    <button class="icon-button" type="button" title="Pencarian">

                        <span class="material-symbols-rounded">
                            search
                        </span>

                    </button>


                    {{-- Notification --}}
                    <button class="icon-button notification-button" type="button" title="Notifikasi">

                        <span class="material-symbols-rounded">
                            notifications
                        </span>

                        <span class="notification-dot"></span>

                    </button>


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


            {{-- =====================================
            CONTENT
        ====================================== --}}

            <main class="main-content">

                @yield('content')

            </main>


        </div>

    </div>


    {{-- Admin JS --}}
    <script src="{{ asset('admin/js/admin.js') }}"></script>

    @stack('scripts')

</body>

</html>
