@extends('layouts.admin')

@section('title', 'Detail Pembayaran')
@section('page', 'Kelola Pembayaran')

@section('content')

    @php
        /*
    |--------------------------------------------------------------------------
    | STATUS PEMBAYARAN MIDTRANS
    |--------------------------------------------------------------------------
    | Status yang dibaca adalah status lokal dari payment_transactions.
    | Backend Midtrans yang sudah ada bertanggung jawab memperbaruinya.
    */

        $status = strtoupper((string) $payment->status_pembayaran);

        $statusLabel = match ($status) {
            'SUCCESS' => 'Berhasil',
            'PENDING' => 'Menunggu Pembayaran',
            'FAILED' => 'Gagal',
            'CANCELLED' => 'Dibatalkan',
            'EXPIRED' => 'Kedaluwarsa',
            default => $status ?: 'Tidak Diketahui',
        };

        $statusClass = match ($status) {
            'SUCCESS' => 'success',
            'PENDING' => 'warning',
            default => 'danger',
        };

        $nominal = (float) $payment->nominal;

        $expiredAt = $payment->expired_at ? \Carbon\Carbon::parse($payment->expired_at)->format('d/m/Y H:i') : '-';

        $createdAt = $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') : '-';

        $bookingId = $booking->id ?? null;

        $jenisBooking = match (strtoupper((string) ($booking->jenis_booking ?? ''))) {
            'REGULER' => 'Tiket Reguler',
            'EVENT' => 'Tiket Event',
            default => 'Tidak tersedia',
        };
    @endphp

    <div class="payment-page">

        <div class="pg-bar"></div>

        {{-- HEADER --}}
        <div class="sec-bar">

            <div>

                <a href="{{ route('admin.payment.pembayaran') }}" class="payment-action" style="margin-bottom:10px;">
                    <span class="material-symbols-rounded">
                        arrow_back
                    </span>

                    Kembali
                </a>

                <div class="sec-hd">
                    Detail Pembayaran
                </div>

                <div class="sec-sub">
                    Informasi transaksi pembayaran dan registrasi terkait
                </div>

            </div>

        </div>

        <div class="payment-detail-grid">

            {{-- KOLOM UTAMA --}}
            <div>

                {{-- INFORMASI PEMBAYARAN --}}
                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                payments
                            </span>

                            Informasi Pembayaran

                        </div>

                        {{-- STATUS DINAMIS --}}
                        <span id="paymentDetailStatus" class="payment-status {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                    </div>

                    <div class="payment-detail-body">

                        {{-- KODE PEMBAYARAN --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Kode Pembayaran
                            </div>

                            <div class="payment-detail-value mono" id="detailKodeBayar">
                                {{ $payment->kode_bayar ?: '-' }}
                            </div>

                        </div>

                        {{-- NOMINAL PEMBAYARAN --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Nominal Pembayaran
                            </div>

                            <div class="payment-detail-value" id="detailNominal">
                                <strong>
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </strong>
                            </div>

                        </div>

                        {{-- METODE PEMBAYARAN --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Metode Pembayaran
                            </div>

                            <div class="payment-detail-value" id="detailMetode">
                                {{ $payment->metode_pembayaran ?: '-' }}
                            </div>

                        </div>

                        {{-- STATUS PEMBAYARAN --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Status
                            </div>

                            <div class="payment-detail-value" id="detailStatus">
                                {{ $statusLabel }}
                            </div>

                        </div>

                        {{-- TANGGAL TRANSAKSI --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Tanggal Transaksi
                            </div>

                            <div class="payment-detail-value" id="detailCreatedAt">
                                {{ $createdAt }} WIB
                            </div>

                        </div>

                        {{-- WAKTU KEDALUWARSA --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Waktu Kedaluwarsa
                            </div>

                            <div class="payment-detail-value" id="detailExpired">
                                {{ $expiredAt }}
                                @if ($expiredAt !== '-')
                                    WIB
                                @endif
                            </div>

                        </div>

                        {{-- REFERENSI MIDTRANS --}}
                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Payment Gateway Ref
                            </div>

                            <div class="payment-detail-value mono" id="detailGatewayRef">
                                {{ $payment->referensi_gateway ?: '-' }}
                            </div>

                        </div>

                    </div>

                </div>

                {{-- REGISTRASI TERKAIT --}}
                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                confirmation_number
                            </span>

                            Registrasi Terkait

                        </div>

                    </div>

                    <div class="payment-detail-body">

                        <div class="payment-related-box">

                            <div class="payment-related-main">

                                {{-- ID BOOKING ASLI --}}
                                <div class="payment-related-code" id="detailRegistration">
                                    {{ $bookingId ?? 'Booking tidak ditemukan' }}
                                </div>

                                {{-- ID PENGGUNA --}}
                                <div class="payment-related-name" id="detailVisitor">
                                    @if ($booking && $booking->user_id)
                                        ID Pengguna:
                                        {{ $booking->user_id }}
                                    @else
                                        Pengguna tidak tersedia
                                    @endif
                                </div>

                                {{-- JENIS BOOKING --}}
                                <div class="payment-related-meta" id="detailRegistrationType">
                                    {{ $jenisBooking }}
                                </div>

                            </div>

                            {{-- TOMBOL DETAIL BOOKING --}}
                            @if ($bookingId)
                                <a href="{{ route('admin.booking.show', [
                                    'booking' => $bookingId,
                                ]) }}"
                                    class="payment-related-link" id="detailRegistrationLink">
                                    Lihat Pemesanan
                                </a>
                            @else
                                <span class="payment-related-link" style="opacity:0.5;cursor:not-allowed;">
                                    Pemesanan Tidak Tersedia
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div>

                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                info
                            </span>

                            Informasi

                        </div>

                    </div>

                    <div class="payment-detail-body">

                        <div class="payment-info-notice">

                            <span class="material-symbols-rounded">
                                info
                            </span>

                            <div>
                                Status pembayaran diperoleh dari
                                payment gateway Midtrans melalui
                                callback/webhook.

                                Admin tidak melakukan verifikasi
                                pembayaran secara manual.
                            </div>

                        </div>

                    </div>

                </div>

                {{-- INFORMASI STATUS TRANSAKSI --}}
                <div class="payment-detail-card" style="margin-top:16px;">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                receipt_long
                            </span>

                            Status Transaksi

                        </div>

                    </div>

                    <div class="payment-detail-body">

                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Status Midtrans Tersimpan
                            </div>

                            <div class="payment-detail-value">
                                {{ $status ?: '-' }}
                            </div>

                        </div>

                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                Jumlah Percobaan Ulang
                            </div>

                            <div class="payment-detail-value">
                                {{ $payment->jumlah_retry ?? 0 }}
                            </div>

                        </div>

                        <div class="payment-detail-row">

                            <div class="payment-detail-label">
                                ID Transaksi
                            </div>

                            <div class="payment-detail-value mono">
                                {{ $payment->id }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
