<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KantorCabangSeeder extends Seeder
{
    public function run(): void
    {
        $wilayah = Wilayah::firstOrCreate(
            ['kode' => 'W-XI'],
            ['nama' => 'Kedeputian Wilayah XI', 'deskripsi' => 'Wilayah Bali, NTB, NTT']
        );

        $kantor = [
            ['kode' => 'KC-DPS', 'nama' => 'KC Denpasar',  'alamat' => 'Denpasar, Bali'],
            ['kode' => 'KC-KLK', 'nama' => 'KC Klungkung', 'alamat' => 'Klungkung, Bali'],
            ['kode' => 'KC-SGR', 'nama' => 'KC Singaraja', 'alamat' => 'Singaraja, Bali'],
            ['kode' => 'KC-MTR', 'nama' => 'KC Mataram',   'alamat' => 'Mataram, NTB'],
            ['kode' => 'KC-SLG', 'nama' => 'KC Selong',    'alamat' => 'Selong, NTB'],
            ['kode' => 'KC-BIM', 'nama' => 'KC Bima',      'alamat' => 'Bima, NTB'],
            ['kode' => 'KC-KPG', 'nama' => 'KC Kupang',    'alamat' => 'Kupang, NTT'],
            ['kode' => 'KC-MOF', 'nama' => 'KC Maumere',   'alamat' => 'Maumere, NTT'],
            ['kode' => 'KC-ENE', 'nama' => 'KC Ende',      'alamat' => 'Ende, NTT'],
            ['kode' => 'KC-WGP', 'nama' => 'KC Waingapu',  'alamat' => 'Waingapu, NTT'],
            ['kode' => 'KC-ATB', 'nama' => 'KC Atambua',   'alamat' => 'Atambua, NTT'],
        ];

        $cabangs = collect();
        foreach ($kantor as $k) {
            $c = Cabang::firstOrCreate(
                ['kode' => $k['kode']],
                ['wilayah_id' => $wilayah->id, 'nama' => $k['nama'], 'alamat' => $k['alamat']]
            );
            $cabangs->push($c);

            $emailSlug = Str::lower(Str::after($k['kode'], 'KC-'));
            $u = User::firstOrCreate(
                ['email' => $emailSlug . '@monev.test'],
                [
                    'name' => 'User ' . $k['nama'],
                    'password' => Hash::make('password'),
                    'wilayah_id' => $wilayah->id,
                    'cabang_id' => $c->id,
                    'is_active' => true,
                ]
            );
            $u->syncRoles(['kantor_cabang']);
        }

        // ===== Dummy WIG / Lag / Lead =====
        $tahun = (int) date('Y');
        $creator = User::role('kedeputian_wilayah')->first();

        $wigDefs = [
            [
                'kode' => 'WIG-' . $tahun . '-01',
                'nama' => 'Peningkatan Pertumbuhan Tabungan',
                'output' => 'Pertumbuhan saldo tabungan minimal 15% YoY',
                'lags' => [
                    [
                        'kode' => 'LAG-' . $tahun . '-01', 'nama' => 'Total saldo tabungan tercapai',
                        'target' => 5000000000, 'satuan' => 'Rp',
                        'leads' => [
                            ['kode' => 'LEAD-' . $tahun . '-01', 'nama' => 'Kunjungan nasabah baru', 'satuan' => 'kunjungan'],
                            ['kode' => 'LEAD-' . $tahun . '-02', 'nama' => 'Pembukaan rekening baru', 'satuan' => 'rekening'],
                        ],
                    ],
                ],
            ],
            [
                'kode' => 'WIG-' . $tahun . '-02',
                'nama' => 'Peningkatan Penyaluran Kredit',
                'output' => 'Pertumbuhan portofolio kredit minimal 12%',
                'lags' => [
                    [
                        'kode' => 'LAG-' . $tahun . '-02', 'nama' => 'Realisasi penyaluran kredit',
                        'target' => 10000000000, 'satuan' => 'Rp',
                        'leads' => [
                            ['kode' => 'LEAD-' . $tahun . '-03', 'nama' => 'Proposal kredit masuk', 'satuan' => 'proposal'],
                            ['kode' => 'LEAD-' . $tahun . '-04', 'nama' => 'Survey lapangan', 'satuan' => 'survey'],
                        ],
                    ],
                ],
            ],
            [
                'kode' => 'WIG-' . $tahun . '-03',
                'nama' => 'Peningkatan Kualitas Layanan',
                'output' => 'CSI (Customer Satisfaction Index) minimal 90',
                'lags' => [
                    [
                        'kode' => 'LAG-' . $tahun . '-03', 'nama' => 'Skor kepuasan nasabah',
                        'target' => 90, 'satuan' => 'skor',
                        'leads' => [
                            ['kode' => 'LEAD-' . $tahun . '-05', 'nama' => 'Pelatihan service excellence', 'satuan' => 'sesi'],
                            ['kode' => 'LEAD-' . $tahun . '-06', 'nama' => 'Penyelesaian komplain < 24 jam', 'satuan' => 'komplain'],
                        ],
                    ],
                ],
            ],
        ];

        $leadMeasures = collect();
        foreach ($wigDefs as $wd) {
            $wig = Wig::firstOrCreate(
                ['kode_wig' => $wd['kode']],
                [
                    'nama_wig' => $wd['nama'],
                    'indikator_output' => $wd['output'],
                    'tahun' => $tahun,
                    'wilayah_id' => $wilayah->id,
                    'created_by' => $creator?->id,
                ]
            );

            foreach ($wd['lags'] as $ld) {
                $lag = LagMeasure::firstOrCreate(
                    ['kode_lag' => $ld['kode']],
                    [
                        'wig_id' => $wig->id,
                        'nama_lag' => $ld['nama'],
                        'target_tahunan' => $ld['target'],
                        'satuan' => $ld['satuan'],
                        'tahun' => $tahun,
                    ]
                );

                foreach ($ld['leads'] as $lead) {
                    $lm = LeadMeasure::firstOrCreate(
                        ['kode_lead' => $lead['kode']],
                        [
                            'lag_measure_id' => $lag->id,
                            'wig_id' => $wig->id,
                            'nama_lead' => $lead['nama'],
                            'satuan' => $lead['satuan'],
                            'tahun' => $tahun,
                        ]
                    );
                    $leadMeasures->push($lm);
                }
            }
        }

        // ===== Dummy Realisasi Mingguan =====
        $bulanSekarang = (int) date('n');
        foreach ($leadMeasures as $lm) {
            foreach ($cabangs as $c) {
                for ($b = 1; $b <= $bulanSekarang; $b++) {
                    for ($mg = 1; $mg <= 4; $mg++) {
                        $target = random_int(50, 200);
                        $realisasi = (int) round($target * (random_int(60, 130) / 100));
                        LeadMeasureRealisasi::updateOrCreate(
                            [
                                'lead_measure_id' => $lm->id,
                                'cabang_id' => $c->id,
                                'tahun' => $tahun,
                                'bulan' => $b,
                                'minggu_ke' => $mg,
                            ],
                            [
                                'target' => $target,
                                'realisasi' => $realisasi,
                                'catatan' => null,
                                'created_by' => $creator?->id,
                            ]
                        );
                    }
                }
            }
        }
    }
}
