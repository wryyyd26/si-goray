@extends('layouts.app')
@section('title', 'Pembayaran Booking | SI-GORAY')
@section('content')
@include('components.navbar')
<main class="payment-page">
    <header class="payment-header">
        <a href="{{ route('home') }}" class="payment-brand"><strong>SI-GORAY</strong></a>
        <div class="payment-secure">Pembayaran melalui Midtrans</div>
    </header>
    <section class="payment-intro">
        <h1>Selesaikan pembayaranmu</h1>
        <p>Pastikan informasi booking dan tarif sudah benar sebelum melanjutkan.</p>
    </section>
    @if(session('error')) <div class="payment-alert" role="alert">{{ session('error') }}</div> @endif
    <div class="payment-layout">
        <section class="order-column">
            <article class="order-card"><div class="order-card-body">
                <h2>Ringkasan booking</h2>
                <p><strong>Booking:</strong> {{ $booking->id }}</p>
                <p><strong>Jenis:</strong> {{ $booking->jenis_booking }}</p>
                @if ($booking->jenis_booking === 'REGULER')
                    <p><strong>Tanggal:</strong> {{ $booking->regularSlot?->tanggal ?? '-' }}</p>
                    <p><strong>Waktu:</strong> {{ $booking->regularSlot?->jam_mulai ?? '-' }} – {{ $booking->regularSlot?->jam_selesai ?? '-' }}</p>
                @else
                    <p><strong>Event:</strong> {{ $booking->eventSchedule?->event?->nama_event ?? 'Event' }}</p>
                    <p><strong>Tanggal:</strong> {{ $booking->eventSchedule?->tanggal ?? '-' }}</p>
                @endif
                <p><strong>Status:</strong> {{ $booking->status_booking }}</p>
                <div class="order-total"><span>Total sesuai tarif booking</span><strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong></div>
            </div></article>
        </section>
        <section class="checkout-column">
            <div class="checkout-card">
                <h2>Pembayaran aman</h2>
                <p>Setelah melanjutkan, kamu bisa memilih metode yang tersedia pada jendela Midtrans (termasuk QRIS atau transfer bank jika aktif).</p>
                <form method="POST" action="{{ route('booking.payment.start', ['booking' => $booking->id]) }}">
                    @csrf
                    <button class="payment-button" type="submit">Bayar menggunakan Midtrans</button>
                </form>
                <p>Booking hanya berubah menjadi CONFIRMED setelah pembayaran diverifikasi backend.</p>
                <p><a href="{{ $draftUrl }}">Kembali dan lihat status tiket</a></p>
            </div>
        </section>
    </div>
</main>
@include('components.footer')
@endsection
