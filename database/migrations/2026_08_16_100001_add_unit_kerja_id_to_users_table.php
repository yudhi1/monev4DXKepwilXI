<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             | Bidang tempat pegawai bertugas. Nullable karena akun institusi
             | lama (admin, kepwil, kc.*) yang dipakai modul 4DX tidak berada
             | di satu bidang tertentu.
             */
            $table->foreignId('unit_kerja_id')
                ->nullable()
                ->after('cabang_id')
                ->constrained('unit_kerjas')
                ->nullOnDelete();

            $table->string('jabatan', 100)->nullable()->after('unit_kerja_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn(['unit_kerja_id', 'jabatan']);
        });
    }
};
