
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration duplikat yang sudah tercatat di database.
 * Dipertahankan sebagai migration kosong agar riwayat
 * migration yang sudah berjalan tidak diubah.
 */

return new class extends Migration
{
    public function up(): void
    {
        // Tidak ada perubahan skema.
    }

    public function down(): void
    {
        // Tidak ada perubahan skema.
    }
};
