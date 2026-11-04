<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\QrTicket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class FreeBookingConfirmationService
{
    public function __construct(private QrTicketService $qrTickets)
    {
    }

    /**
     * Konfirmasi jalur non-retribusi menurut PRD.
     * Reservasi kuota sudah dibuat oleh BookingService; jangan increment lagi.
     * Status CONFIRMED dan QR ditulis atomik dalam transaksi yang sama.
     */
    public function confirm(string $bookingId, string $userId): QrTicket
    {
        return DB::transaction(function () use ($bookingId, $userId): QrTicket {
            $booking = Booking::query()
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $booking->user_id !== $userId) {
                throw new InvalidArgumentException('Booking bukan milik pengunjung.');
            }

            // Jangan percayai label warga ataupun nilai nominal yang dikirim frontend.
            $identity = $booking->identity;
            if (
                !$identity ||
                (string) $identity->user_id !== $userId ||
                $identity->status_warga_kota !== true ||
                $identity->divalidasi_at === null ||
                $booking->retribusi_diperlukan
            ) {
                throw new InvalidArgumentException('Booking memerlukan validasi identitas atau retribusi.');
            }

            if (!in_array($booking->status_booking, ['DRAFT', 'CONFIRMED'], true)) {
                throw new InvalidArgumentException('Status booking tidak dapat dikonfirmasi.');
            }

            // Jangan pernah menerbitkan QR baru apabila QR lama pernah invalid/dipakai.
            $active = $booking->activeQrTicket()->first();
            if ($booking->status_booking === 'CONFIRMED' && $active) {
                if (!$active->waktu_expired || $active->waktu_expired->lte(now())) {
                    throw new RuntimeException('QR sudah kedaluwarsa dan perlu pemeriksaan.');
                }

                return $active;
            }

            if ($booking->qrTickets()->exists()) {
                throw new RuntimeException('Riwayat QR perlu pemeriksaan petugas.');
            }

            if ($booking->status_booking === 'DRAFT') {
                $booking->status_booking = 'CONFIRMED';
                $booking->save();
            }

            // QrTicketService memeriksa masa berlaku jadwal dan mengunci booking.
            // Jika gagal, perubahan CONFIRMED di atas ikut rollback.
            return $this->qrTickets->generate($booking);
        });
    }
}
