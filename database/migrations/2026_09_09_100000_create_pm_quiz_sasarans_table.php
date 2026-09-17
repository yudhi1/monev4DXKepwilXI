<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Sasaran peserta quiz.
 |
 | `pm_quizzes.sasaran_tipe` menentukan makna `sasaran_id` di tabel ini —
 | id cabang, id unit kerja (bidang), atau id pegawai. Tipe `semua` tidak
 | menyimpan baris apa pun di sini.
 |
 | Sengaja tidak memakai tiga kolom terpisah (cabang_id/unit_kerja_id/user_id):
 | sebuah quiz hanya boleh disasar dengan satu cara, dan tiga kolom nullable
 | membuka peluang baris yang mengisi dua-duanya sekaligus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_quizzes', function (Blueprint $table) {
            $table->string('sasaran_tipe', 20)->default('semua')->after('status');
        });

        Schema::create('pm_quiz_sasarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('pm_quizzes')->cascadeOnDelete();
            $table->unsignedBigInteger('sasaran_id');
            $table->timestamps();

            $table->unique(['quiz_id', 'sasaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_quiz_sasarans');

        Schema::table('pm_quizzes', function (Blueprint $table) {
            $table->dropColumn('sasaran_tipe');
        });
    }
};
