<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('iuran_monitorings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->unsignedTinyInteger('minggu');
            $table->unsignedSmallInteger('no_urut')->default(1);
            $table->string('nama_pemda');
            $table->decimal('tagihan', 20, 2)->default(0);
            $table->enum('status_bayar', ['sudah', 'sebagian', 'belum'])->default('belum');
            $table->decimal('outstanding', 20, 2)->default(0);
            $table->string('pic')->nullable();
            $table->text('kendala')->nullable();
            $table->date('target_penyelesaian')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tahun', 'bulan', 'minggu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_monitorings');
    }
};
