<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wig_realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wig_id')->constrained('wigs')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabangs')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->decimal('nilai', 20, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['wig_id', 'cabang_id', 'tahun', 'bulan'], 'wig_realisasi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wig_realisasis');
    }
};
