<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_quiz_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('pm_quizzes')->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->text('pembahasan')->nullable();
            $table->unsignedSmallInteger('poin')->default(1);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'urutan']);
        });

        Schema::create('pm_quiz_opsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('pm_quiz_soals')->cascadeOnDelete();
            $table->text('teks');
            $table->boolean('benar')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['soal_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_quiz_opsis');
        Schema::dropIfExists('pm_quiz_soals');
    }
};
