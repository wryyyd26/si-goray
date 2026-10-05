<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\QrTicket;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QrTicketService
{
    /**
     * Generate QR Ticket baru untuk booking.
     *
     * QR dibuat terlebih dahulu agar Laravel menghasilkan
     * ID sesuai dengan konfigurasi HasUuids pada model.
     * Setelah ID tersedia, payload ditandatangani.
     */
    public function generate(Booking $booking): QrTicket
    {
        return DB::transaction(function () use ($booking) {

            $waktuTerbit = now();
            $waktuExpired = $waktuTerbit->copy()->addHours(2);

            /*
             * Buat QR terlebih dahulu.
             *
             * ID akan dibuat oleh model QrTicket/HasUuids.
             */
            $qrTicket = new QrTicket();

            $qrTicket->booking_id = $booking->id;
            $qrTicket->payload_signed = 'PENDING';
            $qrTicket->status_qr = 'AKTIF';
            $qrTicket->regenerated_from_qr_id = null;
            $qrTicket->waktu_terbit = $waktuTerbit;
            $qrTicket->waktu_expired = $waktuExpired;

            $qrTicket->save();

            /*
             * Sekarang ID database sudah tersedia.
             */
            $qrId = $qrTicket->id;

            $payload = [
                'qr_ticket_id' => $qrId,
                'booking_id' => $booking->id,
                'expires_at' => $waktuExpired->timestamp,
            ];

            $payloadEncoded = $this->encodePayload($payload);

            $signature = $this->sign($payloadEncoded);

            $payloadSigned = $payloadEncoded . '.' . $signature;

            $qrTicket->update([
                'payload_signed' => $payloadSigned,
            ]);

            return $qrTicket->fresh();
        });
    }

    /**
     * Regenerate QR Ticket.
     *
     * QR AKTIF sebelumnya dibuat INVALID,
     * kemudian dibuat QR baru dengan status AKTIF.
     *
     * QR baru menyimpan ID QR lama pada
     * regenerated_from_qr_id.
     */
    public function regenerate(Booking $booking): QrTicket
    {
        return DB::transaction(function () use ($booking) {

            /*
             * Cari QR aktif milik booking.
             *
             * lockForUpdate() mencegah dua proses regenerasi
             * berjalan bersamaan pada QR yang sama.
             */
            $qrLama = QrTicket::where('booking_id', $booking->id)
                ->where('status_qr', 'AKTIF')
                ->lockForUpdate()
                ->first();

            /*
             * Invalidate QR lama terlebih dahulu.
             */
            if ($qrLama) {
                $qrLama->update([
                    'status_qr' => 'INVALID',
                ]);
            }

            $waktuTerbit = now();
            $waktuExpired = $waktuTerbit->copy()->addHours(2);

            /*
             * Buat QR baru.
             *
             * Jangan membuat ID manual.
             * Biarkan HasUuids menghasilkan ID.
             */
            $qrBaru = new QrTicket();

            $qrBaru->booking_id = $booking->id;
            $qrBaru->payload_signed = 'PENDING';
            $qrBaru->status_qr = 'AKTIF';
            $qrBaru->regenerated_from_qr_id = $qrLama?->id;
            $qrBaru->waktu_terbit = $waktuTerbit;
            $qrBaru->waktu_expired = $waktuExpired;

            $qrBaru->save();

            /*
             * Ambil ID yang benar-benar dibuat oleh database/model.
             */
            $qrId = $qrBaru->id;

            $payload = [
                'qr_ticket_id' => $qrId,
                'booking_id' => $booking->id,
                'expires_at' => $waktuExpired->timestamp,
            ];

            $payloadEncoded = $this->encodePayload($payload);

            $signature = $this->sign($payloadEncoded);

            $payloadSigned = $payloadEncoded . '.' . $signature;

            $qrBaru->update([
                'payload_signed' => $payloadSigned,
            ]);

            return $qrBaru->fresh();
        });
    }

    /**
     * Validasi QR berdasarkan payload_signed.
     *
     * Validasi:
     * 1. Format QR
     * 2. Signature
     * 3. Kelengkapan payload
     * 4. Masa berlaku payload
     * 5. QR tersedia di database
     * 6. Booking sesuai
     * 7. Status QR AKTIF
     * 8. Masa berlaku database
     */
    public function validate(string $payloadSigned): array
    {
        /*
         * Pisahkan payload dan signature.
         */
        $parts = explode('.', $payloadSigned, 2);

        if (count($parts) !== 2) {
            return [
                'valid' => false,
                'reason' => 'FORMAT_QR_TIDAK_VALID',
            ];
        }

        [$payloadEncoded, $signature] = $parts;

        /*
         * Hitung signature yang seharusnya.
         */
        $expectedSignature = $this->sign($payloadEncoded);

        /*
         * Bandingkan signature.
         */
        if (!hash_equals($expectedSignature, $signature)) {
            return [
                'valid' => false,
                'reason' => 'SIGNATURE_TIDAK_VALID',
            ];
        }

        /*
         * Decode payload.
         */
        try {
            $payload = $this->decodePayload($payloadEncoded);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'reason' => 'PAYLOAD_TIDAK_VALID',
            ];
        }

        /*
         * Pastikan semua data wajib tersedia.
         */
        if (
            !isset($payload['qr_ticket_id']) ||
            !isset($payload['booking_id']) ||
            !isset($payload['expires_at'])
        ) {
            return [
                'valid' => false,
                'reason' => 'PAYLOAD_TIDAK_LENGKAP',
            ];
        }

        /*
         * Cek expired berdasarkan payload.
         */
        if ((int) $payload['expires_at'] < now()->timestamp) {
            return [
                'valid' => false,
                'reason' => 'QR_EXPIRED',
                'payload' => $payload,
            ];
        }

        /*
         * Cari QR berdasarkan ID yang terdapat
         * pada payload.
         */
        $qrTicket = QrTicket::find($payload['qr_ticket_id']);

        if (!$qrTicket) {
            return [
                'valid' => false,
                'reason' => 'QR_TIDAK_DITEMUKAN',
                'payload' => $payload,
            ];
        }

        /*
         * Pastikan booking pada QR sama dengan
         * booking pada payload.
         */
        if ($qrTicket->booking_id !== $payload['booking_id']) {
            return [
                'valid' => false,
                'reason' => 'BOOKING_TIDAK_SESUAI',
                'qr_ticket' => $qrTicket,
            ];
        }

        /*
         * QR hanya boleh digunakan ketika status AKTIF.
         */
        if ($qrTicket->status_qr !== 'AKTIF') {
            return [
                'valid' => false,
                'reason' => 'QR_TIDAK_AKTIF',
                'qr_ticket' => $qrTicket,
            ];
        }

        /*
         * Cek expired berdasarkan data database.
         */
        if ($qrTicket->waktu_expired->isPast()) {
            return [
                'valid' => false,
                'reason' => 'QR_EXPIRED',
                'qr_ticket' => $qrTicket,
            ];
        }

        /*
         * Semua validasi berhasil.
         */
        return [
            'valid' => true,
            'reason' => 'QR_VALID',
            'payload' => $payload,
            'qr_ticket' => $qrTicket,
        ];
    }

    /**
     * Membuat signature menggunakan HMAC SHA-256.
     */
    protected function sign(string $payloadEncoded): string
    {
        $key = config('app.key');

        if (!$key) {
            throw new RuntimeException('APP_KEY belum tersedia.');
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

    /**
     * Encode payload menjadi Base64 URL-safe.
     */
    protected function encodePayload(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new RuntimeException('Gagal membuat payload QR.');
        }

        return $this->base64UrlEncode($json);
    }

    /**
     * Decode Base64 URL-safe menjadi array.
     */
    protected function decodePayload(string $encoded): array
    {
        $json = $this->base64UrlDecode($encoded);

        $payload = json_decode($json, true);

        if (!is_array($payload)) {
            throw new RuntimeException('Payload QR tidak valid.');
        }

        return $payload;
    }

    /**
     * Encode Base64 URL-safe.
     */
    protected function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(
                base64_encode($value),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /**
     * Decode Base64 URL-safe.
     */
    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/')
        );

        if ($decoded === false) {
            throw new RuntimeException('Base64 payload tidak valid.');
        }

        return $decoded;
    }
}