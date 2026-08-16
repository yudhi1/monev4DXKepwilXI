<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('nama', 100);

            // 'wilayah' = bidang di Kedeputian Wilayah, 'cabang' = bidang di kantor cabang.
            $table->string('tingkat', 10);

            /*
             | Bidang di kantor cabang berdiri sendiri per cabang: "Bidang PMU
             | KC Denpasar" adalah unit yang berbeda dari "Bidang PMU KC Kupang".
             | cabang_id null berarti unit tersebut berada di Kedeputian Wilayah.
             */
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayahs')->nullOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->cascadeOnDelete();

            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['kode', 'cabang_id']);
            $table->index(['tingkat', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_kerjas');
    }
};
