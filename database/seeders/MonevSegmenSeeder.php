<?php

namespace Database\Seeders;

use App\Models\MonevSegmen;
use Illuminate\Database\Seeder;

class MonevSegmenSeeder extends Seeder
{
    public function run(): void
    {
        $segmens = [
            'Pemerintah Daerah',
            'PNS Pusat',
            'TNI',
            'POLRI',
            'PNS Daerah',
            'PPNPN Pusat',
            'PPNPN Daerah',
            'KP Desa',
            'PPU Badan Usaha',
            'PBPU dan BP',
            'Kontribusi Iuran Pemerintah Daerah',
            'PBPU Didaftarkan Pemda',
            'Bantuan Iuran Pemda atas PBPU',
            'Bantuan Iuran Pemda atas PD-Pemda',
        ];

        foreach ($segmens as $i => $nama) {
            MonevSegmen::firstOrCreate(
                ['nama' => $nama],
                ['urutan' => $i + 1, 'is_active' => true]
            );
        }
    }
}
