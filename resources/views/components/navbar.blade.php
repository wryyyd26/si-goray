
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

            <!-- NOTIFIKASI PENGUNJUNG -->
            <div class="notification-wrapper">

                <!-- TOMBOL PANDUAN AR -->
                <a href="{{ route('guides.index') }}"
        class="ar-navbar-button">
                    <span class="material-symbols-rounded">
                        view_in_ar
                    </span>
                </a>

                <!-- TOMBOL LONCENG -->
                <button
                    type="button"
                    class="notification-button"
                    id="notification-btn"
                    aria-label="Buka notifikasi"
                    aria-haspopup="true"
                    aria-controls="notification-dropdown"
                    aria-expanded="false">

                    <span class="material-symbols-rounded">
                        notifications
                    </span>

                    <!-- Tidak memakai angka dummy -->
                    <span
                        class="notification-badge hidden"
                        id="notification-count"
                        hidden>
                        0
                    </span>
                </button>

                <!-- DROPDOWN NOTIFIKASI -->
                <div
                    class="notification-dropdown"
                    id="notification-dropdown">

                    <div class="notification-header">
                        <div>
                            <h3>Notifikasi</h3>
                            <p id="visitor-notification-summary">
                                Informasi terbaru SI-GORAY
                            </p>
                        </div>

                        <button
                            type="button"
                            id="mark-all-read"
                            class="mark-all-read"
                            disabled>
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
                    <!-- Akan diisi dari Laravel pada integrasi backend -->
                    <div
                        class="notification-list"
                        id="notification-list"
                        aria-live="polite">
                    </div>

                    <!-- KONDISI KOSONG -->
                    <div
                        class="notification-empty"
                        id="notification-empty">
                        @auth('web')
                            Belum ada notifikasi.
                        @else
                            Masuk ke akun untuk melihat notifikasi.
                        @endauth
                    </div>

                    <!-- FOOTER DISIMPAN UNTUK HALAMAN NOTIFIKASI -->
                    <!-- Tidak diarahkan ke /notifications sebelum route ada -->
                    <div class="notification-footer" hidden>
                        <a href="#" id="visitor-notification-all">
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
                            <small>
                                {{ auth('web')->user()->no_hp_email }}
                            </small>
                        </div>

                        <!-- PESANAN SAYA -->
                        <a
                            href="{{ route('pengunjung.pesanan.index') }}"
                            class="account-dropdown-item account-order-item">

                            <span class="material-symbols-rounded">
                                receipt_long
                            </span>

                            <span>
                                <strong>Pesanan Saya</strong>
                                <small>Lihat riwayat pemesanan</small>
                            </span>
                        </a>

                        <!-- LOGOUT -->
                        <form
                            id="visitor-navbar-logout-form"
                            method="POST"
                            action="{{ route('pengunjung.web-auth.logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="account-dropdown-item"
                                style="width:100%;text-align:left;border:0;background:transparent;cursor:pointer;">

                                <span class="material-symbols-rounded">
                                    logout
                                </span>

                                <span>
                                    <strong>Logout</strong>
                                    <small>Keluar dari akun pengunjung</small>
                                </span>
                            </button>
                        </form>

                    @else

                        <div class="account-dropdown-header">
                            <strong>Selamat datang!</strong>
                            <small>
                                Masuk atau daftar menggunakan OTP email.
                            </small>
                        </div>

                        <a
                            href="{{ route('pengunjung.login', ['next' => request()->getRequestUri()]) }}"
                            class="account-dropdown-item">

                            <span class="material-symbols-rounded">
                                login
                            </span>

                            <span>
                                <strong>Login / Daftar</strong>
                                <small>Verifikasi menggunakan email</small>
                            </span>
                        </a>

                    @endauth

                </div>
            </div>
        </div>
    </div>
</nav>

