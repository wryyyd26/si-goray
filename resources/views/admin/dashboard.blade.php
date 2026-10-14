@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page', 'Dashboard')

@section('content')
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
                <h2>1.284</h2>
                <div class="stat-trend positive">
                    <span class="material-symbols-rounded">trending_up</span>
                    <span>+34 pengguna bulan ini</span>
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
                <h2>87 <span class="text-sm fw-normal text-muted">/ 200</span></h2>
                <div class="stat-trend neutral">
                    <span>Sisa: 113 tempat</span>
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
                <h2>333</h2>
                <div class="stat-trend neutral">
                    <span>5 event berjalan</span>
                </div>
            </div>
        </div>

        {{-- Pendapatan --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Pendapatan Bulan Ini</span>
                <span class="material-symbols-rounded stat-icon">monetization_on</span>
            </div>
            <div class="stat-content">
                <h2>Rp 12,6 jt</h2>
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
                    <h3>Grafik Pemesanan 2025</h3>
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
                    <div class="legend-value success-text">748</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><span class="dot warning"></span> Menunggu</div>
                    <div class="legend-value warning-text">94</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><span class="dot danger"></span> Ditolak</div>
                    <div class="legend-value danger-text">34</div>
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
            <a href="#" class="btn-outline">
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
                    <tr>
                        <td>TRX-001</td>
                        <td>Budi Santoso</td>
                        <td>Reguler</td>
                        <td>14 Sep 2025</td>
                        <td>Rp 30.000</td>
                        <td><span class="badge badge-success">Selesai</span></td>
                        <td>10:30</td>
                    </tr>
                    <tr>
                        <td>TRX-002</td>
                        <td>Siti Aminah</td>
                        <td>Event Konser</td>
                        <td>15 Sep 2025</td>
                        <td>Rp 150.000</td>
                        <td><span class="badge badge-warning">Menunggu</span></td>
                        <td>09:15</td>
                    </tr>
                    <tr>
                        <td>TRX-003</td>
                        <td>Andi Wijaya</td>
                        <td>Reguler</td>
                        <td>14 Sep 2025</td>
                        <td>Rp 30.000</td>
                        <td><span class="badge badge-success">Selesai</span></td>
                        <td>08:45</td>
                    </tr>
                    <tr>
                        <td>TRX-004</td>
                        <td>Rina Melati</td>
                        <td>Event Turnamen</td>
                        <td>20 Sep 2025</td>
                        <td>Rp 50.000</td>
                        <td><span class="badge badge-danger">Ditolak</span></td>
                        <td>08:10</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@stack('scripts')
