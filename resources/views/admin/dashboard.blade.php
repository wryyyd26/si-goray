@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page', 'Dashboard')

@section('content')
{{-- SIGORAY_DASHBOARD_BINDINGS_V1 --}}
<script>
window.SIGORAY_DASHBOARD = @json([
    'booking' => $bookingChart,
    'payment' => $paymentCounts,
]);
</script>
<div class="payment-info-notice" style="margin-bottom:16px;">
    <span class="material-symbols-rounded">info</span>
    <div>
        Dashboard menampilkan ringkasan data yang tercatat pada sistem.
        Statistik, grafik, dan transaksi terbaru
        diperbarui saat halaman dimuat.
    </div>
</div>
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
        </div>
    </div>

    {{-- =========================
        STATISTIC CARDS
    ========================= --}}
    <div class="stats-grid">
        {{-- Total Pengguna --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Pengguna Terdaftar</span>
                <span class="material-symbols-rounded stat-icon">group</span>
            </div>
            <div class="stat-content">
                <h2>{{ number_format($totalUsers, 0, ",", ".") }}</h2>
                <div class="stat-trend positive">
                    <span class="material-symbols-rounded">trending_up</span>
                    <span>+{{ number_format($newUsersThisMonth, 0, ",", ".") }} pengguna bulan ini</span>
                </div>
            </div>
        </div>

        {{-- Booking Reguler --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Booking Reguler &mdash; Hari Ini</span>
                <span class="material-symbols-rounded stat-icon">calendar_today</span>
            </div>
            <div class="stat-content">
                <h2>{{ number_format($regularUsed, 0, ",", ".") }} <span class="text-sm fw-normal text-muted">/ {{ number_format($regularCapacity, 0, ",", ".") }}</span></h2>
                <div class="stat-trend neutral">
                    <span>Sisa: {{ number_format(max(0, $regularCapacity - $regularUsed), 0, ",", ".") }} tempat</span>
                </div>
            </div>
        </div>

        {{-- Booking Event --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Booking Tiket Event</span>
                <span class="material-symbols-rounded stat-icon">event_available</span>
            </div>
            <div class="stat-content">
                <h2>{{ number_format($eventUsed, 0, ",", ".") }}</h2>
                <div class="stat-trend neutral">
                    <span>Total kuota event yang terpakai</span>
                </div>
            </div>
        </div>

        {{-- Pendapatan --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Pembayaran Sukses (Transaksi Dibuat Bulan Ini)</span>
                <span class="material-symbols-rounded stat-icon">monetization_on</span>
            </div>
            <div class="stat-content">
                <h2>Rp {{ number_format($revenueThisMonth, 0, ",", ".") }}</h2>
                <div class="stat-trend neutral">
                    <span>Retribusi & tiket event</span>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
    CHARTS SECTION
    ========================= --}}
    <div class="charts-grid">
        {{-- Line Chart --}}
        <div class="dashboard-card line-chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <span class="material-symbols-rounded">show_chart</span>
                    <h3>Grafik Pemesanan {{ $bookingChart["year"] }}</h3>
                </div>
                <div class="chart-legend-custom">
                    <span class="legend-item"><span class="dot reguler"></span> Reguler</span>
                    <span class="legend-item"><span class="dot event"></span> Event</span>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="bookingChart"></canvas>
            </div>
        </div>

        {{-- Donut Chart --}}
        <div class="dashboard-card donut-chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <span class="material-symbols-rounded">donut_large</span>
                    <h3>Status Pembayaran</h3>
                </div>
            </div>
            <div class="chart-container donut-container">
                <canvas id="paymentChart"></canvas>
            </div>
            <div class="donut-legend">
                <div class="legend-row">
                    <div class="legend-label"><span class="dot success"></span> Selesai</div>
                    <div class="legend-value success-text">{{ number_format($paymentCounts["success"], 0, ",", ".") }}</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><span class="dot warning"></span> Menunggu</div>
                    <div class="legend-value warning-text">{{ number_format($paymentCounts["pending"], 0, ",", ".") }}</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><span class="dot danger"></span> Ditolak</div>
                    <div class="legend-value danger-text">{{ number_format($paymentCounts["failed"], 0, ",", ".") }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
        TRANSACTION TABLE
    ========================= --}}
    <div class="dashboard-card table-card">
        <div class="card-header">
            <div class="card-title-group">
                <span class="material-symbols-rounded">receipt_long</span>
                <h3>Transaksi Terbaru</h3>
            </div>
            <a href="{{ route('admin.payment.pembayaran') }}" class="btn-outline">
                <span>Semua Transaksi</span>
                <span class="material-symbols-rounded">arrow_forward</span>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>NAMA</th>
                        <th>TIKET</th>
                        <th>KUNJUNGAN</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>WAKTU</th>
                    </tr>
                </thead>
                <tbody>
    @forelse ($latestTransactions as $tx)
    <tr>
        <td>{{ $tx->kode_bayar }}</td>
        <td>{{ $tx->user_label }}</td>
        <td>{{ strtoupper($tx->jenis_booking) === 'EVENT' ? 'Event' : 'Reguler' }}</td>
        <td>{{ $tx->tanggal_kunjungan ? \Carbon\Carbon::parse($tx->tanggal_kunjungan)->format('d/m/Y') : '-' }}</td>
        <td>Rp {{ number_format((float) $tx->nominal, 0, ',', '.') }}</td>
        @php
            $paymentStatus = strtoupper((string) $tx->status_pembayaran);
            $isSuccess = $paymentStatus === 'SUCCESS';
            $isPending = $paymentStatus === 'PENDING';
        @endphp
        <td>
            <span class="badge {{ $isSuccess ? 'badge-success' : ($isPending ? 'badge-warning' : 'badge-danger') }}">
                {{ $isSuccess ? 'Selesai' : ($isPending ? 'Menunggu' : 'Ditolak') }}
            </span>
        </td>
        <td>{{ $tx->created_at ? \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') : '-' }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center">Belum ada transaksi pembayaran.</td></tr>
    @endforelse
</tbody>
            </table>
        </div>
    </div>
@endsection

@stack('scripts')
