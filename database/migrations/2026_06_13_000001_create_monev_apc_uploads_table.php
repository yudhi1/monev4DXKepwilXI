<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monev_apc_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('indikator', 50)->index();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('original_name');
            $table->string('file_path');
            $table->json('sheet_json')->nullable();
            $table->unsignedInteger('rows_count')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_apc_uploads');
    }
};
