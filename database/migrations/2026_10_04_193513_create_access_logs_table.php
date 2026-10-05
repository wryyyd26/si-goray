<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('qr_ticket_id')
                ->constrained('qr_tickets')
                ->restrictOnDelete();

            $table->foreignUuid('petugas_id')
                ->constrained('petugas_gerbang')
                ->restrictOnDelete();

            $table->timestamp('waktu_scan');

            $table->string('hasil_validasi');

            $table->text('catatan')
                ->nullable();

            $table->index('waktu_scan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};