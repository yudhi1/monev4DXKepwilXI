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
            /*
             | Sengaja TANPA pm.lihat-semua. Role ini kini juga dipakai staf
             | bidang di Kedeputian Wilayah, dan staf tidak boleh melihat
             | seluruh project. Hak "pimpinan" diberikan per user sebagai
             | permission langsung lewat form Kelola User.
             */
            'akses-pm', 'pm.project.buat',
        ]);

        $cabang = Role::firstOrCreate(['name' => 'kantor_cabang', 'guard_name' => 'web']);
        $cabang->syncPermissions([
            'akses-4dx', 'input realisasi', 'view dashboard',
            /*
             | Setiap pegawai boleh membuat project atas nama bidangnya sendiri.
             | Tanpa pm.lihat-semua: dia hanya melihat project yang dia ikuti
             | atau yang dimiliki bidangnya.
             */
            'akses-pm', 'pm.project.buat',
        ]);

        /*
         | Akun institusi `kepwil` dipakai untuk memantau, jadi tetap diberi
         | hak melihat seluruh project — sebagai permission langsung, bukan
         | lewat role, supaya staf bidang tidak ikut kebagian.
         */
        User::where('name', 'kepwil')->first()?->givePermissionTo('pm.lihat-semua');
    }
}
