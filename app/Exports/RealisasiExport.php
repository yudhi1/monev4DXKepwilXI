<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RealisasiExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(public Collection $data) {}

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['WIG','Lag Measure','Lead Measure','Cabang','Tahun','Bulan','Minggu','Target','Realisasi','% Capaian','Catatan'];
    }

    public function map($r): array
    {
        return [
            $r->leadMeasure?->lagMeasure?->wig?->nama_wig,
            $r->leadMeasure?->lagMeasure?->nama_lag,
            $r->leadMeasure?->nama_lead,
            $r->cabang?->nama,
            $r->tahun, $r->bulan, $r->minggu_ke,
            $r->target, $r->realisasi, $r->persentase, $r->catatan,
        ];
    }
}
