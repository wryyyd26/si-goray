@extends('layouts.app')

@section('content')
@include('components.navbar')
<main class="booking-page">
    <div class="booking-container">
        <div class="booking-header">
            <h1>{{ $booking->status_booking === 'CONFIRMED' ? 'Tiket Reguler Terkonfirmasi' : 'Booking Reguler Tercatat' }}</h1>
            <p>{{ $booking->status_booking === 'CONFIRMED' ? 'Booking sudah dikonfirmasi.' : 'Booking berhasil disimpan tetapi belum menjadi tiket aktif.' }}</p>
        </div>
        <section class="booking-form-section">
            @if (session('success'))
                <p class="regular-notice" role="status">{{ session('success') }}</p>
            @endif
            @if (session('error'))
                <p class="regular-notice" role="alert">{{ session('error') }}</p>
            @endif

            <p><strong>ID Booking:</strong> {{ $booking->id }}</p>
            <p><strong>Jenis:</strong> Reguler</p>
            <p><strong>Status:</strong> {{ $booking->status_booking }}</p>
            <p><strong>Tanggal:</strong> {{ $booking->regularSlot?->tanggal ?? '-' }}</p>
            <p><strong>Jam:</strong> {{ $booking->regularSlot?->jam_mulai ?? '-' }} – {{ $booking->regularSlot?->jam_selesai ?? '-' }}</p>

            @if ($booking->status_booking === 'DRAFT' && !$booking->retribusi_diperlukan)
                <p>Booking warga kota tidak memerlukan pembayaran. Konfirmasikan untuk menerbitkan tiket.</p>
                <form method="POST" action="{{ route('reguler.booking.confirm-free', ['booking' => $booking->id]) }}">
                    @csrf
                    <button type="submit" class="regular-btn primary">Konfirmasi Booking Gratis</button>
                </form>
            @elseif ($booking->status_booking === 'DRAFT' && $booking->retribusi_diperlukan)
                <p>Booking memerlukan pembayaran sebelum tiket diterbitkan. <a href="{{ route('booking.payment.show', ['booking' => $booking->id]) }}">Lanjut ke pembayaran Midtrans</a>.</p>
            @elseif ($booking->status_booking === 'CONFIRMED')
                @php
                    $ticket = $booking->activeQrTicket;
                    $ticketValid = $ticket && $ticket->waktu_expired && $ticket->waktu_expired->isFuture();
                @endphp
                @if ($ticketValid)
                    <p><strong>Status tiket:</strong> {{ $ticket->status_qr }}</p>
                    <p><strong>Berlaku sampai:</strong> {{ $ticket->waktu_expired->format('d-m-Y H:i') }}</p>
                    <p><div style="text-align:center;margin:24px 0;">
                        <img
                            src="{{ route('reguler.booking.qr-image', ['booking' => $booking->id]) }}"
                            alt="QR tiket masuk GOR untuk booking reguler"
                            width="380"
                            height="380"
                            loading="eager"
                            decoding="async"
                            referrerpolicy="no-referrer"
                            style="display:block;max-width:100%;height:auto;margin:0 auto;background:#fff;padding:12px;border:1px solid #e5e7eb;border-radius:12px;box-sizing:border-box;"
                        >
                        <p style="margin-top:12px;">Tunjukkan QR ini kepada petugas gerbang saat masuk GOR.</p>
                        <p style="font-size:0.9em;">Tiket bersifat sekali pakai. Jangan bagikan QR kepada orang lain.</p>
                    </div></p>
                @else
                    <p>Tiket QR tidak aktif atau sudah kedaluwarsa. Hubungi petugas untuk pemeriksaan.</p>
                @endif
            @else
                <p>Booking tidak dapat dilanjutkan dari status saat ini.</p>
            @endif

            @if ($booking->status_booking !== 'CONFIRMED')
                <p>Booking belum dapat digunakan masuk GOR sebelum status menjadi CONFIRMED.</p>
            @endif
            <a href="{{ route('activity.reguler') }}">Kembali ke kalender reguler</a>
        </section>
    </div>
</main>
@include('components.footer')
@endsection
