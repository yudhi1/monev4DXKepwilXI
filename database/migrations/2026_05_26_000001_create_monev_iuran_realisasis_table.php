<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monev_iuran_realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('segmen_id')->constrained('monev_segmens')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->decimal('mg1', 20, 2)->default(0);
            $table->decimal('mg2', 20, 2)->default(0);
            $table->decimal('mg3', 20, 2)->default(0);
            $table->decimal('mg4', 20, 2)->default(0);
            $table->decimal('realisasi_sd_bulan_lalu', 20, 2)->default(0);
            $table->enum('status_periode', ['draft', 'final'])->default('draft');
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cabang_id', 'segmen_id', 'tahun', 'bulan']);
            $table->index(['tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_iuran_realisasis');
    }
};
