<?php

namespace App\Services;

use Carbon\Carbon;

class WargaKotaValidator
{
    /**
     * Kode wilayah administrasi Kota Mojokerto.
     *
     * 3576 = Kota Mojokerto
     *
     * 357601 = Prajurit Kulon
     * 357602 = Magersari
     * 357603 = Kranggan
     */
    protected const KODE_WILAYAH_KOTA_MOJOKERTO = [
        '357601',
        '357602',
        '357603',
    ];

    /**
     * Validasi NIK dan tanggal lahir secara lokal.
     *
     * Untuk sementara belum menggunakan API Dukcapil.
     *
     * @param string $nik
     * @param string $tanggalLahir Format: Y-m-d
     */
    public function validate(
        string $nik,
        string $tanggalLahir
    ): array {
        /*
         * 1. NIK harus tepat 16 digit.
         */
        if (!preg_match('/^\d{16}$/', $nik)) {
            return [
                'valid' => false,
                'reason' => 'NIK_TIDAK_VALID',
                'message' => 'NIK harus terdiri dari 16 digit.',
            ];
        }

        /*
         * 2. Ambil 6 digit pertama NIK.
         *
         * Contoh:
         * 357602xxxxxxxxxx
         *
         * 357602 = kode wilayah.
         */
        $kodeWilayah = substr($nik, 0, 6);

        /*
         * 3. Ambil bagian tanggal lahir dari NIK.
         *
         * Posisi:
         * digit 7-8  = tanggal
         * digit 9-10 = bulan
         * digit 11-12 = tahun
         *
         * Untuk perempuan, tanggal ditambah 40.
         */
        $tanggalNik = (int) substr($nik, 6, 2);
        $bulanNik = (int) substr($nik, 8, 2);
        $tahunNik = (int) substr($nik, 10, 2);

        /*
         * 4. Tentukan tanggal sebenarnya.
         *
         * Perempuan:
         * 41-71 -> kurangi 40.
         *
         * Laki-laki:
         * 01-31.
         */
        if ($tanggalNik >= 41 && $tanggalNik <= 71) {
            $tanggalNik -= 40;
        }

        /*
         * Setelah penyesuaian, tanggal harus 1-31.
         */
        if ($tanggalNik < 1 || $tanggalNik > 31) {
            return [
                'valid' => false,
                'reason' => 'TANGGAL_LAHIR_NIK_TIDAK_VALID',
                'message' => 'Tanggal lahir pada NIK tidak valid.',
            ];
        }

        /*
         * 5. Bulan harus 1-12.
         */
        if ($bulanNik < 1 || $bulanNik > 12) {
            return [
                'valid' => false,
                'reason' => 'BULAN_LAHIR_NIK_TIDAK_VALID',
                'message' => 'Bulan lahir pada NIK tidak valid.',
            ];
        }

        /*
         * 6. Validasi tanggal lahir yang diberikan user.
         *
         * Format yang kita gunakan:
         * YYYY-MM-DD
         */
        try {
            $tanggalLahirInput = Carbon::createFromFormat(
                'Y-m-d',
                $tanggalLahir
            );

            if (
                !$tanggalLahirInput ||
                $tanggalLahirInput->format('Y-m-d') !== $tanggalLahir
            ) {
                return [
                    'valid' => false,
                    'reason' => 'FORMAT_TANGGAL_LAHIR_TIDAK_VALID',
                    'message' => 'Format tanggal lahir harus YYYY-MM-DD.',
                ];
            }
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'reason' => 'FORMAT_TANGGAL_LAHIR_TIDAK_VALID',
                'message' => 'Tanggal lahir tidak valid.',
            ];
        }

        /*
         * 7. Cocokkan tanggal dan bulan NIK
         * dengan tanggal lahir yang diberikan.
         */
        if (
            (int) $tanggalLahirInput->format('d') !== $tanggalNik ||
            (int) $tanggalLahirInput->format('m') !== $bulanNik ||
            (int) $tanggalLahirInput->format('y') !== $tahunNik
        ) {
            return [
                'valid' => false,
                'reason' => 'TANGGAL_LAHIR_TIDAK_SESUAI',
                'message' => 'Tanggal lahir tidak sesuai dengan NIK.',
            ];
        }

        /*
         * 8. Tentukan apakah kode wilayah
         * termasuk wilayah Kota Mojokerto.
         */
        $statusWargaKota = in_array(
            $kodeWilayah,
            self::KODE_WILAYAH_KOTA_MOJOKERTO,
            true
        );

        /*
         * 9. Semua validasi dasar berhasil.
         */
        return [
            'valid' => true,
            'status_warga_kota' => $statusWargaKota,
            'sumber_validasi' => 'local',
            'kode_wilayah' => $kodeWilayah,
        ];
    }
}