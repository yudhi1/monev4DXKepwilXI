<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /* Permission modul 4DX (yang sudah ada) + penanda akses modulnya. */
        $izin4dx = [
            'akses-4dx',
            'manage users', 'manage wilayah', 'manage cabang',
            'manage wig', 'manage lag', 'manage lead',
            'input realisasi', 'view dashboard', 'export laporan',
        ];

        /*
         | Permission modul Project Management.
         |
         | Peran di dalam sebuah project (manager/member/viewer) TIDAK ada di
         | sini — itu disimpan per-project di pm_project_members.peran, karena
         | satu orang bisa jadi manager di satu project dan member di project
         | lain. Yang di sini hanya hak yang berlaku lintas project.
         */
        $izinPm = [
            'akses-pm',
            'pm.project.buat',
            'pm.lihat-semua',
            'pm.kelola',
        ];

        foreach ([...$izin4dx, ...$izinPm] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([...$izin4dx, ...$izinPm]);

        $wilayah = Role::firstOrCreate(['name' => 'kedeputian_wilayah', 'guard_name' => 'web']);
        $wilayah->syncPermissions([
            'akses-4dx', 'manage wig', 'manage lag', 'manage lead', 'view dashboard', 'export laporan',
            'akses-pm', 'pm.project.buat', 'pm.lihat-semua',
        ]);

        $cabang = Role::firstOrCreate(['name' => 'kantor_cabang', 'guard_name' => 'web']);
        $cabang->syncPermissions([
            'akses-4dx', 'input realisasi', 'view dashboard',
            // Hanya melihat project yang dia ikuti — tanpa pm.lihat-semua.
            'akses-pm',
        ]);
    }
}
