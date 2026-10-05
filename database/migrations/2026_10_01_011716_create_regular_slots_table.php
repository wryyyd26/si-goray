<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regular_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->date('tanggal');

            $table->time('jam_mulai');

            $table->time('jam_selesai');

            $table->unsignedInteger('kapasitas');

            $table->unsignedInteger('kuota_terpakai')
                ->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regular_slots');
    }
};