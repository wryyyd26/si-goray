<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use RuntimeException;
use Throwable;

class PaymentService
{
    public function __construct(
        private MidtransSnapGateway $gateway
    ) {
    }

    /**
     * Membuat transaksi pembayaran untuk booking.
     *
     * Alur:
     * 1. Validasi booking dan buat transaksi PENDING.
     * 2. Meminta Snap Token dari Midtrans.
     * 3. Menyimpan Snap Token ke database.
     */
    public function createPayment(string $bookingId): array
    {
        /*
         * TAHAP 1:
         * Validasi booking dan reservasi transaksi.
         */
        $result = DB::transaction(function () use ($bookingId) {
            $booking = Booking::with(['identity', 'tariff'])
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->first();

            if (!$booking) {
                throw new InvalidArgumentException(
                    'Booking tidak ditemukan.'
                );
            }

            if ($booking->status_booking !== 'DRAFT') {
                throw new InvalidArgumentException(
                    'Booking tidak dapat diproses untuk pembayaran.'
                );
            }

            if (!$booking->identity) {
                throw new InvalidArgumentException(
                    'Identitas booking tidak ditemukan.'
                );
            }

            if (
                $booking->identity->status_warga_kota === null
            ) {
                throw new InvalidArgumentException(
                    'Status warga kota belum divalidasi.'
                );
            }

            if (
                !$booking->retribusi_diperlukan ||
                $booking->identity->status_warga_kota
            ) {
                throw new InvalidArgumentException(
                    'Booking ini tidak memerlukan pembayaran.'
                );
            }

            if (!$booking->tariff) {
                throw new InvalidArgumentException(
                    'Tarif booking tidak ditemukan.'
                );
            }

            $nominal = (float) $booking->tariff->nominal;

            if ($nominal <= 0 || floor($nominal) != $nominal) {
                throw new InvalidArgumentException(
                    'Nominal pembayaran tidak valid.'
                );
            }

            /*
             * Gunakan kembali transaksi PENDING yang masih berlaku.
             */
            $pending = PaymentTransaction::where(
                'booking_id',
                $booking->id
            )
                ->where('status_pembayaran', 'PENDING')
                ->first();

            if ($pending) {
                if (!$pending->snap_token) {
                    throw new InvalidArgumentException(
                        'Transaksi lama belum memiliki Snap Token. '
                        . 'Perlu rekonsiliasi pembayaran.'
                    );
                }

                if (
                    !$pending->expired_at ||
                    $pending->expired_at->isPast()
                ) {
                    throw new InvalidArgumentException(
                        'Batas pembayaran telah lewat. '
                        . 'Status gateway harus diperiksa terlebih dahulu.'
                    );
                }

                return [
                    'existing' => true,
                    'transaction' => $pending,
                ];
            }

            /*
             * BR-4:
             * Maksimal 3 kali retry dalam 24 jam.
             *
             * 1 pembayaran awal + 3 retry = 4 transaksi.
             */
            $jumlahPercobaan = PaymentTransaction::where(
                'booking_id',
                $booking->id
            )
                ->where(
                    'created_at',
                    '>=',
                    now()->subHours(24)
                )
                ->count();

            if ($jumlahPercobaan >= 4) {
                throw new InvalidArgumentException(
                    'Batas maksimal 3 kali retry pembayaran '
                    . 'dalam 24 jam telah tercapai.'
                );
            }

            /*
             * Buat transaksi PENDING sebelum menghubungi Midtrans.
             */
            $payment = PaymentTransaction::create([
                'booking_id' => $booking->id,
                'kode_bayar' => 'GOR-' . str()->uuid(),
                'metode_pembayaran' => 'MIDTRANS',
                'nominal' => (int) $nominal,
                'status_pembayaran' => 'PENDING',
                'jumlah_retry' => $jumlahPercobaan,
                'referensi_gateway' => null,
                'expired_at' => now()->addHours(24),
                'snap_token' => null,
            ]);

            return [
                'existing' => false,
                'transaction' => $payment,
            ];
        });

        $payment = $result['transaction'];

        /*
         * Jika transaksi lama masih berlaku,
         * kembalikan data transaksi beserta Snap Token.
         */
        if ($result['existing']) {
            return $this->formatResponse($payment);
        }

        /*
         * TAHAP 2:
         * Siapkan konfigurasi Midtrans.
         */
        $serverKey = config('midtrans.server_key');

        if (!is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException(
                'Konfigurasi MIDTRANS_SERVER_KEY belum tersedia.'
            );
        }

        Config::$serverKey = $serverKey;

        Config::$isProduction = (bool) config(
            'midtrans.is_production',
            false
        );

        Config::$isSanitized = true;

        $startTime = $payment->created_at
            ->copy()
            ->timezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s O');

        try {
            $snapToken = $this->gateway->createToken([
                'transaction_details' => [
                    'order_id' => $payment->kode_bayar,
                    'gross_amount' => (int) $payment->nominal,
                ],

                'enabled_payments' => [
                    'qris',
                    'bank_transfer',
                ],

                'page_expiry' => [
                    'duration' => 24,
                    'unit' => 'hour',
                ],

                'expiry' => [
                    'start_time' => $startTime,
                    'duration' => 24,
                    'unit' => 'hour',
                ],
            ]);
        } catch (Throwable $e) {
            /*
             * Jangan langsung mengubah status menjadi FAILED.
             *
             * Midtrans mungkin sudah membuat transaksi,
             * tetapi responsnya gagal diterima aplikasi.
             */
            report($e);

            throw new RuntimeException(
                'Status pembuatan pembayaran belum dapat dipastikan. '
                . 'Periksa transaksi sebelum mencoba lagi.',
                0,
                $e
            );
        }

        if (!is_string($snapToken) || $snapToken === '') {
            throw new RuntimeException(
                'Midtrans tidak mengembalikan Snap Token. '
                . 'Transaksi perlu diperiksa.'
            );
        }

        /*
         * TAHAP 3:
         * Simpan Snap Token setelah respons Midtrans diterima.
         */
        $payment = DB::transaction(function () use (
            $payment,
            $snapToken
        ) {
            $lockedPayment = PaymentTransaction::whereKey(
                $payment->id
            )
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedPayment->status_pembayaran !== 'PENDING'
            ) {
                throw new RuntimeException(
                    'Status pembayaran sudah berubah '
                    . 'saat membuat token.'
                );
            }

            $lockedPayment->snap_token = $snapToken;
            $lockedPayment->referensi_gateway =
                $lockedPayment->kode_bayar;

            $lockedPayment->save();

            return $lockedPayment->fresh();
        });

        return $this->formatResponse($payment);
    }

    /**
     * Menyeragamkan format respons transaksi pembayaran.
     */
    private function formatResponse(
        PaymentTransaction $payment
    ): array {
        return [
            'transaction_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'kode_bayar' => $payment->kode_bayar,
            'nominal' => $payment->nominal,
            'status_pembayaran' => $payment->status_pembayaran,
            'expired_at' => $payment->expired_at,
            'snap_token' => $payment->snap_token,
        ];
    }
}
