<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('identity_id')
                ->constrained('identities')
                ->cascadeOnDelete();

            $table->string('jenis_booking');

            $table->foreignUuid('regular_slot_id')
    ->nullable()
    ->constrained('regular_slots')
    ->restrictOnDelete();

            $table->foreignUuid('event_schedule_id')
    ->nullable()
    ->constrained('event_schedules')
    ->restrictOnDelete();

            $table->foreignUuid('tariff_id')
                ->constrained('tariff_reference')
                ->restrictOnDelete();

            $table->boolean('retribusi_diperlukan')
                ->default(false);

            $table->string('status_booking');

            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT chk_booking_target
            CHECK (
                (regular_slot_id IS NOT NULL AND event_schedule_id IS NULL)
                OR
                (regular_slot_id IS NULL AND event_schedule_id IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};