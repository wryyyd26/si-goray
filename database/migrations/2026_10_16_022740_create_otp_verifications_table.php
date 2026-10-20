<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('kode_otp_hash');

            $table->string('tujuan');

            $table->string('status')
                ->default('PENDING');

            $table->unsignedTinyInteger('percobaan_ke')
                ->default(0);

            $table->timestamp('expired_at');

            $table->timestamps();

            $table->index(
                ['user_id', 'status', 'expired_at'],
                'idx_otp_user_status_expired'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
