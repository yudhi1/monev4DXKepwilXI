<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Quiz modul Project Management.
 |
 | Berdiri sendiri, tidak menempel pada project: quiz dipakai untuk menguji
 | pemahaman pegawai (mis. sosialisasi regulasi baru), pesertanya lintas
 | project. `unit_kerja_id` hanya penanda asal, bukan pembatas peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();

            // draf | terbit | ditutup — hanya yang `terbit` bisa dikerjakan.
            $table->string('status', 20)->default('draf');

            /*
             | Timer. Null berarti tanpa batas waktu; batas dihitung server dari
             | waktu mulai percobaan, bukan dari jam di browser peserta.
             */
            $table->unsignedSmallInteger('durasi_menit')->nullable();

            /*
             | Pengacakan. `jumlah_soal` mengambil sebagian bank soal secara
             | acak — null berarti semua soal dipakai.
             */
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_opsi')->default(true);
            $table->unsignedSmallInteger('jumlah_soal')->nullable();

            $table->unsignedTinyInteger('nilai_lulus')->default(70);
            // Null berarti peserta boleh mengulang sebanyak-banyaknya.
            $table->unsignedTinyInteger('maks_percobaan')->default(1);
            // Hasil dan pembahasan boleh disembunyikan sampai quiz ditutup.
            $table->boolean('tampilkan_pembahasan')->default(true);

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_quizzes');
    }
};