<!-- JAVASCRIPT NAVBAR -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger-btn');
    const navMenu = document.getElementById('nav-menu');
    const navAuth = document.getElementById('nav-auth');
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationCount = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');
    const notificationSummary = document.getElementById('visitor-notification-summary');
    const markAllRead = document.getElementById('mark-all-read');
    const emptyMessage = document.getElementById('notification-empty');
    const accountBtn = document.getElementById('account-btn');
    const accountDropdown = document.getElementById('account-dropdown');
    const tabs = notificationDropdown
        ? [...notificationDropdown.querySelectorAll('.notification-tab')]
        : [];
    const visitorLogoutForm = document.getElementById('visitor-navbar-logout-form');
    const isVisitorAuthenticated = @json(auth('web')->check());
    const notificationUrl = @json(route('pengunjung.notifications.index'));
    const readAllUrl = @json(route('pengunjung.notifications.read-all'));
    const ordersPageUrl = @json(route('pengunjung.pesanan.index'));
    const homePageUrl = @json(route('home'));
    const eventsPageUrl = @json(url('/events'));
    const readOneUrlTemplate = @json(route('pengunjung.notifications.read', ['notification' => 'NOTIFICATION_UUID']));

    let notifications = [];
    let unreadCount = 0;
    let activeFilter = 'all';
    let loading = false;
    let marking = false;

    function csrfToken() {
        return visitorLogoutForm?.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content
            || '';
    }

    async function requestJson(url, method = 'GET') {
        const headers = { Accept: 'application/json' };
        if (method !== 'GET') {
            headers['X-CSRF-TOKEN'] = csrfToken();
            headers['Content-Type'] = 'application/json';
        }
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            cache: 'no-store'
        });
        if (!response.ok) {
            throw new Error('Permintaan notifikasi gagal (' + response.status + ').');
        }
        return response.json();
    }

    function updateCount() {
        if (notificationCount) {
            notificationCount.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            notificationCount.hidden = unreadCount === 0;
            notificationCount.classList.toggle('hidden', unreadCount === 0);
        }
        if (notificationSummary) {
            notificationSummary.textContent = isVisitorAuthenticated
                ? unreadCount + ' belum dibaca'
                : 'Masuk untuk melihat notifikasi';
        }
        if (markAllRead) {
            markAllRead.disabled = !isVisitorAuthenticated || unreadCount === 0 || marking;
        }
    }

    function formatTime(iso) {
        if (!iso) return '';
        const date = new Date(iso);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }).format(date);
    }

    function renderNotifications() {
        if (!notificationList) return;
        notificationList.replaceChildren();

        const visible = notifications.filter(item =>
            activeFilter === 'all' || item.category === activeFilter
        );

        visible.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'notification-item' + (!item.is_read ? ' unread' : '');
            row.dataset.category = item.category || 'all';
            row.tabIndex = 0;
            row.setAttribute('role', 'button');
            row.setAttribute('aria-label', 'Baca notifikasi: ' + (item.title || 'Notifikasi'));

            const iconBox = document.createElement('div');
            const iconClass = item.category === 'payment' ? 'payment-icon'
                : item.category === 'event' ? 'event-icon' : 'pending-icon';
            iconBox.className = 'notification-icon ' + iconClass;
            const icon = document.createElement('span');
            icon.className = 'material-symbols-rounded';
            icon.textContent = item.icon || 'notifications';
            iconBox.appendChild(icon);

            const content = document.createElement('div');
            content.className = 'notification-content';
            const title = document.createElement('strong');
            title.textContent = item.title || 'Notifikasi';
            const message = document.createElement('p');
            message.textContent = item.message || '';
            const time = document.createElement('small');
            time.textContent = formatTime(item.created_at);
            content.append(title, message, time);
            row.append(iconBox, content);

            if (!item.is_read) {
                const dot = document.createElement('span');
                dot.className = 'unread-dot';
                row.appendChild(dot);
            }

            function destinationForNotification(notification) {
                // Hanya gunakan URL internal yang diketahui, bukan URL dari teks notifikasi.
                const title = String(notification.title || '').toLowerCase();
                if (title.includes('selamat datang')) return homePageUrl;
                if (title.includes('event terbaru') || title.includes('event baru')) return eventsPageUrl;
                // Booking, pembayaran, QR, dan notifikasi akun lainnya.
                return ordersPageUrl;
            }

            let opening = false;
            async function openNotification() {
                if (opening) return;
                opening = true;
                const destination = destinationForNotification(item);
                // Notifikasi yang sudah dibaca tetap dapat dibuka.
                // Catat status baca lebih dahulu, agar badge konsisten saat pindah halaman.
                if (!item.is_read) {
                    try {
                        await requestJson(
                            readOneUrlTemplate.replace('NOTIFICATION_UUID', encodeURIComponent(item.id)),
                            'POST'
                        );
                    } catch (error) {
                        // Jangan kunci navigasi jika server / jaringan gagal.
                        console.error('Gagal menandai notifikasi dibaca:', error);
                    }
                }
                window.location.assign(destination);
            }
            row.addEventListener('click', openNotification);
            row.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openNotification();
                }
            });
            notificationList.appendChild(row);
        });

        if (emptyMessage) {
            emptyMessage.hidden = visible.length > 0;
            emptyMessage.textContent = !isVisitorAuthenticated
                ? 'Masuk ke akun untuk melihat notifikasi.'
                : activeFilter === 'all'
                    ? 'Belum ada notifikasi.'
                    : 'Tidak ada notifikasi pada kategori ini.';
        }
        updateCount();
    }

    function filterNotifications(filter) {
        activeFilter = filter;
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.filter === filter);
        });
        renderNotifications();
    }

    async function loadNotifications() {
        if (!isVisitorAuthenticated || loading) return;
        loading = true;
        try {
            const data = await requestJson(notificationUrl);
            notifications = Array.isArray(data.notifications) ? data.notifications : [];
            unreadCount = Number.isFinite(Number(data.unread_count))
                ? Math.max(0, Number(data.unread_count)) : 0;
            renderNotifications();
        } catch (error) {
            // Jangan membuat data palsu atau mengubah status dibaca ketika jaringan gagal.
            console.error('Gagal memuat notifikasi pengunjung:', error);
            if (emptyMessage && notifications.length === 0) {
                emptyMessage.hidden = false;
                emptyMessage.textContent = 'Notifikasi belum dapat dimuat. Coba buka kembali lonceng.';
            }
        } finally {
            loading = false;
        }
    }

    function closeDropdowns() {
        notificationDropdown?.classList.remove('show');
        notificationBtn?.setAttribute('aria-expanded', 'false');
        accountDropdown?.classList.remove('show');
        accountBtn?.classList.remove('active');
        accountBtn?.setAttribute('aria-expanded', 'false');
    }

    hamburger?.addEventListener('click', function () {
        navMenu?.classList.toggle('show');
        navAuth?.classList.toggle('show');
    });

    notificationBtn?.addEventListener('click', function (event) {
        event.stopPropagation();
        if (!notificationDropdown) return;
        const isOpen = notificationDropdown.classList.toggle('show');
        notificationBtn.setAttribute('aria-expanded', String(isOpen));
        accountDropdown?.classList.remove('show');
        accountBtn?.classList.remove('active');
        accountBtn?.setAttribute('aria-expanded', 'false');
        if (isOpen) loadNotifications();
    });

    accountBtn?.addEventListener('click', function (event) {
        event.stopPropagation();
        if (!accountDropdown) return;
        const isOpen = accountDropdown.classList.toggle('show');
        accountBtn.classList.toggle('active', isOpen);
        accountBtn.setAttribute('aria-expanded', String(isOpen));
        notificationDropdown?.classList.remove('show');
        notificationBtn?.setAttribute('aria-expanded', 'false');
    });

    tabs.forEach(tab => tab.addEventListener('click', function () {
        filterNotifications(tab.dataset.filter);
    }));

    markAllRead?.addEventListener('click', async function () {
        if (!isVisitorAuthenticated || unreadCount === 0 || marking) return;
        marking = true;
        updateCount();
        try {
            await requestJson(readAllUrl, 'POST');
            await loadNotifications();
        } catch (error) {
            console.error('Gagal menandai semua notifikasi:', error);
        } finally {
            marking = false;
            updateCount();
        }
    });

    document.addEventListener('click', function (event) {
        if (!notificationDropdown?.contains(event.target) &&
            !notificationBtn?.contains(event.target) &&
            !accountDropdown?.contains(event.target) &&
            !accountBtn?.contains(event.target)) {
            closeDropdowns();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeDropdowns();
    });

    visitorLogoutForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const button = visitorLogoutForm.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        try {
            const response = await fetch(visitorLogoutForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                }
            });
            if (!response.ok) throw new Error('Logout gagal. Silakan coba lagi.');
            window.location.assign(@json(route('home')));
        } catch (error) {
            if (button) button.disabled = false;
            alert(error.message);
        }
    });

    filterNotifications('all');
    if (isVisitorAuthenticated) {
        loadNotifications();
        window.setInterval(function () {
            if (!document.hidden) loadNotifications();
        }, 30000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) loadNotifications();
        });
    }
});
</script>
