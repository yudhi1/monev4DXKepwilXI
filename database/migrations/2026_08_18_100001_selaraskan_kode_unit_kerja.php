<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan kode bidang dengan penamaan resmi organisasi.
 *
 * PIKUE → PIKEU, dan SDMUK di kantor cabang → SDMU (di Kedeputian Wilayah
 * tetap SDMUK). Nama tampilnya juga dirapikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('unit_kerjas')->where('kode', 'PIKUE')->update([
            'kode' => 'PIKEU',
            'nama' => 'Bidang PIKEU',
        ]);

        DB::table('unit_kerjas')
            ->where('kode', 'SDMUK')
            ->where('tingkat', 'cabang')
            ->update(['kode' => 'SDMU', 'nama' => 'Bidang SDMU']);

        DB::table('unit_kerjas')->where('kode', 'YANFASKES')->update(['nama' => 'Bidang Yanfaskes']);
    }

    public function down(): void
    {
        DB::table('unit_kerjas')->where('kode', 'PIKEU')->update([
            'kode' => 'PIKUE',
            'nama' => 'Bidang PIKUE',
        ]);

        DB::table('unit_kerjas')
            ->where('kode', 'SDMU')
            ->where('tingkat', 'cabang')
            ->update(['kode' => 'SDMUK', 'nama' => 'Bidang SDMUK']);

        DB::table('unit_kerjas')->where('kode', 'YANFASKES')->update(['nama' => 'Bidang Yanfasskes']);
    }
};
