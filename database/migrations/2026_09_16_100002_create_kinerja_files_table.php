<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berkas capaian kinerja yang diunggah per indikator dan per bulan.
 *
 * Berkas fisiknya ada di disk privat (storage/app/private/kinerja-files),
 * bukan di public/, supaya hanya bisa diambil lewat rute unduh yang sudah
 * melewati middleware modul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kinerja_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kinerja_indikator_id')->constrained('kinerja_indikators')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('file_path', 255);
            $table->string('nama_asli', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('ukuran')->default(0);
            $table->text('keterangan')->nullable();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kinerja_files');
    }
};
