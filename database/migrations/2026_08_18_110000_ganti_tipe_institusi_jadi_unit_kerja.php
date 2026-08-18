<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti nilai users.tipe dari 'institusi' menjadi 'unit_kerja'.
 *
 * Akun jenis ini memang mewakili sebuah unit kerja (kantor), dan istilah itu
 * yang dipakai sehari-hari di organisasi — form-nya pun berlabel "Nama Unit
 * Kerja".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Default dilepas dulu agar nilai lama bisa diubah tanpa bentrok.
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipe', 12)->default('unit_kerja')->change();
        });

        DB::table('users')->where('tipe', 'institusi')->update(['tipe' => 'unit_kerja']);
    }

    public function down(): void
    {
        DB::table('users')->where('tipe', 'unit_kerja')->update(['tipe' => 'institusi']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('tipe', 12)->default('institusi')->change();
        });
    }
};
