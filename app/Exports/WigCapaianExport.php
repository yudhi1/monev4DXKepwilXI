<?php

namespace App\Exports;

use App\Support\Wig\Capaian;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Target & realisasi WIG, menyalin susunan tabel di layar: tiap unit kerja
 * memakai tiga baris (Target, Realisasi, % Capaian) melintasi dua belas bulan.
 *
 * Angka bulanan diekspor apa adanya — bukan akumulasi — karena itulah yang
 * tersimpan; pembaca bisa menjumlahkannya sendiri di Excel bila perlu.
 *
 * WithStrictNullComparison wajib: tanpa itu Sheet::append membandingkan nilai
 * sel dengan null secara longgar, sehingga setiap angka 0 — termasuk 0% —
 * ikut dianggap kosong dan selnya dibiarkan blank.
 */
class WigCapaianExport implements FromArray, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $baris  Keluaran WigCapaianController::baris()
     * @param  array<int, string>  $namaBulan
     */
    public function __construct(
        private array $baris,
        private array $namaBulan,
        private int $tahun,
        private int $bulanAcuan,
        private string $kodeWig,
        private string $namaWig,
        private string $sifat = 'akumulatif',
        private string $arah = 'naik',
    ) {}

    public function title(): string
    {
        return "Capaian {$this->tahun}";
    }

    public function headings(): array
    {
        return [
            ["WIG: {$this->kodeWig} — {$this->namaWig}"],
            ['Bulan berjalan: '.($this->namaBulan[$this->bulanAcuan - 1] ?? $this->bulanAcuan)." {$this->tahun}"],
            ['Sifat capaian: '.(config("wig.sifat.{$this->sifat}.label") ?? $this->sifat)
                .' · '.(config("wig.arah.{$this->arah}.label") ?? $this->arah)],
            [],
            array_merge(
                ['Unit Kerja', 'Baris'],
                $this->namaBulan,
                $this->sifat === 'posisi' ? ['Nilai Awal'] : [],
                [
                    $this->sifat === 'periodik' ? 'Target Bulanan' : "Target {$this->tahun}",
                    $this->sifat === 'periodik' ? '% Rata-rata s.d. Bulan' : '% thd Target',
                    'Satuan',
                    'Tanggal Target',
                ],
            ),
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->baris as $b) {
            $target = array_map(fn ($m) => (float) $m['target'], $b['bulan']);
            $realisasi = array_map(fn ($m) => (float) $m['realisasi'], $b['bulan']);

            $kosong = array_fill(0, $this->sifat === 'posisi' ? 5 : 4, '');

            $rows[] = array_merge(
                [$b['cabang_nama'], 'Target'],
                $target,
                $this->sifat === 'posisi' ? [(float) $b['nilai_awal']] : [],
                [
                    (float) $b['nilai_target'],
                    Capaian::persenTahunan($b, $this->bulanAcuan, $this->sifat),
                    $b['satuan'],
                    $b['tanggal_target'],
                ],
            );

            $rows[] = array_merge(['', 'Realisasi'], $realisasi, $kosong);

            $rows[] = array_merge(
                ['', '% Capaian'],
                array_map(
                    fn ($i) => Capaian::persen($realisasi[$i], $target[$i]),
                    range(0, 11),
                ),
                $kosong,
            );
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            5 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }
}
