<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IuranExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(public Collection $data, public array $bulanLabels = []) {}

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['No', 'Bulan', 'Minggu', 'Kantor Cabang', 'Nama Pemda', 'Tagihan',
            'Bayar', 'Outstanding', 'Target Penyelesaian', 'PIC', 'Kendala', 'Keterangan'];
    }

    public function map($r): array
    {
        static $i = 0;
        $i++;

        return [
            $i,
            $this->bulanLabels[$r->bulan] ?? $r->bulan,
            'Minggu '.$r->minggu,
            $r->cabang?->nama,
            $r->nama_pemda,
            (float) $r->tagihan,
            strtoupper($r->status_bayar),
            (float) $r->outstanding,
            $r->target_penyelesaian?->format('d-m-Y'),
            $r->pic,
            $r->kendala,
            $r->keterangan,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
        ];
    }
}
