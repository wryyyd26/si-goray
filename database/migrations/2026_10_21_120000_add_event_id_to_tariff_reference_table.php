<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariff_reference', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->after('id');
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->nullOnDelete();

            $table->index(
                ['event_id', 'jenis_pengunjung', 'aktif', 'berlaku_mulai'],
                'tariff_event_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tariff_reference', function (Blueprint $table) {
            $table->dropIndex('tariff_event_lookup_idx');
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};
