<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Target bulanan WIG. Sebelumnya hanya ada target tahunan di wig_targets,
 * sehingga capaian per bulan tidak bisa diukur terhadap rencananya.
 *
 * Ditaruh pada tabel realisasi karena barisnya sudah unik per
 * (wig, cabang, tahun, bulan) — target dan realisasi bulan yang sama
 * jadi berdampingan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wig_realisasis', function (Blueprint $table) {
            $table->decimal('target', 20, 2)->default(0)->after('bulan');
        });
    }

    public function down(): void
    {
        Schema::table('wig_realisasis', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};
