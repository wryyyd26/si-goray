<?php

namespace App\Services;

use App\Models\Identity;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class IdentityService
{
    public function __construct(
        protected WargaKotaValidator $wargaKotaValidator
    ) {
    }

    /**
     * Membuat identity baru sekaligus melakukan
     * validasi warga kota secara lokal.
     *
     * NIK:
     * - disimpan terenkripsi melalui model cast
     * - dibuatkan HMAC hash untuk pencarian/keunikan
     *
     * Tanggal lahir:
     * - disimpan terenkripsi melalui model cast
     */
    public function createIdentity(
        string $userId,
        string $nik,
        string $tanggalLahir
    ): Identity {
        return DB::transaction(function () use (
            $userId,
            $nik,
            $tanggalLahir
        ) {

            /*
             * 1. Bersihkan input NIK dari spasi.
             */
            $nik = trim($nik);

            /*
             * 2. Validasi NIK dan tanggal lahir.
             *
             * Untuk sementara menggunakan validator lokal.
             */
            $hasilValidasi = $this->wargaKotaValidator->validate(
                $nik,
                $tanggalLahir
            );

            /*
             * Jika validasi gagal, identity tidak dibuat.
             */
            if (!$hasilValidasi['valid']) {
                throw new InvalidArgumentException(
                    $hasilValidasi['message']
                    ?? 'Identitas tidak valid.'
                );
            }

            /*
             * 3. Ambil secret khusus untuk hash NIK.
             */
            $hashKey = config('identity.hash_key');

            if (!$hashKey) {
                throw new RuntimeException(
                    'IDENTITY_HASH_KEY belum tersedia.'
                );
            }

            /*
             * 4. Buat HMAC SHA-256 dari NIK.
             *
             * NIK asli tidak disimpan sebagai hash biasa.
             */
            $nikHash = hash_hmac(
                'sha256',
                $nik,
                $hashKey
            );

            /*
             * 5. Cek apakah NIK sudah terdaftar.
             *
             * Kita menggunakan nik_hash karena
             * NIK asli terenkripsi dan tidak cocok
             * untuk pencarian langsung.
             */
            $identitySudahAda = Identity::where(
                'nik_hash',
                $nikHash
            )->exists();

            if ($identitySudahAda) {
                throw new InvalidArgumentException(
                    'NIK sudah terdaftar.'
                );
            }

            /*
             * 6. Buat identity.
             *
             * nik_enc dan tanggal_lahir_enc akan
             * otomatis dienkripsi oleh Laravel
             * karena menggunakan cast "encrypted".
             */
            return Identity::create([
                'user_id' => $userId,

                'nik_enc' => $nik,

                'nik_hash' => $nikHash,

                'tanggal_lahir_enc' => $tanggalLahir,

                'status_warga_kota' =>
                    $hasilValidasi['status_warga_kota'],

                'sumber_validasi' =>
                    $hasilValidasi['sumber_validasi'],

                'divalidasi_at' => now(),
            ]);
        });
    }
}