<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Percobaan pengerjaan quiz oleh seorang peserta.
 |
 | Urutan soal dan opsi hasil pengacakan disimpan di sini, bukan dihitung
 | ulang tiap permintaan: tanpa itu, menyegarkan halaman akan mengacak ulang
 | soal dan jawaban yang sudah terisi jadi tidak nyambung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_quiz_percobaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('pm_quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // berjalan | selesai
            $table->string('status', 20)->default('berjalan');

            $table->timestamp('mulai_pada');
            $table->timestamp('selesai_pada')->nullable();
            // Batas waktu dibekukan saat mulai agar tidak ikut berubah bila
            // durasi quiz diubah di tengah pengerjaan.
            $table->timestamp('batas_pada')->nullable();

            $table->json('urutan_soal');
            $table->json('urutan_opsi');

            $table->unsignedSmallInteger('jumlah_soal')->default(0);
            $table->unsignedSmallInteger('jumlah_benar')->default(0);
            $table->unsignedSmallInteger('poin_didapat')->default(0);
            $table->unsignedSmallInteger('poin_maksimal')->default(0);
            $table->unsignedTinyInteger('skor')->default(0);
            // Durasi pengerjaan dalam detik — dipakai sebagai pemecah seri peringkat.
            $table->unsignedInteger('durasi_detik')->nullable();

            $table->timestamps();

            $table->index(['quiz_id', 'user_id']);
            $table->index(['quiz_id', 'skor']);
        });

        Schema::create('pm_quiz_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('percobaan_id')->constrained('pm_quiz_percobaans')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('pm_quiz_soals')->cascadeOnDelete();
            $table->foreignId('opsi_id')->nullable()->constrained('pm_quiz_opsis')->nullOnDelete();
            $table->boolean('benar')->default(false);
            $table->timestamps();

            // Satu jawaban per soal dalam satu percobaan.
            $table->unique(['percobaan_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_quiz_jawabans');
        Schema::dropIfExists('pm_quiz_percobaans');
    }
};
