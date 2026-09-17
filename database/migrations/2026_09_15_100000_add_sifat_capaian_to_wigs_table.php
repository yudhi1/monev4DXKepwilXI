<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sifat pengukuran dan arah keberhasilan tiap WIG.
 *
 * Tanpa keduanya, capaian "s.d. bulan" selalu dihitung sebagai penjumlahan —
 * benar untuk penerimaan iuran, keliru untuk jumlah peserta aktif (posisi)
 * maupun persentase kepatuhan (kadar per bulan). Pilihannya ada di
 * config/wig.php, bukan enum MySQL, agar bisa bertambah tanpa migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wigs', function (Blueprint $table) {
            $table->string('sifat_capaian', 12)->default('akumulatif')->after('indikator_output');
            $table->string('arah', 6)->default('naik')->after('sifat_capaian');
        });

        /*
         | Penetapan awal untuk WIG yang sudah berjalan, dibaca dari pola
         | angkanya: KML mencatat posisi peserta aktif, SDMUK mencatat kadar
         | kepatuhan per bulan, JPK menjumlah biaya yang justru ingin ditekan.
         */
        DB::table('wigs')->where('bidang', 'KML')->update(['sifat_capaian' => 'posisi']);
        DB::table('wigs')->where('bidang', 'SDMUK')->update(['sifat_capaian' => 'periodik', 'arah' => 'naik']);
        DB::table('wigs')->where('bidang', 'JPK')->update(['sifat_capaian' => 'akumulatif', 'arah' => 'turun']);
    }

    public function down(): void
    {
        Schema::table('wigs', function (Blueprint $table) {
            $table->dropColumn(['sifat_capaian', 'arah']);
        });
    }
};
