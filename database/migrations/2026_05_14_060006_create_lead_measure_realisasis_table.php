<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_measure_realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_measure_id')->constrained('lead_measures')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabangs')->cascadeOnDelete();
            $table->year('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->unsignedTinyInteger('minggu_ke');
            $table->decimal('target', 15, 2)->default(0);
            $table->decimal('realisasi', 15, 2)->default(0);
            $table->decimal('persentase', 8, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lead_measure_id', 'cabang_id', 'tahun', 'bulan', 'minggu_ke'], 'realisasi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_measure_realisasis');
    }
};
