<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->foreignId('cabang_id')->nullable()->after('wig_id')->constrained('cabangs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lag_measures', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
