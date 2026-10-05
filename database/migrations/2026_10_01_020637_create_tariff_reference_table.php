<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_reference', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('jenis_pengunjung');

            $table->decimal('nominal', 12, 2);

            $table->date('berlaku_mulai');

            $table->boolean('aktif')
                ->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_reference');
    }
};