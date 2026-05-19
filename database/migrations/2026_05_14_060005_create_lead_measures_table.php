<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_measures', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lead', 30)->unique();
            $table->foreignId('lag_measure_id')->constrained('lag_measures')->cascadeOnDelete();
            $table->foreignId('wig_id')->constrained('wigs')->cascadeOnDelete();
            $table->string('nama_lead');
            $table->string('satuan', 30)->nullable();
            $table->year('tahun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_measures');
    }
};
