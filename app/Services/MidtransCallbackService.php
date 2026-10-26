<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentGatewayCallback;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use RuntimeException;

class MidtransCallbackService
{
    
    public function __construct(
        private MidtransStatusGateway $gateway
    ) {
    }

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

        // Verifikasi signature notifikasi masuk.
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

        // Periksa bahwa order dikenal SI-GORAY.
        $exists = PaymentTransaction::where(
            'kode_bayar',
            $orderId
        )->exists();

        if (!$exists) {
            throw new InvalidArgumentException(
                'Order tidak ditemukan.'
            );
        }

        /*
         * Ambil status terpercaya dari Midtrans.
         * Request HTTP dilakukan di luar DB transaction.
         *
         * Jika Midtrans mengembalikan 404/timeout,
         * jangan mengubah status pembayaran.
         */
        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config(
            'midtrans.is_production',
            false
        );

    $gateway = $this->gateway->getStatus($orderId);

        if (
            (string) ($gateway->order_id ?? '') !== $orderId
        ) {
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

        $status = (string) (
            $gateway->transaction_status ?? ''
        );

        $statusCode = (string) (
            $gateway->status_code ?? ''
        );

        $fraud = strtolower(
            (string) ($gateway->fraud_status ?? '')
        );

        // Kategori hasil berdasarkan status terbaru gateway.
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
        ) {
            $payment = PaymentTransaction::where(
                'kode_bayar',
                $orderId
            )
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

            // Simpan salinan payload tanpa signature.
            $safePayload = $payload;
            unset($safePayload['signature_key']);

            PaymentGatewayCallback::create([
                'transaction_id' => $payment->id,
                'status_callback' => $status,
                'signature_valid' => true,
                'raw_payload' => $safePayload,
                'received_at' => now(),
            ]);

            // Callback berulang: jangan proses ulang.
            if ($payment->status_pembayaran === $nextStatus) {
                return [
                    'status' => $payment->status_pembayaran,
                    'updated' => false,
                ];
            }

            // Status final tidak boleh ditimpa sembarangan.
            if ($payment->status_pembayaran !== 'PENDING') {
                return [
                    'status' => $payment->status_pembayaran,
                    'updated' => false,
                    'requires_review' => true,
                ];
            }

            // Pending, fraud challenge, atau status asing.
            if ($nextStatus === null) {
                return [
                    'status' => 'PENDING',
                    'updated' => false,
                ];
            }

            $booking = Booking::whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($nextStatus === 'SUCCESS' &&
                $booking->status_booking !== 'DRAFT' &&
                $booking->status_booking !== 'CONFIRMED') {
                throw new RuntimeException(
                    'Status booking perlu pemeriksaan manual.'
                );
            }

            $payment->status_pembayaran = $nextStatus;
            $payment->save();

            if ($nextStatus === 'SUCCESS') {
                $booking->status_booking = 'CONFIRMED';
                $booking->save();
            }

            return [
                'status' => $nextStatus,
                'updated' => true,
            ];
        });
    }

    private function amount(string $value): string
    {
        if (!preg_match(
            '/^\d+(\.\d{1,2})?$/',
            $value
        )) {
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
