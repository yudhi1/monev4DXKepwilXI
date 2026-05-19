<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wigs', function (Blueprint $table) {
            $table->string('bidang', 20)->nullable()->after('indikator_output');
        });
    }

    public function down(): void
    {
        Schema::table('wigs', function (Blueprint $table) {
            $table->dropColumn('bidang');
        });
    }
};
