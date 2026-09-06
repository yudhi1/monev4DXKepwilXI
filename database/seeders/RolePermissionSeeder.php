<?php

namespace Database\Seeders;

use App\Models\User;
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
        // Modul Master Data: hanya admin.
        $izinMaster = ['akses-master'];

        $izinPm = [
            'akses-pm',
            'pm.project.buat',
            'pm.lihat-semua',
            'pm.kelola',
        ];

        foreach ([...$izin4dx, ...$izinMaster, ...$izinPm] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([...$izin4dx, ...$izinMaster, ...$izinPm]);

        /*
         | Role di bawah ini murni milik modul Monev 4DX dan hanya dipakai akun
         | unit kerja. Tidak satu pun memuat permission PM: akses modul Project
         | Management ditentukan users.pm_role lewat User::selaraskanIzinPm(),
         | supaya kedua jenis akun benar-benar terpisah.
         */
        $wilayah = Role::firstOrCreate(['name' => 'kedeputian_wilayah', 'guard_name' => 'web']);
        $wilayah->syncPermissions([
            'akses-4dx', 'manage wig', 'manage lag', 'manage lead', 'view dashboard', 'export laporan',
        ]);

        $cabang = Role::firstOrCreate(['name' => 'kantor_cabang', 'guard_name' => 'web']);
        $cabang->syncPermissions(['akses-4dx', 'input realisasi', 'view dashboard']);

        // Sisa permission PM pada akun unit kerja dari versi sebelumnya dibersihkan.
        User::akunUnitKerja()->whereHas('roles', fn ($q) => $q->where('name', '!=', 'admin'))
            ->get()
            ->each(function (User $u) {
                foreach (['akses-pm', 'pm.project.buat', 'pm.lihat-semua', 'pm.kelola'] as $izin) {
                    if ($u->hasDirectPermission($izin)) {
                        $u->revokePermissionTo($izin);
                    }
                }
            });

        // Pegawai: izin PM diturunkan dari pm_role masing-masing.
        User::pegawai()->get()->each(fn (User $u) => $u->selaraskanIzinPm());
    }
}
