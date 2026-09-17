<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori capaian kinerja — lapis teratas modul Monitoring Kinerja.
 *
 * Isinya ditentukan pengguna (Admin/Kedeputian Wilayah), bukan konstanta di
 * kode, karena daftar kategori berubah mengikuti kebijakan tiap tahun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kinerja_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('keterangan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kinerja_kategoris');
    }
};
