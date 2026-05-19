<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wigs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_wig', 30)->unique();
            $table->string('nama_wig');
            $table->text('indikator_output')->nullable();
            $table->year('tahun');
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayahs')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wigs');
    }
};
