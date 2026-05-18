<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('wilayah_id')->nullable()->after('email')->constrained('wilayahs')->nullOnDelete();
            $table->foreignId('cabang_id')->nullable()->after('wilayah_id')->constrained('cabangs')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('cabang_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['wilayah_id']);
            $table->dropForeign(['cabang_id']);
            $table->dropColumn(['wilayah_id', 'cabang_id', 'is_active']);
        });
    }
};
