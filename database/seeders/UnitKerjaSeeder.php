<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\UnitKerja;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

/**
 * Struktur bidang/unit kerja Kedeputian Wilayah XI.
 *
 * Aman dijalankan berulang: baris dikenali dari pasangan (kode, cabang_id).
 */
class UnitKerjaSeeder extends Seeder
{
    /** Bidang di kantor Kedeputian Wilayah. */
    private const BIDANG_WILAYAH = [
        'JPK' => 'Bidang JPK',
        'PIKEU' => 'Bidang PIKEU',
        'KML' => 'Bidang KML',
        'SDMUK' => 'Bidang SDMUK',
    ];

    /** Bidang yang ada di setiap kantor cabang. Perhatikan SDMU, bukan SDMUK. */
    private const BIDANG_CABANG = [
        'KEPESERTAAN' => 'Bidang Kepesertaan',
        'YANFASKES' => 'Bidang Yanfaskes',
        'YANSER' => 'Bidang Yanser',
        'PMU' => 'Bidang PMU',
        'PKP' => 'Bidang PKP',
        'SDMU' => 'Bidang SDMU',
    ];

    public function run(): void
    {
        $wilayah = Wilayah::first();

        if (! $wilayah) {
            $this->command?->warn('Belum ada data wilayah; UnitKerjaSeeder dilewati.');

            return;
        }

        $urutan = 0;
        foreach (self::BIDANG_WILAYAH as $kode => $nama) {
            UnitKerja::updateOrCreate(
                ['kode' => $kode, 'cabang_id' => null],
                [
                    'nama' => $nama,
                    'tingkat' => 'wilayah',
                    'wilayah_id' => $wilayah->id,
                    'urutan' => $urutan++,
                    'is_active' => true,
                ]
            );
        }

        /*
         | "Internal Kepwil" adalah kantor cabang semu yang dipakai modul 4DX
         | untuk input internal, bukan kantor cabang sungguhan — jadi tidak
         | diberi struktur bidang.
         */
        $cabangs = Cabang::where('kode', '!=', 'INTERN')->orderBy('id')->get();

        foreach ($cabangs as $cabang) {
            $urutan = 0;
            foreach (self::BIDANG_CABANG as $kode => $nama) {
                UnitKerja::updateOrCreate(
                    ['kode' => $kode, 'cabang_id' => $cabang->id],
                    [
                        'nama' => $nama,
                        'tingkat' => 'cabang',
                        'wilayah_id' => $cabang->wilayah_id,
                        'urutan' => $urutan++,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command?->info(
            'Unit kerja siap: '.count(self::BIDANG_WILAYAH).' bidang Kedeputian Wilayah + '
            .$cabangs->count().' cabang × '.count(self::BIDANG_CABANG).' bidang = '
            .UnitKerja::count().' unit.'
        );
    }
}
