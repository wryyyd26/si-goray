<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Midtrans\Config;
use RuntimeException;
use Throwable;

class PaymentReconciliationService
{
    public function __construct(
        private MidtransStatusGateway $gateway
    ) {
    }

    /**
     * Memeriksa status transaksi di Midtrans tanpa
     * mengubah status pembayaran pada database.
     */
    public function inspect(string $transactionId): array
    {
        $payment = PaymentTransaction::findOrFail($transactionId);

        $result = [
            'transaction_id' => $payment->id,
            'status_lokal' => $payment->status_pembayaran,
            'snap_token_ada' => !empty($payment->snap_token),
            'expired_lokal' => $payment->expired_at !== null
                && $payment->expired_at->isPast(),
            'status_gateway' => null,
            'tindakan' => 'REVIEW',
        ];

        if ($payment->status_pembayaran === 'SUCCESS') {
            $result['tindakan'] = 'SUDAH_BERHASIL';

            return $result;
        }

        $serverKey = config('midtrans.server_key');

        if (!is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException(
                'Konfigurasi Midtrans belum tersedia.'
            );
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config(
            'midtrans.is_production',
            false
        );

        try {
            $gateway = $this->gateway->getStatus(
                $payment->kode_bayar
            );
        } catch (Throwable $e) {
            $message = $e->getMessage();

            if (
                str_contains($message, 'HTTP status code: 404')
                && str_contains($message, "Transaction doesn't exist")
            ) {
                $result['status_gateway'] = 'NOT_FOUND';
                $result['tindakan'] = 'PERLU_REKONSILIASI';

                return $result;
            }

            report($e);

            $result['status_gateway'] = 'UNAVAILABLE';
            $result['tindakan'] = 'COBA_PERIKSA_KEMBALI';

            return $result;
        }

        if (
            (string) ($gateway->order_id ?? '')
            !== $payment->kode_bayar
        ) {
            $result['status_gateway'] = 'ORDER_MISMATCH';

            return $result;
        }

        $gatewayAmount = (string) (
            $gateway->gross_amount ?? ''
        );

        if (
            !is_numeric($gatewayAmount)
            || number_format((float) $gatewayAmount, 2, '.', '')
                !== (string) $payment->nominal
        ) {
            $result['status_gateway'] = 'AMOUNT_MISMATCH';

            return $result;
        }

        $status = (string) (
            $gateway->transaction_status ?? ''
        );

        $result['status_gateway'] = $status;

        $result['tindakan'] = match ($status) {
            'settlement', 'capture' => 'VERIFIKASI_PEMBAYARAN',
            'expire', 'cancel', 'deny' => 'PROSES_STATUS_AKHIR',
            'pending' => 'MASIH_MENUNGGU',
            default => 'REVIEW',
        };

        return $result;
    }
}
