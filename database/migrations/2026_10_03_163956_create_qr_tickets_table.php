<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->text('payload_signed');

            $table->string('status_qr');

            $table->foreignUuid('regenerated_from_qr_id')
                ->nullable()
                ->constrained('qr_tickets')
                ->restrictOnDelete();

            $table->timestamp('waktu_terbit');

            $table->timestamp('waktu_expired');

            /*
             * Satu QR lama hanya boleh memiliki satu QR pengganti.
             */
            $table->unique(
                'regenerated_from_qr_id',
                'uq_qr_regenerated_from'
            );

            /*
             * Satu booking hanya boleh memiliki satu QR AKTIF.
             *
             * Kolom generated ini bernilai booking_id hanya ketika
             * status_qr = AKTIF, dan NULL untuk status lainnya.
             * UNIQUE memungkinkan banyak NULL tetapi hanya satu
             * booking_id yang sama pada status AKTIF.
             */
            $table->uuid('active_booking_id')
                ->nullable()
                ->storedAs("IF(status_qr = 'AKTIF', booking_id, NULL)");

            $table->unique(
                'active_booking_id',
                'uq_qr_aktif_per_booking'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_tickets');
    }
};