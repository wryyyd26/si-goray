<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identities', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('nik_enc');

            $table->string('nik_hash', 64)
                ->unique();

            $table->text('tanggal_lahir_enc');

            $table->boolean('status_warga_kota')
                ->nullable();

            $table->string('sumber_validasi')
                ->default('local');

            $table->timestamp('divalidasi_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identities');
    }
};