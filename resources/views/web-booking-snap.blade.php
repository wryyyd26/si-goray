@extends('layouts.app')
@section('title', 'Midtrans Snap | SI-GORAY')
@section('content')
@include('components.navbar')
<main class="payment-page">
    <section class="payment-intro">
        <h1>Pembayaran Midtrans</h1>
        <p>Pilih metode pembayaran di jendela resmi Midtrans.</p>
    </section>
    <section class="checkout-card">
        <p>Booking: {{ $booking->id }}</p>
        <button type="button" id="open-snap" class="payment-button">Buka pembayaran Midtrans</button>
        <p id="snap-message" role="status">Jika popup tidak terbuka, klik tombol pembayaran di atas.</p>
        <form method="POST" action="{{ route('booking.payment.sync', ['booking' => $booking->id]) }}">
            @csrf
            <button type="submit" class="payment-button">Periksa Status Pembayaran di Midtrans</button>
        </form>
        <p><a href="{{ $draftUrl }}">Lihat status booking</a></p>
        <p><a href="{{ $paymentUrl }}">Kembali ke ringkasan pembayaran</a></p>
        <p><small>Penutupan popup atau pesan sukses dari browser tidak otomatis mengonfirmasi tiket. Status akan diperbarui setelah notifikasi terverifikasi oleh server.</small></p>
    </section>
</main>
@include('components.footer')
<script src="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ $clientKey }}"></script>
<script>
(function () {
    const token = @json($snapToken);
    const draftUrl = @json($draftUrl);
    const syncForm = document.querySelector('form[action*="/payment/sync"]');
    const button = document.getElementById('open-snap');
    const message = document.getElementById('snap-message');
    button.addEventListener('click', function () {
        if (!window.snap || typeof window.snap.pay !== 'function') {
            message.textContent = 'Midtrans belum termuat. Periksa koneksi internet dan coba muat ulang halaman.';
            return;
        }
        window.snap.pay(token, {
            onSuccess: function () { syncForm.submit(); },
            onPending: function () { window.location.assign(draftUrl); },
            onError: function () { message.textContent = 'Pembayaran belum berhasil. Periksa status booking sebelum mencoba lagi.'; },
            onClose: function () { message.textContent = 'Jendela ditutup. Status booking tidak berubah sampai gateway mengonfirmasi pembayaran.'; }
        });
    });
}());
</script>
@endsection
