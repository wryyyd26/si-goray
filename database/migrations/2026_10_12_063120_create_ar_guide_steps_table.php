<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('ar_guide_steps', function (Blueprint $table) {
    $table->id();

    $table->foreignId('ar_guide_id')
        ->constrained('ar_guides')
        ->cascadeOnDelete();

    $table->string('title');
    $table->text('instruction');
    $table->string('screen_image_path')->nullable();
    $table->unsignedInteger('step_order')->default(1);
    $table->string('animation_type')->default('highlight');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_guide_steps');
    }
};
