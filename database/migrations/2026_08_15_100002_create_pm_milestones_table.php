<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->date('target_tanggal')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_milestones');
    }
};
