<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->dropColumn(['target_awal', 'target_tahunan', 'satuan']);
        });
    }

    public function down(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->decimal('target_awal', 20, 2)->default(0);
            $table->decimal('target_tahunan', 15, 2)->default(0);
            $table->string('satuan', 30)->nullable();
        });
    }
};
