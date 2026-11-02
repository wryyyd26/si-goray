<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saran_masukan', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 150);
            $table->string('email', 254);
            $table->string('whatsapp', 25)->nullable();

            $table->enum('category', [
                'feedback',
                'reservasi',
            ]);

            $table->string('subject', 200);
            $table->text('message');

            $table->enum('status', [
                'baru',
                'diproses',
                'selesai',
            ])->default('baru');

            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saran_masukan');
    }
};
