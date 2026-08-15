<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_projects', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();

            // Nilai dari config/pm.php, bukan enum, agar bisa ditambah tanpa migrasi.
            $table->string('status', 20)->default('perencanaan');
            $table->string('prioritas', 10)->default('sedang');

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            // Project berdiri bebas: tidak terikat wilayah/cabang.
            $table->foreignId('pemilik_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();

            $table->index(['status', 'prioritas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_projects');
    }
};
