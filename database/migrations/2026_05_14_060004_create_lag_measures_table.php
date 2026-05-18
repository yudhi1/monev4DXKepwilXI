<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lag_measures', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lag', 30)->unique();
            $table->foreignId('wig_id')->constrained('wigs')->cascadeOnDelete();
            $table->string('nama_lag');
            $table->decimal('target_tahunan', 15, 2)->default(0);
            $table->string('satuan', 30)->nullable();
            $table->year('tahun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lag_measures');
    }
};
