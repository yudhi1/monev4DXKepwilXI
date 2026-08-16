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
        // (nama, jabatan, kode bidang, kode cabang | null untuk Kedeputian Wilayah)
        $pegawai = [
            ['Rina Kusuma', 'Kepala Bidang', 'KML', null],
            ['Dedi Prasetyo', 'Staf', 'KML', null],
            ['Maya Anggraini', 'Kepala Bidang', 'JPK', null],
            ['Bagus Nugroho', 'Staf', 'JPK', null],
            ['Sari Wulandari', 'Kepala Bidang', 'PIKUE', null],
            ['Hendra Gunawan', 'Kepala Bidang', 'SDMUK', null],

            ['Putu Ariana', 'Kepala Bidang', 'PMU', 'KC-DPS'],
            ['Kadek Surya', 'Staf', 'PMU', 'KC-DPS'],
            ['Wayan Astuti', 'Kepala Bidang', 'KEPESERTAAN', 'KC-DPS'],
            ['Nyoman Adi', 'Staf', 'YANFASKES', 'KC-DPS'],

            ['Lalu Ahmad', 'Kepala Bidang', 'PMU', 'KC-MTR'],
            ['Baiq Nuraini', 'Staf', 'YANSER', 'KC-MTR'],

            ['Yohanes Bere', 'Kepala Bidang', 'PMU', 'KC-KPG'],
            ['Maria Dhema', 'Staf', 'PKP', 'KC-KPG'],
        ];

        $unitKerjas = UnitKerja::with('cabang:id,kode')->get();
        $dibuat = 0;

        foreach ($pegawai as [$nama, $jabatan, $kodeBidang, $kodeCabang]) {
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
                    'email' => Str::slug($nama, '.').'@monev.local',
                    'password' => Hash::make(self::PASSWORD_AWAL),
                    'jabatan' => $jabatan,
                    'unit_kerja_id' => $unit->id,
                    'wilayah_id' => $unit->wilayah_id,
                    'cabang_id' => $unit->cabang_id,
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$unit->tingkat === 'wilayah' ? 'kedeputian_wilayah' : 'kantor_cabang']);
            $dibuat++;
        }

        $this->command?->info("Pegawai demo siap: {$dibuat} orang (password: ".self::PASSWORD_AWAL.').');
    }
}
