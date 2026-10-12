@extends('layouts.admin')

@section('title', 'Kelola Pemesanan')
@section('page', 'Kelola Pemesanan')

@section('content')

    <div class="booking-page">

        {{-- HEADER --}}
        <div class="booking-page-header">
            <div>
                <div class="booking-breadcrumb">
                    Pemesanan
                    <span class="material-symbols-rounded">
                        chevron_right
                    </span>
                    Kelola Pemesanan
                </div>

                <h1>Kelola Pemesanan</h1>

                <p>
                    Pantau seluruh booking reguler dan event,
                    pembayaran, tiket QR, serta riwayat akses.
                </p>
            </div>
        </div>

        {{-- STATISTICS --}}
        <div class="booking-stats">

            <div class="booking-stat-card">
                <div class="booking-stat-icon red">
                    <span class="material-symbols-rounded">
                        receipt_long
                    </span>
                </div>

                <div>
                    <span>Total Booking</span>
                    <strong>
                        {{ number_format($statistics['total']) }}
                    </strong>
                </div>
            </div>

            <div class="booking-stat-card">
                <div class="booking-stat-icon gold">
                    <span class="material-symbols-rounded">
                        calendar_today
                    </span>
                </div>

                <div>
                    <span>Booking Reguler</span>
                    <strong>
                        {{ number_format($statistics['regular']) }}
                    </strong>
                </div>
            </div>

            <div class="booking-stat-card">
                <div class="booking-stat-icon green">
                    <span class="material-symbols-rounded">
                        event
                    </span>
                </div>

                <div>
                    <span>Booking Event</span>
                    <strong>
                        {{ number_format($statistics['event']) }}
                    </strong>
                </div>
            </div>

            <div class="booking-stat-card">
                <div class="booking-stat-icon navy">
                    <span class="material-symbols-rounded">
                        payments
                    </span>
                </div>

                <div>
                    <span>Sudah Bayar</span>
                    <strong>
                        {{ number_format($statistics['paid']) }}
                    </strong>
                </div>
            </div>

        </div>

        {{-- FILTER --}}
        <div class="dashboard-card booking-filter-card">

            <div class="booking-filter-header">
                <div>
                    <h3>
                        <span class="material-symbols-rounded">
                            filter_alt
                        </span>
                        Filter Pemesanan
                    </h3>

                    <p>
                        Gunakan filter untuk menemukan booking tertentu.
                    </p>
                </div>

                <a href="{{ route('admin.pemesanan.index') }}" class="booking-reset-btn">
                    <span class="material-symbols-rounded">
                        restart_alt
                    </span>
                    Reset
                </a>
            </div>

            <form method="GET" action="{{ route('admin.pemesanan.index') }}" class="booking-filter-form">

                <div class="booking-filter-group search">
                    <label for="search">
                        Cari Booking
                    </label>

                    <div class="booking-input-wrapper">
                        <span class="material-symbols-rounded">
                            search
                        </span>

                        <input type="text" id="search" name="search" value="{{ $filters['search'] }}"
                            placeholder="ID booking atau nama pemesan...">
                    </div>
                </div>

                <div class="booking-filter-group">
                    <label for="type">
                        Jenis
                    </label>

                    <select id="type" name="type" class="booking-select">
                        <option value="all" {{ $filters['type'] === 'all' ? 'selected' : '' }}>
                            Semua
                        </option>

                        <option value="regular" {{ $filters['type'] === 'regular' ? 'selected' : '' }}>
                            Reguler
                        </option>

                        <option value="event" {{ $filters['type'] === 'event' ? 'selected' : '' }}>
                            Event
                        </option>
                    </select>
                </div>

                <div class="booking-filter-group">
                    <label for="payment_status">
                        Pembayaran
                    </label>

                    <select id="payment_status" name="payment_status" class="booking-select">
                        <option value="all">Semua</option>

                        <option value="free" {{ $filters['payment_status'] === 'free' ? 'selected' : '' }}>
                            Gratis
                        </option>

                        <option value="paid" {{ $filters['payment_status'] === 'paid' ? 'selected' : '' }}>
                            Sudah Bayar
                        </option>

                        <option value="pending" {{ $filters['payment_status'] === 'pending' ? 'selected' : '' }}>
                            Menunggu
                        </option>

                        <option value="failed" {{ $filters['payment_status'] === 'failed' ? 'selected' : '' }}>
                            Gagal
                        </option>
                    </select>
                </div>

                <div class="booking-filter-group">
                    <label for="qr_status">
                        QR
                    </label>

                    <select id="qr_status" name="qr_status" class="booking-select">
                        <option value="all">Semua</option>

                        <option value="active" {{ $filters['qr_status'] === 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="used" {{ $filters['qr_status'] === 'used' ? 'selected' : '' }}>
                            Sudah Digunakan
                        </option>

                        <option value="none" {{ $filters['qr_status'] === 'none' ? 'selected' : '' }}>
                            Belum Ada
                        </option>
                    </select>
                </div>

                <div class="booking-filter-group">
                    <label for="visit_date">
                        Tanggal Kunjungan
                    </label>

                    <input type="date" id="visit_date" name="visit_date" value="{{ $filters['visit_date'] }}"
                        class="booking-date-input">
                </div>

                <div class="booking-filter-submit">
                    <button type="submit" class="booking-btn booking-btn-primary">
                        <span class="material-symbols-rounded">
                            search
                        </span>
                        Terapkan
                    </button>
                </div>

            </form>
        </div>

        {{-- TABLE --}}
        <div class="dashboard-card booking-table-card">

            <div class="booking-table-header">
                <div>
                    <h3>
                        <span class="material-symbols-rounded">
                            list_alt
                        </span>
                        Daftar Pemesanan
                    </h3>

                    <p>
                        {{ $bookings->count() }} data ditampilkan
                    </p>
                </div>
            </div>

            <div class="booking-table-wrapper">

                <table class="booking-table">

                    <thead>
                        <tr>
                            <th>ID Booking</th>
                            <th>Pemesan</th>
                            <th>Jenis</th>
                            <th>Jadwal Kunjungan</th>
                            <th>Pembayaran</th>
                            <th>QR</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($bookings as $booking)
                            <tr>

                                {{-- ID --}}
                                <td>
                                    <span class="booking-code">
                                        #{{ $booking['booking_code'] }}
                                    </span>
                                </td>

                                {{-- USER --}}
                                <td>
                                    <div class="booking-user">
                                        <div class="booking-avatar">
                                            {{ strtoupper(substr($booking['user_name'], 0, 1)) }}
                                        </div>

                                        <div>
                                            <strong>
                                                {{ $booking['user_name'] }}
                                            </strong>

                                            <small>
                                                {{ $booking['is_warga_kota'] ? 'Warga Kota' : 'Non-Warga' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                {{-- TYPE --}}
                                <td>
                                    @if ($booking['type'] === 'regular')
                                        <span class="booking-badge regular">
                                            <span class="material-symbols-rounded">
                                                calendar_today
                                            </span>
                                            Reguler
                                        </span>
                                    @else
                                        <span class="booking-badge event">
                                            <span class="material-symbols-rounded">
                                                event
                                            </span>
                                            Event
                                        </span>
                                    @endif
                                </td>

                                {{-- SCHEDULE --}}
                                <td>
                                    <div class="booking-schedule">
                                        <strong>
                                            {{ $booking['schedule_title'] }}
                                        </strong>

                                        <span>
                                            {{ $booking['schedule_detail'] }}
                                        </span>
                                    </div>
                                </td>

                                {{-- PAYMENT --}}
                                <td>

                                    @if ($booking['payment_status'] === 'free')
                                        <span class="booking-status success">
                                            <span class="material-symbols-rounded">
                                                check_circle
                                            </span>
                                            Gratis
                                        </span>
                                    @elseif($booking['payment_status'] === 'paid')
                                        <span class="booking-status success">
                                            <span class="material-symbols-rounded">
                                                check_circle
                                            </span>
                                            Sudah Bayar
                                        </span>
                                    @elseif($booking['payment_status'] === 'pending')
                                        <span class="booking-status warning">
                                            <span class="material-symbols-rounded">
                                                schedule
                                            </span>
                                            Menunggu
                                        </span>
                                    @else
                                        <span class="booking-status danger">
                                            <span class="material-symbols-rounded">
                                                error
                                            </span>
                                            Gagal
                                        </span>
                                    @endif

                                </td>

                                {{-- QR --}}
                                <td>

                                    @if ($booking['qr_status'] === 'active')
                                        <span class="booking-status success">
                                            <span class="material-symbols-rounded">
                                                qr_code_2
                                            </span>
                                            Aktif
                                        </span>
                                    @elseif($booking['qr_status'] === 'used')
                                        <span class="booking-status info">
                                            <span class="material-symbols-rounded">
                                                verified
                                            </span>
                                            Digunakan
                                        </span>
                                    @else
                                        <span class="booking-status muted">
                                            <span class="material-symbols-rounded">
                                                remove
                                            </span>
                                            Belum Ada
                                        </span>
                                    @endif

                                </td>

                                {{-- CREATED --}}
                                <td>
                                    <div class="booking-created">
                                        {{ \Carbon\Carbon::parse($booking['created_at'])->format('d M Y') }}

                                        <small>
                                            {{ \Carbon\Carbon::parse($booking['created_at'])->format('H:i') }}
                                        </small>
                                    </div>
                                </td>

                                {{-- ACTION --}}
                                <td>
                                    <a href="{{ route('admin.payment.show', $booking['booking_code']) }}"
                                        class="booking-action-btn">
                                        <span class="material-symbols-rounded">
                                            visibility
                                        </span>
                                        Detail
                                    </a>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="booking-empty">
                                    <span class="material-symbols-rounded">
                                        search_off
                                    </span>

                                    <strong>
                                        Data pemesanan tidak ditemukan
                                    </strong>

                                    <span>
                                        Coba ubah kata kunci atau filter pencarian.
                                    </span>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>
        </div>

    </div>

@endsection
