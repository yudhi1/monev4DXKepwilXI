<?php

namespace Database\Seeders;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Contoh pegawai perorangan untuk pengembangan modul Project Management.
 *
 * Hanya mengisi beberapa bidang, bukan seluruh 70 unit — cukup untuk menguji
 * keanggotaan lintas bidang dan lintas level. Data pegawai sungguhan diunggah
 * admin lewat Master → User → Impor Pegawai.
 */
class PegawaiDemoSeeder extends Seeder
{
    private const PASSWORD_AWAL = 'monev2026';

    public function run(): void
    {
        // (nama, jabatan, kode bidang, kode cabang | null untuk Kedeputian Wilayah, role)
        $pegawai = [
            ['Rina Kusuma', 'Kepala Bidang', 'KML', null, 'project_manager'],
            ['Dedi Prasetyo', 'Staf', 'KML', null, 'member'],
            ['Maya Anggraini', 'Kepala Bidang', 'JPK', null, 'project_manager'],
            ['Bagus Nugroho', 'Staf', 'JPK', null, 'member'],
            ['Sari Wulandari', 'Kepala Bidang', 'PIKEU', null, 'project_manager'],
            ['Hendra Gunawan', 'Kepala Bidang', 'SDMUK', null, 'pimpinan'],

            ['Putu Ariana', 'Kepala Bidang', 'PMU', 'KC-DPS', 'project_manager'],
            ['Kadek Surya', 'Staf', 'PMU', 'KC-DPS', 'member'],
            ['Wayan Astuti', 'Kepala Bidang', 'KEPESERTAAN', 'KC-DPS', 'project_manager'],
            ['Nyoman Adi', 'Staf', 'YANFASKES', 'KC-DPS', 'member'],

            ['Lalu Ahmad', 'Kepala Bidang', 'PMU', 'KC-MTR', 'project_manager'],
            ['Baiq Nuraini', 'Staf', 'YANSER', 'KC-MTR', 'member'],

            ['Yohanes Bere', 'Kepala Bidang', 'PMU', 'KC-KPG', 'project_manager'],
            ['Maria Dhema', 'Staf', 'PKP', 'KC-KPG', 'member'],
        ];

        $unitKerjas = UnitKerja::with('cabang:id,kode')->get();
        $dibuat = 0;

        // NPP demo diurutkan dari 900001 — pegawai memakainya untuk login.
        $nomor = 900000;

        foreach ($pegawai as [$nama, $jabatan, $kodeBidang, $kodeCabang, $role]) {
            $nomor++;

            $unit = $unitKerjas->first(
                fn (UnitKerja $u) => $u->kode === $kodeBidang
                    && ($kodeCabang === null ? $u->cabang_id === null : $u->cabang?->kode === $kodeCabang)
            );

            if (! $unit) {
                $this->command?->warn("Unit {$kodeBidang} ".($kodeCabang ?? 'Kepwil').' tidak ditemukan; dilewati.');

                continue;
            }

            $user = User::updateOrCreate(
                ['name' => $nama],
                [
                    'npp' => (string) $nomor,
                    'email' => Str::slug($nama, '.').'@monev.local',
                    'password' => Hash::make(self::PASSWORD_AWAL),
                    'tipe' => 'pegawai',
                    'jabatan' => $jabatan,
                    'pm_role' => $role,
                    'unit_kerja_id' => $unit->id,
                    'wilayah_id' => $unit->wilayah_id,
                    'cabang_id' => $unit->cabang_id,
                    'is_active' => true,
                ]
            );

            /*
             | Pegawai sengaja tidak diberi role spatie: role itu milik modul
             | 4DX dan hanya dipakai akun unit kerja. Akses PM diturunkan dari
             | pm_role.
             */
            $user->syncRoles([]);
            $user->selaraskanIzinPm();
            $dibuat++;
        }

        $this->command?->info("Pegawai demo siap: {$dibuat} orang (password: ".self::PASSWORD_AWAL.').');
    }
}
