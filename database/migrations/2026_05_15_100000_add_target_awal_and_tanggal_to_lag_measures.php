<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->decimal('target_awal', 20, 2)->default(0)->after('nama_lag');
            $table->date('tanggal_target')->nullable()->after('satuan');
        });
    }

    public function down(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->dropColumn(['target_awal', 'tanggal_target']);
        });
    }
};
