
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas_gerbang', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable()
                ->unique('uq_petugas_gerbang_user_id')
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('petugas_gerbang', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('uq_petugas_gerbang_user_id');
            $table->dropColumn('user_id');
        });
    }
};
