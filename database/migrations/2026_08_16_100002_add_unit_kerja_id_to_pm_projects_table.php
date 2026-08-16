<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_projects', function (Blueprint $table) {
            /*
             | Unit kerja pemilik project. Nullable agar project lama tetap
             | valid; project baru selalu diisi dari unit kerja pembuatnya.
             */
            $table->foreignId('unit_kerja_id')
                ->nullable()
                ->after('pemilik_id')
                ->constrained('unit_kerjas')
                ->nullOnDelete();

            $table->index('unit_kerja_id');
        });
    }

    public function down(): void
    {
        Schema::table('pm_projects', function (Blueprint $table) {
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn('unit_kerja_id');
        });
    }
};
