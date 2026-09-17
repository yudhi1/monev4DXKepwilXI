<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indikator capaian kinerja, selalu berada di bawah satu kategori.
 *
 * Nama indikator unik per kategori, bukan unik global: indikator bernama sama
 * boleh hidup di dua kategori berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kinerja_indikators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kinerja_kategori_id')->constrained('kinerja_kategoris')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['kinerja_kategori_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kinerja_indikators');
    }
};
