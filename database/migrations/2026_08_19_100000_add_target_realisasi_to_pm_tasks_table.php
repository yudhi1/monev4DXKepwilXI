<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_tasks', function (Blueprint $table) {
            /*
             | Target & realisasi bersifat opsional: hanya diisi untuk pekerjaan
             | yang memang punya angka (mis. penagihan Rp, kolekting badan
             | usaha). Task tanpa angka — "susun narasi laporan" — tetap
             | memakai progress manual.
             |
             | Sengaja SEKALI per task, bukan riwayat per periode: pelacakan
             | berkala sudah menjadi tugas modul 4DX (wig_realisasis dan
             | lead_measure_realisasis), dan menduplikasinya di sini akan
             | membuat dua sumber angka untuk hal yang sama.
             */
            $table->string('satuan', 20)->nullable()->after('bobot');
            $table->decimal('target', 18, 2)->nullable()->after('satuan');
            $table->decimal('realisasi', 18, 2)->nullable()->after('target');
        });
    }

    public function down(): void
    {
        Schema::table('pm_tasks', function (Blueprint $table) {
            $table->dropColumn(['satuan', 'target', 'realisasi']);
        });
    }
};
