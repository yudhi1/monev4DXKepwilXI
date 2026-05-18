<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->text('nama_lag')->change();
        });
    }

    public function down(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->string('nama_lag', 150)->change();
        });
    }
};
