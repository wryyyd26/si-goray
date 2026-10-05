<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petugas_gerbang', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nama');

            $table->string('username')
                ->unique();

            $table->string('no_hp')
                ->nullable();

            $table->boolean('aktif')
                ->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petugas_gerbang');
    }
};