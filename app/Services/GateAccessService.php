<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\PetugasGerbang;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GateAccessService
{
    public function __construct(
        protected QrTicketService $qrTicketService
    ) {
    }

    /**
     * Memproses scan QR oleh petugas gerbang.
     *
     * Jika QR valid:
     * - membuat AccessLog BERHASIL
     * - mengubah status QR menjadi SUDAH_DIPAKAI
     * - memberikan hasil akses diterima
     *
     * Jika QR tidak valid:
     * - membuat AccessLog GAGAL
     * - QR tidak diberikan akses
     */
    public function scan(
        string $payloadSigned,
        PetugasGerbang $petugas
    ): array {
        /*
         * Pastikan petugas aktif.
         */
        if (!$petugas->aktif) {
            return [
                'success' => false,
                'hasil' => 'GAGAL',
                'reason' => 'PETUGAS_TIDAK_AKTIF',
                'message' => 'Petugas gerbang tidak aktif.',
            ];
        }

        /*
         * Validasi QR terlebih dahulu.
         */
        $hasilValidasi = $this->qrTicketService->validate(
            $payloadSigned
        );

        /*
         * Jika QR tidak valid.
         */
        if (!$hasilValidasi['valid']) {

            $qrTicket = $hasilValidasi['qr_ticket'] ?? null;

            /*
             * Catat kegagalan scan jika QR
             * masih ditemukan di database.
             */
            if ($qrTicket) {
                AccessLog::create([
                    'qr_ticket_id' => $qrTicket->id,
                    'petugas_id' => $petugas->id,
                    'waktu_scan' => now(),
                    'hasil_validasi' => 'GAGAL',
                    'catatan' => $hasilValidasi['reason'],
                ]);
            }

            return [
                'success' => false,
                'hasil' => 'GAGAL',
                'reason' => $hasilValidasi['reason'],
                'message' => $this->messageForReason(
                    $hasilValidasi['reason']
                ),
                'qr_ticket' => $qrTicket,
            ];
        }

        /*
         * QR valid.
         *
         * Sekarang lakukan proses dalam transaction
         * supaya perubahan status QR dan AccessLog
         * konsisten.
         */
        return DB::transaction(function () use (
            $hasilValidasi,
            $petugas
        ) {

            $qrTicket = $hasilValidasi['qr_ticket'];

            /*
             * Lock QR untuk mencegah dua petugas
             * memproses QR yang sama secara bersamaan.
             */
            $qrTicket = $qrTicket->newQuery()
                ->lockForUpdate()
                ->find($qrTicket->id);

            if (!$qrTicket) {
                throw new RuntimeException(
                    'QR Ticket tidak ditemukan.'
                );
            }

            /*
             * Cek kembali status QR setelah mendapatkan lock.
             *
             * Ini penting untuk mencegah double scan.
             */
            if ($qrTicket->status_qr !== 'AKTIF') {

                AccessLog::create([
                    'qr_ticket_id' => $qrTicket->id,
                    'petugas_id' => $petugas->id,
                    'waktu_scan' => now(),
                    'hasil_validasi' => 'GAGAL',
                    'catatan' => 'QR sudah tidak aktif.',
                ]);

                return [
                    'success' => false,
                    'hasil' => 'GAGAL',
                    'reason' => 'QR_TIDAK_AKTIF',
                    'message' => 'QR sudah tidak aktif atau sudah digunakan.',
                    'qr_ticket' => $qrTicket,
                ];
            }

            /*
             * Cek kembali masa berlaku QR.
             */
            if ($qrTicket->waktu_expired->isPast()) {

                $qrTicket->update([
                    'status_qr' => 'INVALID',
                ]);

                AccessLog::create([
                    'qr_ticket_id' => $qrTicket->id,
                    'petugas_id' => $petugas->id,
                    'waktu_scan' => now(),
                    'hasil_validasi' => 'GAGAL',
                    'catatan' => 'QR sudah kedaluwarsa.',
                ]);

                return [
                    'success' => false,
                    'hasil' => 'GAGAL',
                    'reason' => 'QR_EXPIRED',
                    'message' => 'QR sudah kedaluwarsa.',
                    'qr_ticket' => $qrTicket,
                ];
            }

            /*
             * QR valid.
             *
             * Catat akses terlebih dahulu.
             */
            $accessLog = AccessLog::create([
                'qr_ticket_id' => $qrTicket->id,
                'petugas_id' => $petugas->id,
                'waktu_scan' => now(),
                'hasil_validasi' => 'BERHASIL',
                'catatan' => 'QR valid dan akses diberikan.',
            ]);

            /*
             * Setelah AccessLog berhasil dibuat,
             * QR menjadi single-use.
             */
            $qrTicket->update([
                'status_qr' => 'SUDAH_DIPAKAI',
            ]);

            /*
             * Hasil akhir.
             */
            return [
                'success' => true,
                'hasil' => 'BERHASIL',
                'reason' => 'QR_VALID',
                'message' => 'QR valid. Akses diberikan.',
                'qr_ticket' => $qrTicket->fresh(),
                'access_log' => $accessLog,
            ];
        });
    }

    /**
     * Pesan yang ditampilkan kepada petugas
     * berdasarkan alasan QR ditolak.
     */
    protected function messageForReason(string $reason): string
    {
        return match ($reason) {
            'FORMAT_QR_TIDAK_VALID'
                => 'Format QR tidak valid.',

            'SIGNATURE_TIDAK_VALID'
                => 'QR tidak valid atau telah dimodifikasi.',

            'PAYLOAD_TIDAK_VALID'
                => 'Data QR tidak valid.',

            'PAYLOAD_TIDAK_LENGKAP'
                => 'Data QR tidak lengkap.',

            'QR_EXPIRED'
                => 'QR sudah kedaluwarsa.',

            'QR_TIDAK_DITEMUKAN'
                => 'QR tidak ditemukan.',

            'BOOKING_TIDAK_SESUAI'
                => 'QR tidak sesuai dengan booking.',

            'QR_TIDAK_AKTIF'
                => 'QR sudah tidak aktif atau sudah digunakan.',

            'PETUGAS_TIDAK_AKTIF'
                => 'Petugas gerbang tidak aktif.',

            default
                => 'QR tidak dapat divalidasi.',
        };
    }
}