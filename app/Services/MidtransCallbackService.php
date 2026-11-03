<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentGatewayCallback;
use App\Models\PaymentTransaction;
use App\Models\QrTicket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use RuntimeException;

class MidtransCallbackService
{
    /**
     * QrTicketService dibuat opsional agar pemanggilan langsung yang sudah
     * ada tetap kompatibel. Jika tidak diberikan, service diambil dari
     * container Laravel saat QR perlu diterbitkan.
     */
    public function __construct(
        private MidtransStatusGateway $gateway,
        private ?QrTicketService $qrTicketService = null
    ) {
    }

    /**
     * Memverifikasi notifikasi Midtrans, memperbarui status pembayaran,
     * mengonfirmasi booking, dan menerbitkan QR secara idempoten.
     */
    public function receive(array $payload): array
    {
        $required = [
            'order_id',
            'status_code',
            'gross_amount',
            'transaction_status',
            'signature_key',
        ];

        foreach ($required as $field) {
            if (
                !isset($payload[$field]) ||
                !is_string($payload[$field]) ||
                $payload[$field] === ''
            ) {
                throw new InvalidArgumentException(
                    'Payload Midtrans tidak lengkap.'
                );
            }
        }

        $serverKey = config('midtrans.server_key');

        if (!is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException(
                'Konfigurasi Midtrans tidak tersedia.'
            );
        }

        /*
         * Verifikasi signature notifikasi masuk.
         */
        $expected = hash(
            'sha512',
            $payload['order_id']
                . $payload['status_code']
                . $payload['gross_amount']
                . $serverKey
        );

        if (!hash_equals($expected, $payload['signature_key'])) {
            throw new InvalidArgumentException(
                'Signature tidak valid.'
            );
        }

        $orderId = $payload['order_id'];

        $exists = PaymentTransaction::query()
            ->where('kode_bayar', $orderId)
            ->exists();

        if (!$exists) {
            throw new InvalidArgumentException(
                'Order tidak ditemukan.'
            );
        }

        /*
         * Ambil status terbaru dari Midtrans di luar transaksi database.
         * Jika gateway gagal atau timeout, status lokal tidak diubah.
         */
        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config(
            'midtrans.is_production',
            false
        );

        $gateway = $this->gateway->getStatus($orderId);

        if ((string) ($gateway->order_id ?? '') !== $orderId) {
            throw new RuntimeException(
                'Order ID gateway tidak sesuai.'
            );
        }

        $gatewayAmount = $this->amount(
            (string) ($gateway->gross_amount ?? '')
        );

        $callbackAmount = $this->amount(
            $payload['gross_amount']
        );

        if ($gatewayAmount !== $callbackAmount) {
            throw new InvalidArgumentException(
                'Nominal notifikasi tidak sesuai.'
            );
        }

        $status = (string) ($gateway->transaction_status ?? '');
        $statusCode = (string) ($gateway->status_code ?? '');
        $fraud = strtolower(
            (string) ($gateway->fraud_status ?? '')
        );

        /*
         * Hanya status gateway yang telah diverifikasi yang boleh
         * mengubah status transaksi lokal.
         */
        $nextStatus = match ($status) {
            'settlement' => $statusCode === '200'
                ? 'SUCCESS'
                : null,

            'capture' => (
                $statusCode === '200' &&
                $fraud === 'accept'
            ) ? 'SUCCESS' : null,

            'deny' => 'FAILED',
            'cancel' => 'CANCELLED',
            'expire' => 'EXPIRED',
            default => null,
        };

        return DB::transaction(function () use (
            $orderId,
            $payload,
            $gatewayAmount,
            $status,
            $nextStatus
        ): array {
            $payment = PaymentTransaction::query()
                ->where('kode_bayar', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $this->amount((string) $payment->nominal)
                !== $gatewayAmount
            ) {
                throw new InvalidArgumentException(
                    'Nominal database tidak sesuai.'
                );
            }

            /*
             * Simpan payload untuk audit, tetapi jangan menyimpan signature.
             */
            $safePayload = $payload;
            unset($safePayload['signature_key']);

            PaymentGatewayCallback::query()->create([
                'transaction_id' => $payment->id,
                'status_callback' => $status,
                'signature_valid' => true,
                'raw_payload' => $safePayload,
                'received_at' => now(),
            ]);

            /*
             * Callback berulang untuk status yang sama.
             * Jika pembayaran sudah SUCCESS, pastikan QR yang semestinya
             * tersedia tidak dibuat dua kali. Ini juga membantu pemulihan
             * booking lama yang sudah SUCCESS tetapi belum memiliki QR.
             */
            if ($payment->status_pembayaran === $nextStatus) {
                if ($nextStatus === 'SUCCESS') {
                    $booking = Booking::query()
                        ->whereKey($payment->booking_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($booking->status_booking !== 'CONFIRMED') {
                        return [
                            'status' => $payment->status_pembayaran,
                            'updated' => false,
                            'requires_review' => true,
                        ];
                    }

                    $qrResult = $this->ensureQrTicket($booking);

                    if ($qrResult['requires_review']) {
                        return [
                            'status' => $payment->status_pembayaran,
                            'updated' => false,
                            'requires_review' => true,
                        ];
                    }
                }

                return [
                    'status' => $payment->status_pembayaran,
                    'updated' => false,
                ];
            }

            /*
             * Status final tidak boleh ditimpa oleh callback lain.
             */
            if ($payment->status_pembayaran !== 'PENDING') {
                return [
                    'status' => $payment->status_pembayaran,
                    'updated' => false,
                    'requires_review' => true,
                ];
            }

            /*
             * Status pending, fraud challenge, atau status gateway yang
             * tidak dikenal tidak mengonfirmasi booking.
             */
            if ($nextStatus === null) {
                return [
                    'status' => 'PENDING',
                    'updated' => false,
                ];
            }

            $booking = Booking::query()
                ->whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $nextStatus === 'SUCCESS' &&
                !in_array(
                    $booking->status_booking,
                    ['DRAFT', 'CONFIRMED'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Status booking perlu pemeriksaan manual.'
                );
            }

            /*
             * Perbarui pembayaran dan konfirmasi booking dalam transaksi
             * database yang sama dengan penerbitan QR.
             */
            $payment->status_pembayaran = $nextStatus;
            $payment->save();

            if ($nextStatus === 'SUCCESS') {
                $booking->status_booking = 'CONFIRMED';
                $booking->save();

                /*
                 * Jika tidak ada riwayat QR, terbitkan QR pertama.
                 * Jika QR aktif sudah ada, gunakan QR yang sama.
                 * Jika ada riwayat QR tetapi tidak ada QR aktif, jangan
                 * menerbitkan ulang otomatis; perlu pemeriksaan manual.
                 */
                $qrResult = $this->ensureQrTicket($booking);

                if ($qrResult['requires_review']) {
                    return [
                        'status' => $nextStatus,
                        'updated' => true,
                        'requires_review' => true,
                    ];
                }
            }

            return [
                'status' => $nextStatus,
                'updated' => true,
            ];
        });
    }

    /**
     * Memastikan booking memiliki QR aktif tanpa membuat duplikat.
     *
     * @return array{created: bool, requires_review: bool}
     */
    private function ensureQrTicket(Booking $booking): array
    {
        $hasActiveQr = QrTicket::query()
            ->where('booking_id', $booking->id)
            ->where('status_qr', 'AKTIF')
            ->exists();

        if ($hasActiveQr) {
            return [
                'created' => false,
                'requires_review' => false,
            ];
        }

        $hasQrHistory = QrTicket::query()
            ->where('booking_id', $booking->id)
            ->exists();

        if ($hasQrHistory) {
            return [
                'created' => false,
                'requires_review' => true,
            ];
        }

        $service = $this->qrTicketService
            ?? app(QrTicketService::class);

        $service->generate($booking);

        return [
            'created' => true,
            'requires_review' => false,
        ];
    }

    /**
     * Normalisasi nominal agar perbandingan tidak terpengaruh format desimal.
     */
    private function amount(string $value): string
    {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException(
                'Format nominal tidak valid.'
            );
        }

        [$whole, $decimal] = array_pad(
            explode('.', $value, 2),
            2,
            '00'
        );

        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole)
            . '.'
            . str_pad($decimal, 2, '0');
    }
}
