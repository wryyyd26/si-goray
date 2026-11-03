@extends('layouts.admin')

@section('title', 'Tiket Reguler')
@section('page', 'Tiket Reguler')

@section('content')
    @php
        // Tanggal terpilih dipakai untuk penanda visual pada kalender.
        $selectedDate = $selectedDate ?? request('date');
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

                                    // kode untuk mendeteksi event
                                    $cellEvents = collect($cell['events'] ?? []);
                                    $cellHasEvent = $cellEvents->isNotEmpty();

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
                                    data-date="{{ $cell['date'] }}"
                                    data-date-label="{{ \Carbon\Carbon::parse($cell['date'])->locale('id')->translatedFormat('l, d F Y') }}"
                                    data-has-event="{{ $cellHasEvent ? '1' : '0' }}"
                                    data-slots="{{ $cellSlots->values()->toJson() }}"
                                    data-events="{{ $cellEvents->values()->toJson() }}" aria-haspopup="dialog"
                                    aria-controls="regularDetailModal"
                                    aria-label="{{ \Carbon\Carbon::parse($cell['date'])->locale('id')->translatedFormat('d F Y') }} — {{ $cellStatusLabel }}"
                                    @if ($cellSelected) aria-current="date" @endif>

                                    <div class="regular-calendar-day-top">
                                        <span class="regular-calendar-day-number">{{ $cell['day'] }}</span>

                                        <div class="regular-calendar-day-top-right">
                                            @if ($cellIsToday)
                                                <span class="regular-calendar-today-label">Hari ini</span>
                                            @endif

                                            @if ($cellHasEvent)
                                                <span class="calendar-status-badge event">Event</span>
                                            @else
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
                                        @if (!empty($cell['events']))
                                            <span class="regular-calendar-cell-status status-event">
                                                <span class="material-symbols-rounded">event</span>
                                                Lihat Event
                                            </span>
                                        @else
                                            <span class="regular-calendar-unconfigured">
                                                <span class="material-symbols-rounded">event_busy</span>
                                                Belum diatur
                                            </span>
                                        @endif
                                    @endif

                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>


                {{-- Detail tanggal sekarang ditampilkan melalui popup. --}}
            </div>
        </div>
    </div>

    {{-- POPUP DETAIL SLOT: dipakai untuk tanggal reguler maupun tanggal Event. --}}
    <div class="regular-modal-overlay regular-detail-overlay" id="regularDetailModal" aria-hidden="true">
        <section class="regular-modal regular-detail-modal" role="dialog" aria-modal="true"
            aria-labelledby="regularDetailTitle" aria-describedby="regularDetailDate">
            <div class="regular-modal-header">
                <div>
                    <span class="regular-modal-eyebrow">MANAJEMEN TIKET REGULER</span>
                    <h2 id="regularDetailTitle">Detail Slot Reguler</h2>
                    <p id="regularDetailDate">Tanggal yang dipilih</p>
                </div>
                <button type="button" class="regular-modal-close" id="closeRegularDetailModal"
                    aria-label="Tutup detail slot">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <div class="regular-detail-body">
                <div class="regular-detail-event-warning" id="regularDetailEventWarning" hidden>
                    <span class="material-symbols-rounded" aria-hidden="true">event_busy</span>
                    <div>
                        <strong>Tanggal memiliki Event</strong>
                        <p>Seluruh penjualan tiket reguler pada tanggal ini ditutup. Informasi sesi ditampilkan sebagai
                            referensi.</p>
                    </div>
                </div>

                <div class="regular-detail-stats" aria-live="polite">
                    <div class="regular-detail-stat">
                        <span>Total sesi</span>
                        <strong id="regularDetailSessionCount">0</strong>
                    </div>
                    <div class="regular-detail-stat">
                        <span>Total kuota</span>
                        <strong id="regularDetailQuota">0</strong>
                    </div>
                    <div class="regular-detail-stat">
                        <span>Tiket terjual</span>
                        <strong id="regularDetailUsed">0</strong>
                    </div>
                </div>

                <div class="regular-detail-usage">
                    <div class="regular-detail-usage-heading">
                        <span>Penggunaan kuota</span>
                        <strong id="regularDetailPercentage">0%</strong>
                    </div>
                    <div class="regular-detail-progress" aria-hidden="true">
                        <span id="regularDetailProgressBar"></span>
                    </div>
                    <small id="regularDetailRemaining">Sisa kuota: 0</small>
                </div>

                <section class="regular-detail-section" aria-labelledby="regularDetailSessionsHeading">
                    <div class="regular-detail-section-heading">
                        <div>
                            <h3 id="regularDetailSessionsHeading">Daftar Sesi</h3>
                            <p>Ringkasan jam operasional, kuota, dan status setiap sesi.</p>
                        </div>
                    </div>
                    <div class="regular-detail-session-list" id="regularDetailSessionList"></div>
                </section>

                <section class="regular-detail-section" id="regularDetailEventSection"
                    aria-labelledby="regularDetailEventHeading" hidden>
                    <div class="regular-detail-section-heading">
                        <div>
                            <h3 id="regularDetailEventHeading">Informasi Event</h3>
                            <p>Event yang berlangsung pada tanggal ini.</p>
                        </div>
                    </div>
                    <div class="regular-detail-event-list" id="regularDetailEventList"></div>
                </section>
            </div>

            <div class="regular-modal-actions regular-detail-actions">
                <button type="button" class="regular-btn regular-btn-secondary" id="closeRegularDetailFooter">
                    Tutup
                </button>
                {{-- URL tanggal akan diisi oleh JavaScript sesuai kalender yang dipilih. --}}
                <button type="button" class="regular-btn regular-btn-primary" id="regularDetailConfigure"
                    data-route-template="{{ route('admin.reguler.edit', ['date' => '__DATE__']) }}">
                    <span class="material-symbols-rounded">settings</span>
                    Atur Slot
                </button>
            </div>
        </section>
    </div>
@endsection
