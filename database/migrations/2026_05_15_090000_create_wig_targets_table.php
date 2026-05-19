<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wig_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wig_id')->constrained('wigs')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabangs')->cascadeOnDelete();
            $table->decimal('nilai_awal', 20, 2)->default(0);
            $table->decimal('nilai_target', 20, 2)->default(0);
            $table->string('satuan', 30)->default('Rp');
            $table->date('tanggal_target')->nullable();
            $table->timestamps();
            $table->unique(['wig_id', 'cabang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wig_targets');
    }
};
