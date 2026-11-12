<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_key', 190)->unique();
            $table->string('type', 40);
            $table->string('title', 160);
            $table->text('message');
            $table->string('icon', 50);
            $table->string('url', 500);
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
