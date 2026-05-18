<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage users', 'manage wilayah', 'manage cabang',
            'manage wig', 'manage lag', 'manage lead',
            'input realisasi', 'view dashboard', 'export laporan',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $wilayah = Role::firstOrCreate(['name' => 'kedeputian_wilayah', 'guard_name' => 'web']);
        $wilayah->syncPermissions(['manage wig', 'manage lag', 'manage lead', 'view dashboard', 'export laporan']);

        $cabang = Role::firstOrCreate(['name' => 'kantor_cabang', 'guard_name' => 'web']);
        $cabang->syncPermissions(['input realisasi', 'view dashboard']);
    }
}
