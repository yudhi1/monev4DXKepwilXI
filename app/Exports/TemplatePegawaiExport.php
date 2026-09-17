<?php

namespace App\Exports;

use App\Models\Cabang;
use App\Models\UnitKerja;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template unggah pegawai — berisi header, beberapa baris contoh, dan
 * daftar kode bidang/cabang yang sah agar admin tidak menebak.
 */
class TemplatePegawaiExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Pegawai';
    }

    public function headings(): array
    {
        return ['npp', 'nama', 'jabatan', 'bidang', 'cabang', 'role'];
    }

    public function array(): array
    {
        $contohCabang = UnitKerja::where('tingkat', 'cabang')->with('cabang')->first();

        $baris = [
            ['10001', 'Budi Santoso', 'Staf', 'KML', '', 'member'],
            ['10002', 'Siti Rahayu', 'Kepala Bidang', 'JPK', '', 'project_manager'],
            ['10003', 'Andi Wijaya', 'Staf', $contohCabang?->kode ?? 'PMU', $contohCabang?->cabang?->kode ?? 'KC-DPS', ''],
            [],
            ['— Kolom "npp" wajib: NPP dipakai pegawai untuk masuk ke Project Management —'],
            ['— Kosongkan kolom "cabang" untuk pegawai di kantor Kedeputian Wilayah —'],
            ['— Kolom "role" boleh kosong; bawaannya member —'],
            [],
            ['PILIHAN ROLE'],
            ['kode', 'arti'],
        ];

        foreach (config('pm.role_akun') as $kode => $meta) {
            $baris[] = [$kode, $meta['keterangan']];
        }

        $baris[] = [];
        $baris[] = ['DAFTAR KODE BIDANG'];
        $baris[] = ['tingkat', 'kode', 'nama', 'berlaku di'];

        foreach (UnitKerja::aktif()->where('tingkat', 'wilayah')->orderBy('urutan')->get() as $u) {
            $baris[] = ['wilayah', $u->kode, $u->nama, 'Kedeputian Wilayah (kolom cabang dikosongkan)'];
        }

        $bidangCabang = UnitKerja::aktif()->where('tingkat', 'cabang')
            ->get()
            ->unique('kode')
            ->sortBy('urutan');

        foreach ($bidangCabang as $u) {
            $baris[] = ['cabang', $u->kode, $u->nama, 'semua kantor cabang'];
        }

        $baris[] = [];
        $baris[] = ['DAFTAR KODE CABANG'];

        foreach (Cabang::where('kode', '!=', 'INTERN')->orderBy('nama')->get() as $c) {
            $baris[] = ['', $c->kode, $c->nama];
        }

        return $baris;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
