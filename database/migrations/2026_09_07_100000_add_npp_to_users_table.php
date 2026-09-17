<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | NPP — nomor pokok pegawai, dipakai akun pegawai untuk login ke modul
 | Project Management. Nullable karena akun unit kerja (Monev 4DX) tidak
 | punya NPP dan pegawai lama belum terisi; unique agar tetap bisa jadi
 | identitas login yang tunggal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('npp', 20)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['npp']);
            $table->dropColumn('npp');
        });
    }
};
