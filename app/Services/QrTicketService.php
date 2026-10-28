<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\QrTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QrTicketService
{
    /**
     * Terbitkan QR pertama hanya untuk booking CONFIRMED.
     * Booking yang memiliki riwayat QR tidak dapat diterbitkan
     * ulang melalui method ini.
     */
    public function generate(Booking $booking): QrTicket
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $this->ensureBookingConfirmed($booking);

            $hasHistory = QrTicket::query()
                ->where('booking_id', $booking->id)
                ->exists();

            if ($hasHistory) {
                throw new RuntimeException(
                    'Booking sudah memiliki riwayat QR. '
                    . 'Gunakan prosedur regenerasi jika diizinkan.'
                );
            }

            return $this->createSignedTicket($booking);
        });
    }

    /**
     * Regenerasi QR yang masih berstatus AKTIF.
     * QR lama diinvalidasi dalam transaksi yang sama.
     */
    public function regenerate(Booking $booking): QrTicket
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $this->ensureBookingConfirmed($booking);

            $qrLama = QrTicket::query()
                ->where('booking_id', $booking->id)
                ->where('status_qr', 'AKTIF')
                ->lockForUpdate()
                ->first();

            if (!$qrLama) {
                throw new RuntimeException(
                    'Tidak ada QR AKTIF yang dapat diregenerasi.'
                );
            }

            $qrLama->update([
                'status_qr' => 'INVALID',
            ]);

            return $this->createSignedTicket(
                $booking,
                $qrLama->id
            );
        });
    }

    /**
     * Validasi QR tanpa mengubah status tiket.
     * Penggunaan QR satu kali tetap ditangani oleh
     * GateAccessService.
     */
    public function validate(string $payloadSigned): array
    {
        $parts = explode('.', $payloadSigned, 2);

        if (
            count($parts) !== 2 ||
            $parts[0] === '' ||
            $parts[1] === ''
        ) {
            return [
                'valid' => false,
                'reason' => 'FORMAT_QR_TIDAK_VALID',
            ];
        }

        [$payloadEncoded, $signature] = $parts;

        if (
            !hash_equals(
                $this->sign($payloadEncoded),
                $signature
            )
        ) {
            return [
                'valid' => false,
                'reason' => 'SIGNATURE_TIDAK_VALID',
            ];
        }

        try {
            $payload = $this->decodePayload($payloadEncoded);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'reason' => 'PAYLOAD_TIDAK_VALID',
            ];
        }

        if (
            !isset(
                $payload['qr_ticket_id'],
                $payload['booking_id'],
                $payload['expires_at']
            ) ||
            !is_string($payload['qr_ticket_id']) ||
            !is_string($payload['booking_id']) ||
            !is_numeric($payload['expires_at'])
        ) {
            return [
                'valid' => false,
                'reason' => 'PAYLOAD_TIDAK_LENGKAP',
            ];
        }

        if ((int) $payload['expires_at'] <= now()->timestamp) {
            return [
                'valid' => false,
                'reason' => 'QR_EXPIRED',
                'payload' => $payload,
            ];
        }

        $qrTicket = QrTicket::query()
            ->find($payload['qr_ticket_id']);

        if (!$qrTicket) {
            return [
                'valid' => false,
                'reason' => 'QR_TIDAK_DITEMUKAN',
                'payload' => $payload,
            ];
        }

        if (
            (string) $qrTicket->booking_id !==
                $payload['booking_id'] ||
            !hash_equals(
                (string) $qrTicket->payload_signed,
                $payloadSigned
            )
        ) {
            return [
                'valid' => false,
                'reason' => 'BOOKING_TIDAK_SESUAI',
                'qr_ticket' => $qrTicket,
            ];
        }

        if ($qrTicket->status_qr !== 'AKTIF') {
            return [
                'valid' => false,
                'reason' => 'QR_TIDAK_AKTIF',
                'qr_ticket' => $qrTicket,
            ];
        }

        if (
            !$qrTicket->waktu_expired ||
            $qrTicket->waktu_expired->lte(now()) ||
            (int) $payload['expires_at'] !==
                $qrTicket->waktu_expired->timestamp
        ) {
            return [
                'valid' => false,
                'reason' => 'QR_EXPIRED',
                'qr_ticket' => $qrTicket,
            ];
        }

        if (
            !$qrTicket->booking ||
            $qrTicket->booking->status_booking !== 'CONFIRMED'
        ) {
            return [
                'valid' => false,
                'reason' => 'BOOKING_BELUM_CONFIRMED',
                'qr_ticket' => $qrTicket,
            ];
        }

        return [
            'valid' => true,
            'reason' => 'QR_VALID',
            'payload' => $payload,
            'qr_ticket' => $qrTicket,
        ];
    }

    /**
     * QR hanya dapat diterbitkan untuk booking terkonfirmasi.
     */
    protected function ensureBookingConfirmed(
        Booking $booking
    ): void {
        if ($booking->status_booking !== 'CONFIRMED') {
            throw new RuntimeException(
                'QR hanya dapat diterbitkan untuk booking CONFIRMED.'
            );
        }
    }

    /**
     * Tentukan masa berlaku berdasarkan jadwal booking.
     * QR berlaku sampai dua jam setelah jadwal berakhir.
     */
    protected function resolveExpiryAt(
        Booking $booking
    ): Carbon {
        $booking->loadMissing([
            'regularSlot',
            'eventSchedule',
        ]);

        if ($booking->regularSlot) {
            $schedule = $booking->regularSlot;
        } elseif ($booking->eventSchedule) {
            $schedule = $booking->eventSchedule;
        } else {
            throw new RuntimeException(
                'Jadwal booking tidak ditemukan. QR tidak dapat diterbitkan.'
            );
        }

        $tanggal = $schedule->tanggal;
        $jamSelesai = $schedule->jam_selesai;

        if (!$tanggal || !$jamSelesai) {
            throw new RuntimeException(
                'Tanggal atau waktu selesai booking tidak tersedia.'
            );
        }

        $tanggal = Carbon::parse($tanggal)->toDateString();

        $jadwalBerakhir = Carbon::parse(
            $tanggal . ' ' . $jamSelesai
        );

        $waktuExpired = $jadwalBerakhir->copy()->addHours(2);

        if ($waktuExpired->lte(now())) {
            throw new RuntimeException(
                'Masa berlaku jadwal booking sudah berakhir. '
                . 'QR tidak dapat diterbitkan.'
            );
        }

        return $waktuExpired;
    }

    /**
     * Membuat tiket dan payload bertanda tangan digital.
     */
    protected function createSignedTicket(
        Booking $booking,
        ?string $regeneratedFromId = null
    ): QrTicket {
        $waktuTerbit = now();
        $waktuExpired = $this->resolveExpiryAt($booking);

        $qrTicket = new QrTicket();

        $qrTicket->booking_id = $booking->id;
        $qrTicket->payload_signed = 'PENDING';
        $qrTicket->status_qr = 'AKTIF';
        $qrTicket->regenerated_from_qr_id =
            $regeneratedFromId;
        $qrTicket->waktu_terbit = $waktuTerbit;
        $qrTicket->waktu_expired = $waktuExpired;

        $qrTicket->save();

        $payload = [
            'qr_ticket_id' => $qrTicket->id,
            'booking_id' => $booking->id,
            'expires_at' => $waktuExpired->timestamp,
        ];

        $payloadEncoded = $this->encodePayload($payload);

        $qrTicket->update([
            'payload_signed' => $payloadEncoded
                . '.'
                . $this->sign($payloadEncoded),
        ]);

        return $qrTicket->fresh();
    }

    /**
     * Tanda tangan HMAC SHA-256 menggunakan APP_KEY.
     */
    protected function sign(string $payloadEncoded): string
    {
        $key = config('app.key');

        if (!$key) {
            throw new RuntimeException(
                'APP_KEY belum tersedia.'
            );
        }

        return $this->base64UrlEncode(
            hash_hmac(
                'sha256',
                $payloadEncoded,
                $key,
                true
            )
        );
    }

    protected function encodePayload(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException(
                'Gagal membuat payload QR.'
            );
        }

        return $this->base64UrlEncode($json);
    }

    protected function decodePayload(string $encoded): array
    {
        $decoded = $this->base64UrlDecode($encoded);

        $payload = json_decode($decoded, true);

        if (!is_array($payload)) {
            throw new RuntimeException(
                'Payload QR tidak valid.'
            );
        }

        return $payload;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }

    protected function base64UrlDecode(string $value): string
    {
        if (
            $value === '' ||
            preg_match('/[^A-Za-z0-9_-]/', $value)
        ) {
            throw new RuntimeException(
                'Base64 payload tidak valid.'
            );
        }

        $remainder = strlen($value) % 4;

        if ($remainder === 1) {
            throw new RuntimeException(
                'Panjang Base64 tidak valid.'
            );
        }

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        if ($decoded === false) {
            throw new RuntimeException(
                'Base64 payload tidak valid.'
            );
        }

        return $decoded;
    }
}
