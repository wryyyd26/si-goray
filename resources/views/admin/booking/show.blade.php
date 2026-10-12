@extends('layouts.admin')

@section('title', 'Detail Pemesanan')
@section('page', 'Kelola Pemesanan')

@section('content')

    <div class="booking-page">

        {{-- HEADER --}}
        <div class="booking-detail-header">

            <div>
                <div class="booking-breadcrumb">
                    Kelola Pemesanan

                    <span class="material-symbols-rounded">
                        chevron_right
                    </span>

                    Detail Booking

                    <span class="material-symbols-rounded">
                        chevron_right
                    </span>

                    {{ $booking['booking_code'] }}
                </div>

                <h1>
                    Detail Pemesanan
                </h1>

                <p>
                    Informasi lengkap booking,
                    pembayaran, QR ticket, dan riwayat akses.
                </p>
            </div>

            <a href="{{ route('admin.pemesanan.index') }}" class="booking-btn booking-btn-secondary">
                <span class="material-symbols-rounded">
                    arrow_back
                </span>
                Kembali
            </a>

        </div>

        {{-- BOOKING SUMMARY --}}
        <div class="dashboard-card booking-detail-summary">

            <div class="booking-detail-main">

                <div class="booking-detail-icon">
                    <span class="material-symbols-rounded">
                        receipt_long
                    </span>
                </div>

                <div>
                    <span class="booking-detail-label">
                        ID Booking
                    </span>

                    <h2>
                        #{{ $booking['booking_code'] }}
                    </h2>

                    <span class="booking-detail-created">
                        Dibuat
                        {{ \Carbon\Carbon::parse($booking['created_at'])->translatedFormat('d F Y, H:i') }}
                    </span>
                </div>

            </div>

            <div class="booking-detail-summary-right">

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

                @if ($booking['payment_status'] === 'free')
                    <span class="booking-status success">
                        Gratis
                    </span>
                @elseif($booking['payment_status'] === 'paid')
                    <span class="booking-status success">
                        Pembayaran Berhasil
                    </span>
                @elseif($booking['payment_status'] === 'pending')
                    <span class="booking-status warning">
                        Menunggu Pembayaran
                    </span>
                @else
                    <span class="booking-status danger">
                        Pembayaran Gagal
                    </span>
                @endif

            </div>

        </div>

        <div class="booking-detail-grid">

            {{-- LEFT --}}
            <div class="booking-detail-left">

                {{-- USER --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    person
                                </span>
                                Data Pemesan
                            </h3>

                            <p>
                                Informasi user dan identitas terkait booking.
                            </p>
                        </div>
                    </div>

                    <div class="booking-info-grid">

                        <div class="booking-info-item">
                            <span>Nama</span>
                            <strong>
                                {{ $booking['user_name'] }}
                            </strong>
                        </div>

                        <div class="booking-info-item">
                            <span>User ID</span>
                            <strong>
                                #{{ $booking['user_id'] }}
                            </strong>
                        </div>

                        <div class="booking-info-item">
                            <span>NIK</span>
                            <strong class="booking-mono">
                                {{ $booking['nik_masked'] }}
                            </strong>
                        </div>

                        <div class="booking-info-item">
                            <span>Status Warga</span>

                            @if ($booking['is_warga_kota'])
                                <span class="booking-status success">
                                    Warga Kota
                                </span>
                            @else
                                <span class="booking-status warning">
                                    Non-Warga
                                </span>
                            @endif
                        </div>

                    </div>

                </div>

                {{-- SCHEDULE --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    event_available
                                </span>
                                Jadwal Kunjungan
                            </h3>

                            <p>
                                Jadwal yang dipilih pada booking.
                            </p>
                        </div>
                    </div>

                    <div class="booking-schedule-detail">

                        <div class="booking-schedule-icon">
                            <span class="material-symbols-rounded">
                                {{ $booking['type'] === 'regular' ? 'calendar_today' : 'event' }}
                            </span>
                        </div>

                        <div>

                            <span class="booking-schedule-type">
                                {{ $booking['type'] === 'regular' ? 'Regular Slot' : 'Event Schedule' }}
                            </span>

                            <h3>
                                {{ $booking['schedule_title'] }}
                            </h3>

                            <p>
                                {{ $booking['schedule_detail'] }}
                            </p>

                            <div class="booking-relation-id">

                                @if ($booking['regular_slot_id'])
                                    <span>
                                        regular_slot_id:
                                        <strong>
                                            {{ $booking['regular_slot_id'] }}
                                        </strong>
                                    </span>
                                @endif

                                @if ($booking['event_schedule_id'])
                                    <span>
                                        event_schedule_id:
                                        <strong>
                                            {{ $booking['event_schedule_id'] }}
                                        </strong>
                                    </span>
                                @endif

                            </div>

                        </div>

                    </div>

                </div>

                {{-- PAYMENT --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    payments
                                </span>
                                Riwayat Pembayaran
                            </h3>

                            <p>
                                Riwayat payment transaction untuk booking ini.
                            </p>
                        </div>
                    </div>

                    @if ($booking['payment_status'] === 'free')

                        <div class="booking-free-payment">
                            <span class="material-symbols-rounded">
                                check_circle
                            </span>

                            <div>
                                <strong>
                                    Tidak memerlukan pembayaran
                                </strong>

                                <span>
                                    Booking ini termasuk kunjungan gratis.
                                </span>
                            </div>
                        </div>
                    @elseif(count($booking['payment_transactions']) > 0)
                        <div class="booking-history-table">

                            <table>

                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Metode</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($booking['payment_transactions'] as $payment)
                                        <tr>

                                            <td>
                                                <span class="booking-code">
                                                    {{ $payment['code'] }}
                                                </span>
                                            </td>

                                            <td>
                                                {{ $payment['method'] }}
                                            </td>

                                            <td>
                                                Rp {{ number_format($payment['amount'], 0, ',', '.') }}
                                            </td>

                                            <td>

                                                @if ($payment['status'] === 'paid')
                                                    <span class="booking-status success">
                                                        Berhasil
                                                    </span>
                                                @elseif($payment['status'] === 'pending')
                                                    <span class="booking-status warning">
                                                        Pending
                                                    </span>
                                                @else
                                                    <span class="booking-status danger">
                                                        Gagal
                                                    </span>
                                                @endif

                                            </td>

                                            <td>
                                                {{ \Carbon\Carbon::parse($payment['created_at'])->format('d M Y H:i') }}
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @endif

                </div>

            </div>

            {{-- RIGHT --}}
            <div class="booking-detail-right">

                {{-- PAYMENT SUMMARY --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    account_balance_wallet
                                </span>
                                Ringkasan Pembayaran
                            </h3>
                        </div>
                    </div>

                    <div class="booking-payment-summary">

                        <span>Total</span>

                        <strong>
                            @if ($booking['amount'] === 0)
                                Gratis
                            @else
                                Rp {{ number_format($booking['amount'], 0, ',', '.') }}
                            @endif
                        </strong>

                    </div>

                    <div class="booking-payment-status-box">

                        @if ($booking['payment_status'] === 'free')
                            <span class="material-symbols-rounded">
                                check_circle
                            </span>

                            <div>
                                <strong>
                                    Gratis
                                </strong>

                                <span>
                                    Tidak ada transaksi pembayaran.
                                </span>
                            </div>
                        @elseif($booking['payment_status'] === 'paid')
                            <span class="material-symbols-rounded">
                                verified
                            </span>

                            <div>
                                <strong>
                                    Pembayaran Berhasil
                                </strong>

                                <span>
                                    Transaksi terakhir telah berhasil.
                                </span>
                            </div>
                        @elseif($booking['payment_status'] === 'pending')
                            <span class="material-symbols-rounded">
                                schedule
                            </span>

                            <div>
                                <strong>
                                    Menunggu Pembayaran
                                </strong>

                                <span>
                                    Belum terdapat transaksi berhasil.
                                </span>
                            </div>
                        @else
                            <span class="material-symbols-rounded">
                                error
                            </span>

                            <div>
                                <strong>
                                    Pembayaran Gagal
                                </strong>

                                <span>
                                    Transaksi terakhir gagal.
                                </span>
                            </div>
                        @endif

                    </div>

                </div>

                {{-- QR --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    qr_code_2
                                </span>
                                QR Ticket
                            </h3>

                            <p>
                                Status dan riwayat QR ticket.
                            </p>
                        </div>
                    </div>

                    @if (count($booking['qr_tickets']) > 0)

                        <div class="booking-qr-list">

                            @foreach ($booking['qr_tickets'] as $qr)
                                <div class="booking-qr-item">

                                    <div class="booking-qr-icon">
                                        <span class="material-symbols-rounded">
                                            qr_code_2
                                        </span>
                                    </div>

                                    <div class="booking-qr-content">

                                        <strong>
                                            {{ $qr['code'] }}
                                        </strong>

                                        <span>
                                            Berlaku sampai
                                            {{ $qr['valid_until'] }}
                                        </span>

                                        @if ($qr['regenerated_from'])
                                            <small>
                                                Regenerasi dari
                                                {{ $qr['regenerated_from'] }}
                                            </small>
                                        @endif

                                    </div>

                                    @if ($qr['status'] === 'active')
                                        <span class="booking-status success">
                                            Aktif
                                        </span>
                                    @elseif($qr['status'] === 'used')
                                        <span class="booking-status info">
                                            Digunakan
                                        </span>
                                    @else
                                        <span class="booking-status danger">
                                            Invalid
                                        </span>
                                    @endif

                                </div>
                            @endforeach

                        </div>
                    @else
                        <div class="booking-no-data">
                            <span class="material-symbols-rounded">
                                qr_code_2
                            </span>

                            <span>
                                QR ticket belum tersedia.
                            </span>
                        </div>

                    @endif

                </div>

                {{-- ACCESS LOG --}}
                <div class="dashboard-card booking-detail-card">

                    <div class="booking-detail-card-header">
                        <div>
                            <h3>
                                <span class="material-symbols-rounded">
                                    door_front
                                </span>
                                Riwayat Akses
                            </h3>

                            <p>
                                Catatan scan QR di gerbang.
                            </p>
                        </div>
                    </div>

                    @if (count($booking['access_logs']) > 0)

                        <div class="booking-access-list">

                            @foreach ($booking['access_logs'] as $log)
                                <div class="booking-access-item">

                                    <div class="booking-access-icon success">
                                        <span class="material-symbols-rounded">
                                            check
                                        </span>
                                    </div>

                                    <div>
                                        <strong>
                                            Akses diterima
                                        </strong>

                                        <span>
                                            {{ $log['scanned_at'] }}
                                        </span>

                                        <small>
                                            Petugas:
                                            {{ $log['officer'] }}
                                        </small>
                                    </div>

                                </div>
                            @endforeach

                        </div>
                    @else
                        <div class="booking-no-data">
                            <span class="material-symbols-rounded">
                                history
                            </span>

                            <span>
                                Belum ada riwayat scan.
                            </span>
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

@endsection
