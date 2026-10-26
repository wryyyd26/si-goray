<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unique(
                'kode_bayar',
                'uq_payment_kode_bayar'
            );
        });

        DB::statement("
            ALTER TABLE payment_transactions
            ADD COLUMN pending_booking_id CHAR(36)
            GENERATED ALWAYS AS (
                CASE
                    WHEN status_pembayaran = 'PENDING'
                    THEN booking_id
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement("
            CREATE UNIQUE INDEX uq_payment_pending_per_booking
            ON payment_transactions (pending_booking_id)
        ");
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX uq_payment_pending_per_booking
            ON payment_transactions
        ");

        DB::statement("
            ALTER TABLE payment_transactions
            DROP COLUMN pending_booking_id
        ");

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('uq_payment_kode_bayar');
        });
    }
};
