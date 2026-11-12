{{-- Data diambil khusus berdasarkan user_id akun web yang sedang login. --}}
@if ($pesanan->isEmpty())
    <div class="pesanan-empty">
        <div class="pesanan-empty-icon">
            <span class="material-symbols-rounded">receipt_long</span>
        </div>
        <h3>Belum Ada Pesanan</h3>
        <p>Pesanan kegiatan reguler atau event yang kamu buat akan tampil di sini.</p>
        <a href="{{ route('home') }}" class="pesanan-home-button">Cari Kegiatan</a>
    </div>
@else
    <div class="pesanan-list" style="display:grid;gap:16px;">
        @foreach ($pesanan as $booking)
            @php
                $jenis = $booking->regular_slot_id ? 'Reguler' : ($booking->event_schedule_id ? 'Event' : (string) $booking->jenis_booking);
                $payment = $booking->paymentTransactions->sortByDesc('created_at')->first();
                $activeQr = $booking->qrTickets->firstWhere('status_qr', 'AKTIF');
                $status = (string) $booking->status_booking;
                $canPay = $booking->retribusi_diperlukan && $status === 'DRAFT';
                $canQr = $activeQr !== null && $status === 'CONFIRMED';
            @endphp
            <article class="pesanan-item" style="border:1px solid #dbe3ec;border-radius:12px;padding:18px;">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:start;">
                    <div>
                        <h3 style="margin:0 0 7px;">Pesanan {{ $jenis }}</h3>
                        <p style="margin:0;overflow-wrap:anywhere;">No. booking: <strong>{{ $booking->id }}</strong></p>
                    </div>
                    <strong>{{ $status }}</strong>
                </div>
                <p style="margin:12px 0 6px;">Dibuat: {{ $booking->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                <p style="margin:0 0 14px;">Pembayaran: {{ $payment?->status_pembayaran ?? ($booking->retribusi_diperlukan ? 'Belum tercatat' : 'Tidak diperlukan') }}</p>
                @if ($canQr)
                    <a class="pesanan-home-button" href="{{ $booking->regular_slot_id ? route('reguler.booking.qr-image', ['booking' => $booking->id]) : route('events.booking.qr-image', ['booking' => $booking->id]) }}" target="_blank" rel="noopener">Lihat QR Tiket</a>
                @elseif ($canPay)
                    <a class="pesanan-home-button" href="{{ route('booking.payment.show', ['booking' => $booking->id]) }}">Lanjutkan Pembayaran</a>
                @endif
            </article>
        @endforeach
    </div>
    <div style="margin-top:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        @if ($pesanan->previousPageUrl())
            <a href="{{ $pesanan->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>Halaman {{ $pesanan->currentPage() }} dari {{ $pesanan->lastPage() }}</span>
        @if ($pesanan->nextPageUrl())
            <a href="{{ $pesanan->nextPageUrl() }}">Selanjutnya</a>
        @endif
    </div>
@endif
