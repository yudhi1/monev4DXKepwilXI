<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             | Dua jenis akun hidup di tabel yang sama karena Laravel hanya
             | mengautentikasi satu tabel per guard, dan seluruh relasi
             | (pm_project_members, pm_task_assignees, activity_log) menunjuk
             | ke users. Kolom ini yang memisahkan keduanya di layar kelola.
             |
             | 'institusi' = akun unit kerja untuk Monev 4DX (kc.*, kepwil, admin)
             | 'pegawai'   = akun perorangan untuk Project Management
             */
            $table->string('tipe', 12)->default('institusi')->after('email');

            /*
             | Role modul PM di tingkat akun. Berbeda dari peran di dalam sebuah
             | project (pm_project_members.peran) yang tetap per project:
             | kolom ini menentukan hak lintas project.
             */
            $table->string('pm_role', 20)->nullable()->after('jabatan');

            $table->index('tipe');
        });

        // Pegawai dikenali dari unit kerjanya yang sudah terisi.
        DB::table('users')->whereNotNull('unit_kerja_id')->update([
            'tipe' => 'pegawai',
            'pm_role' => 'member',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tipe']);
            $table->dropColumn(['tipe', 'pm_role']);
        });
    }
};
