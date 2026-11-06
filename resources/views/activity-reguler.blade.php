@extends('layouts.app')

@section('content')
@include('components.navbar')

<section class="regular-page-heading">
    <div class="regular-page-heading-inner">
        <a href="{{ url('/') }}" class="regular-back-link">&larr; Kembali ke Beranda</a>
        <p class="regular-kicker">GOR Seni Majapahit</p>
        <h1>Activity Reguler</h1>
        <p>Pilih tanggal dan sesi penggunaan fasilitas GOR sesuai ketersediaan kuota.</p>
    </div>
</section>

<main class="regular-booking-section">
    <div class="regular-booking-container">
        <div class="regular-section-heading">
            <div>
                <span class="regular-kicker">RESERVASI ONLINE</span>
                <h2>Jadwalkan Aktivitasmu</h2>
                <p>Pilih tanggal yang tersedia, kemudian tentukan sesi penggunaan GOR.</p>
            </div>
        </div>

        @if (session('error'))
            <div class="regular-notice" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="regular-notice" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="regular-calendar-card" aria-labelledby="regular-calendar-title">
            <div class="regular-calendar-top">
                <div>
                    <h3 id="regular-calendar-title">Pilih Tanggal</h3>
                    <p>Kuota ditampilkan berdasarkan jadwal yang tersedia.</p>
                </div>
                <div class="regular-calendar-head">
                    <button type="button" class="regular-btn secondary" id="calendar-prev" aria-label="Bulan sebelumnya">&lsaquo;</button>
                    <strong id="calendar-month-label">Memuat kalender...</strong>
                    <button type="button" class="regular-btn secondary" id="calendar-next" aria-label="Bulan berikutnya">&rsaquo;</button>
                </div>
            </div>

            <div class="regular-calendar-grid" id="regular-calendar-grid">
                <div class="regular-notice regular-grid-message">Mengambil jadwal dan kuota...</div>
            </div>

            <div class="regular-legend">
                <span class="legend-available">Tanggal tersedia</span>
                <span class="legend-unavailable">Tidak tersedia / kuota habis</span>
            </div>
            <div class="regular-notice" id="calendar-message" role="status">
                Jadwal ditampilkan berdasarkan data API Activity Reguler.
            </div>
        </section>

        <section class="regular-session-card" id="regular-session-section" hidden aria-labelledby="regular-session-title">
            <div class="regular-session-heading">
                <div>
                    <span class="regular-kicker">LANGKAH 2</span>
                    <h3 id="regular-session-title">Pilih Sesi Penggunaan</h3>
                    <p id="selected-date-label">Tanggal terpilih</p>
                </div>
                <span class="regular-selected-date" id="selected-date-badge"></span>
            </div>
            <div class="regular-session-grid" id="regular-session-grid"></div>
        </section>
    </div>
</main>

{{-- Modal konfirmasi domisili --}}
<div class="regular-modal" id="modal-regular-identity" aria-hidden="true">
    <div class="regular-dialog regular-dialog-narrow" role="dialog" aria-modal="true" aria-labelledby="regular-identity-title">
        <button type="button" class="regular-close" data-close="modal-regular-identity" aria-label="Tutup">&times;</button>
        <p class="regular-kicker">LANGKAH 3</p>
        <h3 class="regular-title" id="regular-identity-title">Konfirmasi Domisili</h3>
        <p class="regular-subtitle">Apakah Anda merupakan warga Kota Mojokerto?</p>
        <div class="regular-selected" id="selected-session-label"></div>
        <div class="regular-choice">
            <button type="button" id="regular-is-resident">Ya, warga Mojokerto</button>
            <button type="button" id="regular-is-nonresident">Bukan warga Mojokerto</button>
        </div>
        <div class="regular-actions">
            <button type="button" class="regular-btn secondary" id="identity-back">Kembali</button>
        </div>
    </div>
</div>

