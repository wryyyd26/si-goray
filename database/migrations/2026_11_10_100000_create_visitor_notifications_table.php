<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitor_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_key', 190)->unique();
            $table->string('category', 30);
            $table->string('title', 150);
            $table->text('message');
            $table->string('icon', 50)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at', 'created_at'], 'visitor_notification_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_notifications');
    }
};
