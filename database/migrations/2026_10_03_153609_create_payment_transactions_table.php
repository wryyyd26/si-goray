<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->string('kode_bayar');

            $table->string('metode_pembayaran');

            $table->decimal('nominal', 12, 2);

            $table->string('status_pembayaran');

            $table->unsignedTinyInteger('jumlah_retry')
                ->default(0);

            $table->string('referensi_gateway')
                ->nullable();

            $table->timestamp('expired_at')
                ->nullable();

            $table->timestamp('created_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};