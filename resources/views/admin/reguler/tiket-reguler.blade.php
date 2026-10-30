@extends('layouts.admin')

@section('title', 'Tiket Reguler')
@section('page', 'Tiket Reguler')

@section('content')
    @php
        // Normalisasi data sesi dari controller untuk komponen tampilan Admin.
        $selectedSlots = collect($selectedSlots ?? []);
        $selectedDate = $selectedDate ?? request('date');
        $modalSlot = $selectedSlots->firstWhere('id', old('slot_id')) ?? $selectedSlots->first();
        $addSessionHasErrors = $errors->any() && old('_form') === 'add_session';
    @endphp

    <div class="regular-page">
        <div class="regular-page-header">
            <div>
                <h1>Kelola Tiket Reguler</h1>
                <p>Pantau kapasitas tiket reguler dan atur kuota setiap sesi berdasarkan tanggal.</p>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="regular-alert regular-alert-success" role="status">
                <span class="material-symbols-rounded">check_circle</span>
                <div><strong>Berhasil</strong>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="regular-alert regular-alert-error" role="alert">
                <span class="material-symbols-rounded">error</span>
                <div><strong>Perubahan gagal</strong>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- RINGKASAN BULAN --}}
        <div class="regular-summary-grid">
            <div class="regular-summary-card regular-card-red">
                <div class="regular-summary-top">
                    <span class="regular-summary-icon"><span
                            class="material-symbols-rounded">confirmation_number</span></span>
                    <span class="regular-summary-label">Total Kuota Bulan Ini</span>
                </div>
                <strong>{{ number_format($totalQuota ?? 0, 0, ',', '.') }}</strong>
                <p>Akumulasi kuota slot pada {{ $calendarMonthLabel ?? '' }}</p>
            </div>

            <div class="regular-summary-card regular-card-green">
                <div class="regular-summary-top">
                    <span class="regular-summary-icon"><span class="material-symbols-rounded">groups</span></span>
                    <span class="regular-summary-label">Sudah Terpakai</span>
                </div>
                <strong>{{ number_format($totalUsed ?? 0, 0, ',', '.') }}</strong>
                <p>Total tiket terpakai pada bulan ini</p>
            </div>

            <div class="regular-summary-card regular-card-blue">
                <div class="regular-summary-top">
                    <span class="regular-summary-icon"><span class="material-symbols-rounded">event_available</span></span>
                    <span class="regular-summary-label">Sisa Kuota</span>
                </div>
                <strong>{{ number_format($totalRemaining ?? 0, 0, ',', '.') }}</strong>
                <p>Kuota yang masih tersedia pada bulan ini</p>
            </div>
        </div>

        {{-- KALENDER BULANAN --}}
        <div class="regular-table-card regular-calendar-card">
            <div class="regular-table-header regular-calendar-header">
                <div class="regular-table-title">
                    <div class="regular-title-icon">
                        <span class="material-symbols-rounded">calendar_month</span>
                    </div>
                    <div>
                        <h2>Kalender Tiket Reguler</h2>
                        <p>Pilih tanggal untuk melihat detail kuota dan mengatur sesi.</p>
                    </div>
                </div>

                <nav class="regular-calendar-navigation" aria-label="Navigasi bulan kalender">
                    <a href="{{ route('admin.reguler.index', ['month' => $previousMonth]) }}"
                        class="regular-calendar-nav-button" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </a>

                    <span class="regular-calendar-month-label">{{ $calendarMonthLabel }}</span>

                    <a href="{{ route('admin.reguler.index', ['month' => $nextMonth]) }}"
                        class="regular-calendar-nav-button" aria-label="Bulan berikutnya" title="Bulan berikutnya">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </a>

                    <a href="{{ route('admin.reguler.index', ['month' => now()->format('Y-m')]) }}"
                        class="regular-calendar-current-link">
                        Bulan ini
                    </a>
                </nav>
            </div>

            <div class="regular-calendar-content">
                <div class="regular-calendar-legend" aria-label="Keterangan status kalender">
                    <span><i class="regular-calendar-legend-dot is-available"></i>Tersedia</span>
                    <span><i class="regular-calendar-legend-dot is-warning"></i>Hampir habis</span>
                    <span><i class="regular-calendar-legend-dot is-full"></i>Habis</span>
                    <span><i class="regular-calendar-legend-dot is-inactive"></i>Nonaktif</span>
                    <span><i class="regular-calendar-legend-dot is-unconfigured"></i>Belum diatur</span>
                </div>

                <div class="regular-calendar-scroll">
                    <div class="regular-calendar-weekdays" aria-hidden="true">
                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                            <div class="regular-calendar-weekday">{{ $weekday }}</div>
                        @endforeach
                    </div>

                    <div class="regular-calendar-grid">
                        @foreach ($calendarCells as $cell)
                            @if ($cell === null)
                                <div class="regular-calendar-placeholder" aria-hidden="true"></div>
                            @else
                                @php
                                    $cellSlots = collect($cell['slots'] ?? []);
                                    $cellSelected = $cell['date'] === $selectedDate;
                                    $cellIsToday = $cell['date'] === now()->toDateString();

                                    $cellQuota = $cellSlots->sum(fn($item) => (int) ($item['quota'] ?? 0));
                                    $cellUsed = $cellSlots->sum(fn($item) => (int) ($item['used'] ?? 0));
                                    $cellRemaining = max($cellQuota - $cellUsed, 0);
                                    $cellPercentage =
                                        $cellQuota > 0 ? min((int) round(($cellUsed / $cellQuota) * 100), 100) : 0;

                                    if ($cellSlots->isEmpty()) {
                                        $cellStatusLabel = 'Belum diatur';
                                        $cellStatusClass = 'unconfigured';
                                    } elseif ($cellSlots->every(fn($item) => ($item['status'] ?? '') === 'inactive')) {
                                        $cellStatusLabel = 'Nonaktif';
                                        $cellStatusClass = 'inactive';
                                    } elseif ($cellRemaining <= 0) {
                                        $cellStatusLabel = 'Habis';
                                        $cellStatusClass = 'full';
                                    } elseif ($cellPercentage >= 80) {
                                        $cellStatusLabel = 'Hampir habis';
                                        $cellStatusClass = 'warning';
                                    } else {
                                        $cellStatusLabel = 'Tersedia';
                                        $cellStatusClass = 'available';
                                    }
                                @endphp

                                <a href="{{ route('admin.reguler.index', ['month' => $monthKey, 'date' => $cell['date']]) }}"
                                    class="regular-calendar-day {{ $cellSlots->isNotEmpty() ? 'has-slot status-' . $cellStatusClass : 'no-slot' }} {{ $cellSelected ? 'is-selected' : '' }} {{ $cellIsToday ? 'is-today' : '' }}"
                                    aria-label="{{ \Carbon\Carbon::parse($cell['date'])->locale('id')->translatedFormat('d F Y') }} — {{ $cellStatusLabel }}"
                                    @if ($cellSelected) aria-current="date" @endif>

                                    <div class="regular-calendar-day-top">
                                        <span class="regular-calendar-day-number">{{ $cell['day'] }}</span>

                                        <div class="regular-calendar-day-top-right">
                                            @if ($cellIsToday)
                                                <span class="regular-calendar-today-label">Hari ini</span>
                                            @endif

                                            @if ($cellSlots->contains(fn($item) => ($item['status'] ?? 'active') === 'active'))
                                                <span class="calendar-status-badge active">Active</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($cellSlots->isNotEmpty())
                                        <span class="regular-calendar-cell-status status-{{ $cellStatusClass }}">
                                            {{ $cellStatusLabel }}
                                        </span>

                                        <div class="regular-calendar-day-metrics">
                                            <span><strong>{{ number_format($cellQuota, 0, ',', '.') }}</strong>
                                                kuota</span>
                                            <span>{{ number_format($cellRemaining, 0, ',', '.') }} tersisa</span>
                                        </div>

                                        <div class="regular-calendar-mini-progress" aria-hidden="true">
                                            <span class="status-{{ $cellStatusClass }}"
                                                style="width: {{ $cellPercentage }}%"></span>
                                        </div>
                                    @else
                                        <span class="regular-calendar-unconfigured">
                                            <span class="material-symbols-rounded">event_busy</span>
                                            Belum diatur
                                        </span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>


                {{-- DETAIL TANGGAL TERPILIH --}}
                <section class="regular-calendar-detail" aria-live="polite">
                    @if ($selectedDate && $selectedSlots->isNotEmpty())
                        @php
                            $selectedQuota = $selectedSlots->sum(fn($item) => (int) ($item['quota'] ?? 0));
                            $selectedUsed = $selectedSlots->sum(fn($item) => (int) ($item['used'] ?? 0));
                            $selectedRemaining = max($selectedQuota - $selectedUsed, 0);
                            $selectedPercentage =
                                $selectedQuota > 0 ? min((int) round(($selectedUsed / $selectedQuota) * 100), 100) : 0;

                            if ($selectedSlots->every(fn($item) => ($item['status'] ?? '') === 'inactive')) {
                                $selectedStatusClass = 'inactive';
                            } elseif ($selectedRemaining <= 0) {
                                $selectedStatusClass = 'full';
                            } elseif ($selectedPercentage >= 80) {
                                $selectedStatusClass = 'warning';
                            } else {
                                $selectedStatusClass = 'available';
                            }
                        @endphp

                        <div class="regular-calendar-detail-title">
                            <span class="material-symbols-rounded">event</span>
                            <div>
                                <span class="regular-calendar-overline">DETAIL SLOT HARIAN</span>
                                <h3>{{ \Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y') }}
                                </h3>
                                <p>{{ $selectedSlots->count() }} sesi terdaftar</p>
                            </div>
                        </div>

                        <div class="regular-calendar-detail-stats">
                            <div class="regular-calendar-detail-stat">
                                <span>Total kuota</span>
                                <strong>{{ number_format($selectedQuota, 0, ',', '.') }}</strong>
                            </div>
                            <div class="regular-calendar-detail-stat">
                                <span>Sudah terpakai</span>
                                <strong>{{ number_format($selectedUsed, 0, ',', '.') }}</strong>
                            </div>
                            <div class="regular-calendar-detail-stat">
                                <span>Sisa kuota</span>
                                <strong>{{ number_format($selectedRemaining, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="regular-calendar-detail-footer">
                            <div class="regular-calendar-detail-usage">
                                <div class="regular-calendar-detail-usage-heading">
                                    <span>Penggunaan kuota</span>
                                    <strong>{{ $selectedPercentage }}%</strong>
                                </div>
                                <div class="regular-calendar-detail-progress">
                                    <span class="status-{{ $selectedStatusClass }}"
                                        style="width: {{ $selectedPercentage }}%"></span>
                                </div>
                            </div>
                        </div>

                        {{-- FORM PENGATURAN TARIF (SATU KOTAK KARTU BESAR) --}}
                        <form action="{{ route('admin.reguler.tarif.update') }}" method="POST" class="regular-price-card">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="month" value="{{ $monthKey }}">
                            <input type="hidden" name="date" value="{{ $selectedDate }}">

                            <div class="regular-price-card-header">
                                <div class="regular-price-icon">
                                    <span class="material-symbols-rounded">payments</span>
                                </div>
                                <div>
                                    <h3>Pengaturan Tarif Tiket</h3>
                                    <p>Atur tarif kunjungan berdasarkan kategori warga dan non-warga.</p>
                                </div>
                            </div>

                            <div class="regular-price-body">
                                <div class="regular-price-grid">

                                    {{-- TARIF WARGA KOTA (TERKUNCI / READONLY) --}}
                                    <div class="form-group is-locked">
                                        <div class="form-group-header">
                                            <label for="warga_price">Tarif Warga Kota</label>
                                            <span class="locked-badge" title="Tidak dapat diubah oleh admin">
                                                <span class="material-symbols-rounded">lock</span>
                                                Terkunci
                                            </span>
                                        </div>

                                        <div class="price-input-wrapper readonly">
                                            <span class="price-prefix">Rp</span>
                                            <input type="number" id="warga_price" name="warga_price" value="0"
                                                readonly tabindex="-1" aria-readonly="true">
                                            <span class="price-lock-icon" aria-hidden="true">
                                                <span class="material-symbols-rounded">lock</span>
                                            </span>
                                        </div>

                                        <small>Tarif default gratis (Rp0) dan dikunci oleh sistem.</small>
                                    </div>

                                    {{-- TARIF NON-WARGA / LUAR KOTA (DAPAT DIATUR ADMIN) --}}
                                    <div class="form-group">
                                        <div class="form-group-header">
                                            <label for="non_warga_price">Tarif Non-Warga / Luar Kota</label>
                                        </div>

                                        <div class="price-input-wrapper">
                                            <span class="price-prefix">Rp</span>
                                            <input type="number" id="non_warga_price" name="non_warga_price"
                                                min="1" step="500"
                                                value="{{ old('non_warga_price', $nonWargaPrice) }}"
                                                placeholder="Masukkan tarif" required>
                                        </div>

                                        {{-- Pesan ditampilkan jika referensi tarif reguler belum aktif. --}}
                                        @if (($nonWargaPrice ?? null) === null)
                                            <small class="text-warning">
                                                Belum ada tarif reguler aktif. Silakan tentukan nominal sesuai kebijakan yang berlaku.
                                            </small>
                                        @endif

                                        <small>Tarif berbayar sesuai ketentuan admin.</small>
                                    </div>

                                </div>

                                {{-- TOMBOL SIMPAN DI DALAM KARTU --}}
                                <div class="regular-price-actions">
                                    <button type="submit" class="regular-btn regular-btn-primary">
                                        <span class="material-symbols-rounded">save</span>
                                        Simpan Tarif
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    @if ($selectedDate)
                        {{-- PENGATURAN KUOTA HARIAN --}}
@php
    $dailySchedule = [
        ['08:00', '10:00'],
        ['10:00', '12:00'],
        ['12:00', '14:00'],
        ['14:00', '16:00'],
        ['16:00', '18:00'],
        ['18:00', '20:00'],
    ];
    $dailyQuotaErrors = $errors->any()
        && old('_form') === 'daily_quota';
@endphp

<div class="regular-session-card" style="margin-bottom:18px">
    <div class="regular-session-card-header">
        <div class="regular-session-heading-left">
            <div class="regular-session-icon">
                <span class="material-symbols-rounded">groups</span>
            </div>
            <div>
                <h3>Pengaturan Kuota Harian</h3>
                <p>
                    {{ \Carbon\Carbon::parse($selectedDate)
                        ->translatedFormat('d F Y') }}
                    · Kapasitas berlaku untuk setiap sesi
                </p>
            </div>
        </div>
    </div>

    <form
        id="dailyQuotaForm"
        method="POST"
        action="{{ route('admin.reguler.kuota-harian.store') }}"
        class="regular-session-body"
    >
        @csrf

        <input type="hidden" name="_form" value="daily_quota">
        <input type="hidden" name="date" value="{{ $selectedDate }}">

        <div class="regular-price-grid">
            <div class="form-group">
                <label for="capacityPerSession">
                    Kapasitas Tribun per Sesi
                </label>
                <input
                    type="number"
                    id="capacityPerSession"
                    name="capacity_per_session"
                    value="{{ old('capacity_per_session', 200) }}"
                    min="1"
                    max="100000"
                    required
                >
                <small>
                    Contoh: 200 pengunjung untuk setiap sesi.
                </small>
            </div>

            <div class="form-group">
                <label>Pengaturan Cepat</label>
                <button
                    id="applyQuotaAllSessions"
                    type="button"
                    class="regular-btn regular-btn-secondary"
                >
                    <span class="material-symbols-rounded">done_all</span>
                    Terapkan ke 6 Sesi
                </button>
                <small>
                    Kamu masih bisa mengubah kuota tiap sesi.
                </small>
            </div>
        </div>

        <div class="regular-session-list" style="margin-top:16px">
            @foreach ($dailySchedule as $i => $time)
                @php
                    $savedSlot = $selectedSlots->first(
                        fn ($s) =>
                            substr((string) ($s['start_time'] ?? ''), 0, 5)
                                === $time[0]
                            &&
                            substr((string) ($s['end_time'] ?? ''), 0, 5)
                                === $time[1]
                    );
                    $used = (int) ($savedSlot['used'] ?? 0);
                    $quota = (int) ($savedSlot['quota'] ?? 200);
                @endphp

                <div class="regular-session-item">
                    <div class="regular-session-info">
                        <strong>Sesi {{ $i + 1 }}</strong>
                        <span>
                            {{ $time[0] }}–{{ $time[1] }} WIB
                        </span>
                        <small>
                            Terpakai: {{ $used }}
                            {{ $savedSlot ? '' : ' · Belum disimpan' }}
                        </small>
                    </div>

                    <label for="dailyQuota{{ $i }}">Kuota</label>

                    <input
                        type="number"
                        id="dailyQuota{{ $i }}"
                        name="quotas[{{ $i }}]"
                        class="js-daily-session-quota"
                        value="{{ old('quotas.'.$i, $quota) }}"
                        min="{{ $used }}"
                        max="100000"
                        style="max-width:110px"
                        required
                    >
                </div>
            @endforeach
        </div>

        <p style="margin-top:14px">
            Total alokasi tiket sehari:
            <strong id="dailyQuotaSum">0</strong>
            <small>
                (Jumlah seluruh sesi, bukan kapasitas serentak.)
            </small>
        </p>

        @if ($dailyQuotaErrors)
            <div class="regular-alert regular-alert-error" role="alert">
                <strong>Kuota belum berhasil disimpan.</strong>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="regular-price-actions" style="margin-top:18px">
            <button
                type="submit"
                class="regular-btn regular-btn-primary"
                @disabled($selectedDate < now()->toDateString())
            >
                <span class="material-symbols-rounded">save</span>
                Simpan Kuota Tanggal Ini
            </button>
        </div>
    </form>
</div>
{{-- DAFTAR SESI HARIAN --}}
                        <div class="regular-session-card">
                            {{-- HEADER KARTU + TOMBOL TAMBAH SESI --}}
                            <div class="regular-session-card-header">
                                <div class="regular-session-heading-left">
                                    <div class="regular-session-icon">
                                        <span class="material-symbols-rounded">schedule</span>
                                    </div>
                                    <div>
                                        <h3>Daftar Sesi Harian</h3>
                                        <p>Atur dan kelola waktu sesi kunjungan untuk tanggal ini.</p>
                                    </div>
                                </div>

                                <button type="button" class="regular-btn regular-btn-primary" id="addSessionBtn">
                                    <span class="material-symbols-rounded">add</span>
                                    Tambah Sesi
                                </button>
                            </div>

                            <div class="regular-session-body">
                                <div class="regular-session-list">
                                    {{-- Enam posisi sesi selalu tampil. Slot yang belum dibuat bukan slot aktif. --}}
                                    @foreach ([
                                        ['08:00', '10:00'],
                                        ['10:00', '12:00'],
                                        ['12:00', '14:00'],
                                        ['14:00', '16:00'],
                                        ['16:00', '18:00'],
                                        ['18:00', '20:00'],
                                    ] as $defaultSession)
                                        @php
                                            $session = $selectedSlots->first(function ($item) use ($defaultSession) {
                                                return substr((string) ($item['start_time'] ?? ''), 0, 5) === $defaultSession[0]
                                                    && substr((string) ($item['end_time'] ?? ''), 0, 5) === $defaultSession[1];
                                            });
                                            $sessionQuota = (int) ($session['quota'] ?? 0);
                                            $sessionUsed = (int) ($session['used'] ?? 0);
                                            $sessionRemaining = max(0, $sessionQuota - $sessionUsed);
                                            $sessionStatus = $session['status'] ?? 'inactive';
                                        @endphp
                                        <article class="regular-session-item">
                                            <div class="regular-session-info">
                                                <strong>Sesi {{ $loop->iteration }}</strong>
                                                <span>{{ $defaultSession[0] }}–{{ $defaultSession[1] }} WIB</span>
                                                @if ($session)
                                                    <small>
                                                        Kuota {{ number_format($sessionQuota, 0, ',', '.') }}
                                                        · Terpakai {{ number_format($sessionUsed, 0, ',', '.') }}
                                                        · Sisa {{ number_format($sessionRemaining, 0, ',', '.') }}
                                                    </small>
                                                @else
                                                    <small>Kuota belum diatur · Belum tersedia untuk booking</small>
                                                @endif
                                            </div>
                                            @if ($session)
                                                <span class="regular-calendar-cell-status status-{{ $sessionStatus === 'inactive' ? 'inactive' : ($sessionRemaining <= 0 ? 'full' : 'available') }}">
                                                    {{ $sessionStatus === 'inactive' ? 'Nonaktif' : ($sessionRemaining <= 0 ? 'Habis' : 'Aktif') }}
                                                </span>
                                                <button type="button"
                                                    class="regular-btn regular-btn-primary js-open-regular-session"
                                                    data-slot-id="{{ $session['id'] }}"
                                                    data-session-number="{{ $loop->iteration }}"
                                                    data-start-time="{{ $session['start_time'] }}"
                                                    data-end-time="{{ $session['end_time'] }}"
                                                    data-quota="{{ $sessionQuota }}"
                                                    data-used="{{ $sessionUsed }}"
                                                    data-status="{{ $sessionStatus }}">
                                                    <span class="material-symbols-rounded">tune</span>
                                                    Atur
                                                </button>
                                            @else
                                                <span class="regular-calendar-cell-status status-unconfigured">Belum diatur</span>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="regular-calendar-no-slot">
                            <div class="regular-calendar-no-slot-icon">
                                <span class="material-symbols-rounded">touch_app</span>
                            </div>
                            <div>
                                <span class="regular-calendar-overline">PILIH TANGGAL</span>
                                <h3>Pilih tanggal pada kalender</h3>
                                <p>Pilih tanggal untuk melihat semua sesi, total kuota, jumlah terpakai, dan sisa kuota.</p>
                            </div>
                        </div>
                    @endif

                </section>
            </div>
        </div>
    </div>

    {{-- MODAL: SATU MODAL DIPAKAI UNTUK MENGATUR SESI YANG DIPILIH --}}
    @if ($selectedDate && $selectedSlots->isNotEmpty())
        <div class="regular-modal-overlay {{ $errors->any() && ! $addSessionHasErrors && old('_form') !== 'daily_quota' ? 'is-visible' : '' }}" id="regularSlotModal"
            aria-hidden="{{ $errors->any() && ! $addSessionHasErrors && old('_form') !== 'daily_quota' ? 'false' : 'true' }}">

            <section class="regular-modal" role="dialog" aria-modal="true" aria-labelledby="regularModalTitle">

                <div class="regular-modal-header">
                    <div>
                        <span class="regular-modal-eyebrow">MANAJEMEN TIKET REGULER</span>
                        <h2 id="regularModalTitle">
                            Atur Sesi
                            <span id="regularModalSessionNumber">{{ $modalSlot['session_number'] ?? 1 }}</span>
                        </h2>
                        <p>{{ \Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y') }}</p>
                    </div>

                    <button type="button" class="regular-modal-close" id="closeRegularModal" aria-label="Tutup modal">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <form action="{{ route('admin.reguler.slots.update', ['slotId' => $modalSlot['id']]) }}"
                    data-update-url-template="{{ route('admin.reguler.slots.update', ['slotId' => '__SLOT_ID__']) }}"
                    method="POST" class="regular-modal-form" id="regularSlotForm">

                    @csrf
                    @method('PUT')

                    <input type="hidden" name="month" value="{{ $monthKey }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="slot_id" id="regularSlotId"
                        value="{{ old('slot_id', $modalSlot['id'] ?? '') }}">

                    <div class="regular-modal-notice">
                        <span class="material-symbols-rounded">info</span>
                        <p>
                            Perubahan akan diproses untuk sesi yang dipilih.
                            Pastikan jadwal dan kuota sudah sesuai sebelum menyimpan.
                        </p>
                    </div>

                    <div class="regular-modal-fields">
                        <div class="regular-modal-field">
                            <label for="regularStartTime">Jam Mulai</label>
                            <input type="time" id="regularStartTime" name="start_time"
                                value="{{ old('start_time', $modalSlot['start_time'] ?? '') }}" required>
                            @error('start_time')
                                <small class="regular-modal-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="regular-modal-field">
                            <label for="regularEndTime">Jam Selesai</label>
                            <input type="time" id="regularEndTime" name="end_time"
                                value="{{ old('end_time', $modalSlot['end_time'] ?? '') }}" required>
                            @error('end_time')
                                <small class="regular-modal-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="regular-modal-field">
                            <label for="regularQuota">Kuota Sesi</label>
                            <input type="number" id="regularQuota" name="quota"
                                min="{{ max(0, (int) ($modalSlot['used'] ?? 0)) }}"
                                value="{{ old('quota', $modalSlot['quota'] ?? 0) }}" required>

                            <small>
                                Tiket terpakai:
                                <span id="regularUsedCount">
                                    {{ number_format((int) ($modalSlot['used'] ?? 0), 0, ',', '.') }}
                                </span>
                            </small>

                            @error('quota')
                                <small class="regular-modal-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="regular-modal-field">
                            <label for="regularStatus">Status Sesi</label>
                            <select id="regularStatus" name="status" required>
                                <option value="active"
                                    {{ old('status', $modalSlot['status'] ?? 'active') === 'active' ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="inactive"
                                    {{ old('status', $modalSlot['status'] ?? 'active') === 'inactive' ? 'selected' : '' }}>
                                    Nonaktif
                                </option>
                            </select>

                            @error('status')
                                <small class="regular-modal-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="regular-modal-validation">
                            Periksa kembali isian yang masih salah sebelum menyimpan.
                        </div>
                    @endif

                    <div class="regular-modal-actions">
                        <button type="button" class="regular-btn regular-btn-secondary" id="cancelRegularModal">
                            Batal
                        </button>

                        <button type="submit" class="regular-btn regular-btn-primary">
                            <span class="material-symbols-rounded">save</span>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif


    {{-- MODAL TAMBAH SESI: menggunakan gaya modal Admin yang sama dengan tombol Atur. --}}
    @if ($selectedDate)
        <div class="regular-modal-overlay {{ $addSessionHasErrors ? 'is-visible' : '' }}"
            id="regularAddSessionModal" aria-hidden="{{ $addSessionHasErrors ? 'false' : 'true' }}">
            <section class="regular-modal" role="dialog" aria-modal="true" aria-labelledby="regularAddModalTitle">
                <div class="regular-modal-header">
                    <div>
                        <span class="regular-modal-eyebrow">MANAJEMEN TIKET REGULER</span>
                        <h2 id="regularAddModalTitle">Tambah Sesi</h2>
                        <p>{{ \Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y') }}</p>
                    </div>
                    <button type="button" class="regular-modal-close" id="closeRegularAddModal" aria-label="Tutup modal tambah sesi">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <form action="{{ route('admin.reguler.store') }}" method="POST" class="regular-modal-form" id="regularAddSessionForm">
                    @csrf
                    <input type="hidden" name="_form" value="add_session">
                    <input type="hidden" name="month" value="{{ $monthKey }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    @if ($selectedDate < now()->toDateString())
                        <div class="regular-modal-notice">
                            <span class="material-symbols-rounded">info</span>
                            <p>Tanggal ini sudah berlalu. Backend hanya menerima sesi baru untuk hari ini atau tanggal berikutnya.</p>
                        </div>
                    @endif

                    <div class="regular-modal-fields">
                        <div class="regular-modal-field">
                            <label for="newStartTime">Jam Mulai</label>
                            <input type="time" id="newStartTime" name="start_time"
                                value="{{ $addSessionHasErrors ? old('start_time') : '' }}" required>
                            @if ($addSessionHasErrors)
                                @error('start_time') <small class="regular-modal-error">{{ $message }}</small> @enderror
                            @endif
                        </div>
                        <div class="regular-modal-field">
                            <label for="newEndTime">Jam Selesai</label>
                            <input type="time" id="newEndTime" name="end_time"
                                value="{{ $addSessionHasErrors ? old('end_time') : '' }}" required>
                            @if ($addSessionHasErrors)
                                @error('end_time') <small class="regular-modal-error">{{ $message }}</small> @enderror
                            @endif
                        </div>
                        <div class="regular-modal-field">
                            <label for="newQuota">Kuota Sesi</label>
                            <input type="number" id="newQuota" name="quota" min="1"
                                value="{{ $addSessionHasErrors ? old('quota', 50) : 50 }}" required>
                            @if ($addSessionHasErrors)
                                @error('quota') <small class="regular-modal-error">{{ $message }}</small> @enderror
                            @endif
                        </div>
                    </div>

                    @if ($addSessionHasErrors)
                        <div class="regular-modal-validation">Periksa kembali isian sesi yang belum sesuai.</div>
                    @endif

                    <div class="regular-modal-actions">
                        <button type="button" class="regular-btn regular-btn-secondary" id="cancelRegularAddModal">Batal</button>
                        <button type="submit" class="regular-btn regular-btn-primary"
                            {{ $selectedDate < now()->toDateString() ? 'disabled' : '' }}>
                            <span class="material-symbols-rounded">save</span>
                            Simpan Sesi
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- POP UP DETAIL KETIKA ADMIN KLIK TANGGAL YANG ADA EVENTNYA --}}
    <div class="event-detail-modal" id="eventDetailModal" aria-hidden="true">
        <div class="event-detail-backdrop" data-close-event-modal></div>

        <section class="event-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">

            <div class="event-detail-header">
                <div class="event-detail-heading">
                    <span class="material-symbols-rounded event-detail-icon">
                        event
                    </span>

                    <div>
                        <span class="event-detail-eyebrow">INFORMASI EVENT</span>
                        <h2 id="eventModalTitle">Detail Event</h2>
                        <p id="eventModalDate">Tanggal event</p>
                    </div>
                </div>

                <button type="button" class="event-modal-close" aria-label="Tutup pop-up" data-close-event-modal>
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <div class="event-detail-body" id="eventModalBody">
                <!-- Detail event diisi melalui JavaScript -->
            </div>

            <div class="event-detail-footer">
                <span class="event-detail-note">
                    <span class="material-symbols-rounded">info</span>
                    Tanggal ini menggunakan jalur pemesanan event.
                </span>

                <button type="button" class="event-modal-button" data-close-event-modal>
                    Tutup
                </button>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('regularAddSessionModal');
    const addButton = document.getElementById('addSessionBtn');

    if (addModal && addButton) {
        const openAdd = () => {
            addModal.classList.add('is-visible');
            addModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('regular-modal-open');
        };
        const closeAdd = () => {
            addModal.classList.remove('is-visible');
            addModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('regular-modal-open');
        };

        addButton.addEventListener('click', openAdd);
        document.getElementById('closeRegularAddModal')?.addEventListener('click', closeAdd);
        document.getElementById('cancelRegularAddModal')?.addEventListener('click', closeAdd);
        addModal.addEventListener('click', (event) => {
            if (event.target === addModal) closeAdd();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && addModal.classList.contains('is-visible')) closeAdd();
        });
        if (addModal.classList.contains('is-visible')) {
            document.body.classList.add('regular-modal-open');
        }
    }

    // Action form edit mengikuti ID sesi yang diklik, bukan hanya tanggal.
    const editForm = document.getElementById('regularSlotForm');
    const editTemplate = editForm?.dataset.updateUrlTemplate;
    if (editForm && editTemplate) {
        document.querySelectorAll('.js-open-regular-session').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.slotId) {
                    editForm.action = editTemplate.replace('__SLOT_ID__', encodeURIComponent(button.dataset.slotId));
                }
            });
        });
    }
});
</script>
@endpush




@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dailyQuotaForm');
    if (!form) return;

    const capacity = document.getElementById('capacityPerSession');
    const button = document.getElementById('applyQuotaAllSessions');
    const inputs = Array.from(
        form.querySelectorAll('.js-daily-session-quota')
    );
    const total = document.getElementById('dailyQuotaSum');

    function refresh() {
        const sum = inputs.reduce(
            (value, input) => value + Number(input.value || 0), 0
        );
        total.textContent = sum.toLocaleString('id-ID');
        inputs.forEach(input => {
            input.max = capacity.value;
        });
    }

    button.addEventListener('click', function () {
        const value = Number(capacity.value);

        if (!Number.isInteger(value) || value < 1) {
            alert('Isi kapasitas tribun yang valid.');
            return;
        }

        if (inputs.some(input => value < Number(input.min || 0))) {
            alert('Kapasitas di bawah jumlah tiket terpakai.');
            return;
        }

        inputs.forEach(input => {
            input.value = String(value);
        });
        refresh();
    });

    capacity.addEventListener('input', refresh);
    inputs.forEach(input => input.addEventListener('input', refresh));

    form.addEventListener('submit', function (event) {
        const max = Number(capacity.value);
        const invalid = !Number.isInteger(max) || max < 1 ||
            inputs.some(input => {
                const value = Number(input.value);
                return !Number.isInteger(value) ||
                    value < Number(input.min || 0) ||
                    value > max;
            });

        if (invalid) {
            event.preventDefault();
            alert('Periksa kuota setiap sesi sebelum menyimpan.');
        }
    });

    refresh();
});
</script>
@endpush