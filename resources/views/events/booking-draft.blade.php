@extends('layouts.app')

@section('content')
@include('components.navbar')
<main class="booking-page">
    <div class="booking-container">
        <div class="booking-header">
            <h1>Booking Tercatat</h1>
            <p>Booking telah dibuat, tetapi belum menjadi tiket aktif.</p>
        </div>
        <section class="booking-form-section">
            <p><strong>ID Booking:</strong> {{ $booking->id }}</p>
            <p><strong>Status:</strong> {{ $booking->status_booking }}</p>
            <p><strong>Event:</strong> {{ $booking->eventSchedule?->event?->nama_event ?? 'Event' }}</p>
            <p><strong>Jadwal:</strong> {{ $booking->eventSchedule?->tanggal?->format('d-m-Y') ?? '-' }} {{ $booking->eventSchedule?->jam_mulai ?? '' }}</p>
            @if(session('success')) <p role="status">{{ session('success') }}</p> @endif
            @if(session('error')) <p role="alert">{{ session('error') }}</p> @endif
            @if($booking->status_booking === 'CONFIRMED')
                @php $ticket = $booking->activeQrTicket; @endphp
                @if($ticket && $ticket->waktu_expired && $ticket->waktu_expired->isFuture())
                    <p><strong>Status QR:</strong> {{ $ticket->status_qr }}</p>
                    <p>Berlaku sampai {{ $ticket->waktu_expired->format('d-m-Y H:i') }}</p>
                    <div style="text-align:center;margin:24px 0;"><img src="{{ route('events.booking.qr-image', ['booking' => $booking->id]) }}" alt="QR tiket event" width="380" height="380" style="max-width:100%;height:auto;background:white;padding:12px;border:1px solid #e5e7eb;border-radius:12px;"><p>Jangan bagikan QR. Tiket hanya untuk satu kali masuk.</p></div>
                @else
                    <p>QR tidak aktif atau kedaluwarsa. Hubungi petugas.</p>
                @endif
            @elseif ($booking->retribusi_diperlukan)
                <p>Retribusi diperlukan. <a href="{{ route('booking.payment.show', ['booking' => $booking->id]) }}">Lanjut ke pembayaran Midtrans</a>.</p>
            @else
                <p>Booking gratis. Konfirmasi untuk menerbitkan tiket.</p><form method="POST" action="{{ route('events.booking.confirm-free', ['booking' => $booking->id]) }}">@csrf <button type="submit" class="regular-btn primary">Konfirmasi Booking Gratis</button></form>
            @endif
            <p>Jangan menganggap booking ini sudah terkonfirmasi sebelum status menjadi CONFIRMED.</p>
            <a href="{{ route('events.index') }}">Kembali ke daftar event</a>
        </section>
    </div>
</main>
@include('components.footer')
@endsection
