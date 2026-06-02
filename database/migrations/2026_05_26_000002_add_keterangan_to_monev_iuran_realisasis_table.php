<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monev_iuran_realisasis', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('realisasi_sd_bulan_lalu');
        });
    }

    public function down(): void
    {
        Schema::table('monev_iuran_realisasis', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