{{-- Form warga --}}
<div class="regular-modal" id="modal-regular-resident-form" aria-hidden="true">
    <div class="regular-dialog regular-dialog-narrow" role="dialog" aria-modal="true" aria-labelledby="resident-form-title">
        <button type="button" class="regular-close" data-close="modal-regular-resident-form" aria-label="Tutup">&times;</button>
        <p class="regular-kicker">LANGKAH 4</p>
        <h3 class="regular-title" id="resident-form-title">Data Warga Kota Mojokerto</h3>
        <p class="regular-subtitle">Lengkapi identitas untuk melanjutkan reservasi.</p>
        <div class="regular-selected" id="resident-booking-summary"></div>

        <form class="regular-form" id="regular-resident-form" method="POST" action="{{ route('reguler.booking.store') }}">
            @csrf
            <input type="hidden" name="regular_slot_id" class="selected-regular-slot-id" value="">
            <input type="hidden" name="kategori" value="warga">
            <div class="regular-form-group">
                <label for="resident-nik">NIK</label>
                <input id="resident-nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" placeholder="Masukkan 16 digit NIK" required>
            </div>
            <div class="regular-form-group">
                <label for="resident-tanggal-lahir">Tanggal lahir</label>
                <input type="date" id="resident-tanggal-lahir" name="tanggal_lahir" max="{{ now()->toDateString() }}" required>
            </div>
            <div class="regular-form-group">
                <label for="resident-name">Nama lengkap</label>
                <input id="resident-name" name="nama_lengkap" autocomplete="name" placeholder="Nama sesuai identitas" required>
            </div>
            <div class="regular-form-group">
                <label for="resident-address">Alamat</label>
                <textarea id="resident-address" name="alamat" rows="3" placeholder="Alamat sesuai identitas" required></textarea>
            </div>
            <div class="regular-notice">Pemesanan akan divalidasi oleh sistem berdasarkan NIK, tanggal lahir, dan kuota sesi.</div>
            <div class="regular-actions">
                <button type="button" class="regular-btn secondary" id="resident-back">Kembali</button>
                <button type="submit" class="regular-btn primary">Lanjutkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Form non-warga --}}
<div class="regular-modal" id="modal-regular-nonresident-form" aria-hidden="true">
    <div class="regular-dialog regular-dialog-narrow" role="dialog" aria-modal="true" aria-labelledby="nonresident-form-title">
        <button type="button" class="regular-close" data-close="modal-regular-nonresident-form" aria-label="Tutup">&times;</button>
        <p class="regular-kicker">LANGKAH 4</p>
        <h3 class="regular-title" id="nonresident-form-title">Data Non-Warga</h3>
        <p class="regular-subtitle">Lengkapi identitas dan asal daerah Anda.</p>
        <div class="regular-selected" id="nonresident-booking-summary"></div>

        <form class="regular-form" id="regular-nonresident-form" method="POST" action="{{ route('reguler.booking.store') }}">
            @csrf
            <input type="hidden" name="regular_slot_id" class="selected-regular-slot-id" value="">
            <input type="hidden" name="kategori" value="luar">
            <div class="regular-form-group">
                <label for="nonresident-nik">NIK</label>
                <input id="nonresident-nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" placeholder="Masukkan 16 digit NIK" required>
            </div>
            <div class="regular-form-group">
                <label for="nonresident-tanggal-lahir">Tanggal lahir</label>
                <input type="date" id="nonresident-tanggal-lahir" name="tanggal_lahir" max="{{ now()->toDateString() }}" required>
            </div>
            <div class="regular-form-group">
                <label for="nonresident-name">Nama lengkap</label>
                <input id="nonresident-name" name="nama_lengkap" autocomplete="name" placeholder="Nama sesuai identitas" required>
            </div>
            <div class="regular-form-group">
                <label for="nonresident-origin">Kota/Kabupaten asal</label>
                <input id="nonresident-origin" name="asal" placeholder="Contoh: Kabupaten Mojokerto" required>
            </div>
            <div class="regular-notice">Pemesanan akan divalidasi oleh sistem berdasarkan NIK, tanggal lahir, dan kuota sesi.</div>
            <div class="regular-actions">
                <button type="button" class="regular-btn secondary" id="nonresident-back">Kembali</button>
                <button type="submit" class="regular-btn primary">Lanjutkan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Gunakan origin aplikasi Laravel, bukan IP lokal pengembang.
    const REGULAR_SLOTS_API = @json(url('/api/reguler/slots'));
    const REGULAR_LOGIN_URL = @json(route('pengunjung.login', ['next' => '/activity-reguler']));
    const VISITOR_AUTHENTICATED = @json(\Illuminate\Support\Facades\Auth::guard('web')->check());
    const PENDING_SLOT_KEY = 'sigoray.reguler.pending-slot';

    const SESSION_DEFINITIONS = [
        { number: 1, start: '08:00', label: '08.00–10.00' },
        { number: 2, start: '10:00', label: '10.00–12.00' },
        { number: 3, start: '12:00', label: '12.00–14.00' },
        { number: 4, start: '14:00', label: '14.00–16.00' },
        { number: 5, start: '16:00', label: '16.00–18.00' },
        { number: 6, start: '18:00', label: '18.00–20.00' }
    ];

    const modalIds = [
        'modal-regular-identity',
        'modal-regular-resident-form',
        'modal-regular-nonresident-form'
    ];

    let slots = [];
    let slotsRequestNumber = 0;
    let selectedDate = null;
    let selectedSlot = null;
    let calendarCursor = new Date();
    calendarCursor.setDate(1);

    const getElement = id => document.getElementById(id);
    // Simpan hanya slot/tanggal/kategori, tidak menyimpan NIK atau tanggal lahir.
    function savePendingSelection(slot, date, category = null) {
        const id = String(idOf(slot) ?? '');
        if (!/^[0-9a-fA-F-]{36}$/.test(id)) return;
        try {
            sessionStorage.setItem(PENDING_SLOT_KEY, JSON.stringify({ slotId: id, date, category }));
        } catch (_) { /* Mode privat mungkin menolak sessionStorage. */ }
    }

    function readPendingSelection() {
        if (!VISITOR_AUTHENTICATED) return null;
        try {
            const parsed = JSON.parse(sessionStorage.getItem(PENDING_SLOT_KEY) || 'null');
            if (!parsed || !/^[0-9a-fA-F-]{36}$/.test(String(parsed.slotId)) ||
                !/^20\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/.test(String(parsed.date))) {
                sessionStorage.removeItem(PENDING_SLOT_KEY);
                return null;
            }
            return parsed;
        } catch (_) {
            try { sessionStorage.removeItem(PENDING_SLOT_KEY); } catch (_) {}
            return null;
        }
    }

    function clearPendingSelection() {
        try { sessionStorage.removeItem(PENDING_SLOT_KEY); } catch (_) {}
    }

    const pendingSelection = readPendingSelection();
    if (pendingSelection) {
        const [year, month] = pendingSelection.date.split('-').map(Number);
        calendarCursor = new Date(year, month - 1, 1);
    }
    const openModal = id => {
        const modal = getElement(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('regular-modal-open');
    };
    const closeModal = id => {
        const modal = getElement(id);
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.regular-modal.show')) {
            document.body.classList.remove('regular-modal-open');
        }
    };
    const closeAllModals = () => modalIds.forEach(closeModal);

    function valueOf(object, keys, fallback = null) {
        for (const key of keys) {
            if (object && object[key] !== undefined && object[key] !== null) {
                return object[key];
            }
        }
        return fallback;
    }

    function numberOf(value) {
        const number = Number(value ?? 0);
        return Number.isFinite(number) ? number : 0;
    }

    const dateOf = slot => valueOf(slot, ['tanggal', 'date', 'slot_date', 'booking_date']);
    const totalOf = slot => numberOf(valueOf(slot, ['quota', 'total_kuota', 'kuota_total', 'total_quota', 'total'], 0));
    const remainingOf = slot => numberOf(valueOf(slot, ['available', 'sisa_kuota', 'kuota_tersisa', 'remaining_quota', 'remaining', 'available_quota'], 0));
    const idOf = slot => valueOf(slot, ['id', 'regular_slot_id', 'uuid']);

    function isActive(slot) {
        const rawStatus = valueOf(slot, ['status', 'status_slot', 'is_active'], 'aktif');
        const status = String(rawStatus).toLowerCase();

        const closedStatuses = ['nonaktif', 'tidak aktif', 'inactive', 'closed', 'tutup', 'false', '0', 'habis'];
        return !closedStatuses.includes(status) && rawStatus !== false && remainingOf(slot) > 0;
    }

    function formatDate(date) {
        return new Date(date + 'T00:00:00').toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function extractRecords(payload) {
        if (Array.isArray(payload)) return payload;

        const possible = payload?.data ?? payload?.slots ?? payload?.results ?? [];
        if (Array.isArray(possible)) return possible;

        // Mendukung format Laravel: { data: { data: [...] } }
        if (Array.isArray(possible?.data)) return possible.data;

        return [];
    }

    async function loadRegularSlots() {
        const requestNumber = ++slotsRequestNumber;
        const year = calendarCursor.getFullYear();
        const month = calendarCursor.getMonth() + 1;
        const grid = getElement('regular-calendar-grid');
        const message = getElement('calendar-message');
        const url = new URL(REGULAR_SLOTS_API, window.location.origin);
        url.searchParams.set('month', String(month));
        url.searchParams.set('year', String(year));

        // Jangan tampilkan sesi dari bulan yang sebelumnya dipilih.
        selectedDate = null;
        selectedSlot = null;
        getElement('regular-session-section').hidden = true;
        closeAllModals();
        getElement('calendar-month-label').textContent =
            calendarCursor.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        grid.innerHTML = '<div class="regular-notice regular-grid-message">Mengambil jadwal dan kuota...</div>';
        message.textContent = 'Mengambil jadwal Activity Reguler...';

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                throw new Error('Server mengembalikan HTTP ' + response.status);
            }

            const payload = await response.json();
            // Jika pengguna cepat berpindah bulan, abaikan respons bulan lama.
            if (requestNumber !== slotsRequestNumber) return;

            slots = extractRecords(payload);
            renderCalendar();
            if (pendingSelection &&
                pendingSelection.date.startsWith(`${year}-${String(month).padStart(2, '0')}`)) {
                const matching = slots.find(slot => String(idOf(slot)) === pendingSelection.slotId &&
                    String(dateOf(slot)).slice(0, 10) === pendingSelection.date && isActive(slot));
                clearPendingSelection();
                if (matching) {
                    const daySlots = slots.filter(slot => String(dateOf(slot)).slice(0, 10) === pendingSelection.date);
                    chooseDate(pendingSelection.date, daySlots, pendingSelection.slotId, pendingSelection.category);
                } else {
                    message.textContent = 'Sesi sebelumnya tidak tersedia lagi. Silakan pilih sesi lain.';
                }
            }
            message.textContent = slots.length
                ? 'Pilih tanggal dan sesi yang masih tersedia.'
                : 'Belum ada jadwal Activity Reguler yang tersedia untuk bulan ini.';
        } catch (error) {
            if (requestNumber !== slotsRequestNumber) return;
            slots = [];
            grid.innerHTML = '<div class="regular-notice regular-grid-message">Jadwal belum dapat dimuat. Periksa koneksi API atau format respons endpoint.</div>';
            message.textContent = error.message;
        }
    }

    function renderCalendar() {
        const grid = getElement('regular-calendar-grid');
        const year = calendarCursor.getFullYear();
        const month = calendarCursor.getMonth();

        getElement('calendar-month-label').textContent =
            calendarCursor.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

        grid.innerHTML = '';

        ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].forEach(day => {
            const heading = document.createElement('div');
            heading.className = 'regular-weekday';
            heading.textContent = day;
            grid.appendChild(heading);
        });

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let blankIndex = 0; blankIndex < firstDay; blankIndex++) {
            grid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const daySlots = slots.filter(slot => String(dateOf(slot)).slice(0, 10) === date);

            const total = daySlots.reduce((sum, slot) => sum + totalOf(slot), 0);
            const remaining = daySlots.reduce((sum, slot) => sum + remainingOf(slot), 0);
            const available = daySlots.some(isActive);
            const percentage = total > 0
                ? Math.max(0, Math.min(100, ((total - remaining) / total) * 100))
                : 0;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'regular-day';
            button.disabled = !available;
            button.setAttribute('aria-label', `${day} ${calendarCursor.toLocaleDateString('id-ID', { month: 'long' })}: ${available ? 'tersedia' : 'tidak tersedia'}`);

            if (selectedDate === date) button.classList.add('selected');

            const number = document.createElement('span');
            number.className = 'day-number';
            number.textContent = day;

            const status = document.createElement('span');
            status.className = 'day-status';
            status.textContent = daySlots.length ? (available ? 'Tersedia' : 'Kuota habis') : 'Tidak ada jadwal';

            const quota = document.createElement('span');
            quota.className = 'day-quota';
            quota.textContent = daySlots.length ? `Sisa ${remaining}/${total}` : '—';

            const progress = document.createElement('div');
            progress.className = 'regular-progress';
            const progressBar = document.createElement('span');
            progressBar.style.width = `${percentage}%`;
            progress.appendChild(progressBar);

            button.append(number, status, quota, progress);

            if (available) {
                button.addEventListener('click', () => {
                    selectedDate = date;
                    renderCalendar();
                    chooseDate(date, daySlots);
                });
            }

            grid.appendChild(button);
        }
    }

    function chooseDate(date, daySlots, restoreSlotId = null, restoreCategory = null) {
        selectedDate = date;
        selectedSlot = null;

        getElement('selected-date-label').textContent = formatDate(date);
        getElement('selected-date-badge').textContent = formatDate(date);
        getElement('regular-session-section').hidden = false;

        const grid = getElement('regular-session-grid');
        grid.innerHTML = '';

        let restoreButton = null;
        SESSION_DEFINITIONS.forEach(session => {
            const slot = daySlots.find(item => {
                const number = Number(valueOf(item, ['sesi', 'session', 'session_number', 'nomor_sesi'], 0));
                const start = String(valueOf(item, ['waktu_mulai', 'start_time', 'jam_mulai'], '')).slice(0, 5);
                return number === session.number || start === session.start;
            });

            const available = Boolean(slot && isActive(slot));
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'regular-session';
            button.disabled = !available;

            const title = document.createElement('strong');
            title.textContent = `Sesi ${session.number}`;

            const time = document.createElement('small');
            time.textContent = session.label;

            const quota = document.createElement('span');
            quota.className = 'session-quota';
            quota.textContent = slot
                ? `Sisa ${remainingOf(slot)} dari ${totalOf(slot)} kuota`
                : 'Jadwal sesi tidak tersedia';

            button.append(title, time, quota);

            if (available) {
                button.addEventListener('click', () => {
                    if (!VISITOR_AUTHENTICATED) {
                        savePendingSelection(slot, date);
                        window.location.assign(REGULAR_LOGIN_URL);
                        return;
                    }
                    selectedSlot = slot;
                    document.querySelectorAll('.selected-regular-slot-id').forEach(input => {
                        input.value = String(idOf(slot) ?? '');
                    });

                    getElement('selected-session-label').textContent =
                        `${formatDate(date)} • Sesi ${session.number} (${session.label})`;

                    getElement('resident-booking-summary').textContent =
                        `${formatDate(date)} • Sesi ${session.number} • Slot ID: ${idOf(slot) ?? 'menunggu data'}`;

                    getElement('nonresident-booking-summary').textContent =
                        `${formatDate(date)} • Sesi ${session.number} • Slot ID: ${idOf(slot) ?? 'menunggu data'}`;

                    openModal('modal-regular-identity');
                });
            }

            if (restoreSlotId && String(idOf(slot)) === String(restoreSlotId) && available) {
                restoreButton = button;
            }
            grid.appendChild(button);
        });

        if (restoreButton) {
            restoreButton.click();
            if (restoreCategory === 'warga' || restoreCategory === 'luar') {
                closeModal('modal-regular-identity');
                openModal(restoreCategory === 'warga'
                    ? 'modal-regular-resident-form'
                    : 'modal-regular-nonresident-form');
            }
        }

        getElement('regular-session-section').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    getElement('calendar-prev').addEventListener('click', () => {
        calendarCursor.setMonth(calendarCursor.getMonth() - 1);
        loadRegularSlots();
    });

    getElement('calendar-next').addEventListener('click', () => {
        calendarCursor.setMonth(calendarCursor.getMonth() + 1);
        loadRegularSlots();
    });

    getElement('regular-is-resident').addEventListener('click', () => {
        closeModal('modal-regular-identity');
        openModal('modal-regular-resident-form');
    });

    getElement('regular-is-nonresident').addEventListener('click', () => {
        closeModal('modal-regular-identity');
        openModal('modal-regular-nonresident-form');
    });

    getElement('identity-back').addEventListener('click', () => {
        closeModal('modal-regular-identity');
        getElement('regular-session-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    getElement('resident-back').addEventListener('click', () => {
        closeModal('modal-regular-resident-form');
        openModal('modal-regular-identity');
    });

    getElement('nonresident-back').addEventListener('click', () => {
        closeModal('modal-regular-nonresident-form');
        openModal('modal-regular-identity');
    });

    document.querySelectorAll('[data-close]').forEach(button => {
        button.addEventListener('click', () => closeModal(button.dataset.close));
    });

    modalIds.forEach(id => {
        const modal = getElement(id);
        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal(id);
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeAllModals();
    });

    ['regular-resident-form', 'regular-nonresident-form'].forEach(id => {
        getElement(id).addEventListener('submit', event => {
            if (!VISITOR_AUTHENTICATED) {
                event.preventDefault();
                if (selectedSlot) savePendingSelection(selectedSlot, selectedDate,
                    id === 'regular-resident-form' ? 'warga' : 'luar');
                window.location.assign(REGULAR_LOGIN_URL);
                return;
            }
            if (!selectedSlot || !idOf(selectedSlot)) {
                event.preventDefault();
                alert('Silakan pilih kembali sesi reguler yang tersedia.');
            }
        });
    });

    loadRegularSlots();
});
</script>

@include('components.footer')
@endsection
