<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use Livewire\Component;

class WigRealisasiInput extends Component
{
    public ?int $wig_id = null;

    public ?int $cabang_id = null;

    public int $tahun;

    /** @var array<int, array{nilai: float|string, catatan: string}> */
    public array $rows = [];

    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }
        $this->wig_id = Wig::query()
            ->when($u && ! $u->hasRole('admin') && $u->wilayah_id, fn ($q) => $q->where('wilayah_id', $u->wilayah_id))
            ->orderByDesc('tahun')->orderBy('kode_wig')
            ->value('id');
        $this->loadRows();
    }

    public function updatedWigId(): void
    {
        $this->loadRows();
    }

    public function updatedCabangId(): void
    {
        $this->loadRows();
    }

    public function updatedTahun(): void
    {
        $this->loadRows();
    }

    public function loadRows(): void
    {
        $this->rows = [];
        for ($b = 1; $b <= 12; $b++) {
            $this->rows[$b] = ['nilai' => 0, 'catatan' => ''];
        }
        if (! $this->wig_id || ! $this->cabang_id) {
            return;
        }

        $existing = WigRealisasi::where('wig_id', $this->wig_id)
            ->where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->get()->keyBy('bulan');

        foreach ($existing as $bulan => $r) {
            $this->rows[$bulan] = [
                'nilai' => (float) $r->nilai,
                'catatan' => (string) ($r->catatan ?? ''),
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'wig_id' => 'required|exists:wigs,id',
            'cabang_id' => 'required|exists:cabangs,id',
            'tahun' => 'required|integer|min:2000|max:2100',
        ]);

        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id && $this->cabang_id !== $u->cabang_id) {
            $this->dispatch('notify', type: 'error', message: 'Tidak diizinkan input untuk cabang lain.');

            return;
        }

        foreach ($this->rows as $bulan => $r) {
            WigRealisasi::updateOrCreate(
                ['wig_id' => $this->wig_id, 'cabang_id' => $this->cabang_id, 'tahun' => $this->tahun, 'bulan' => $bulan],
                [
                    'nilai' => (float) ($r['nilai'] ?? 0),
                    'catatan' => $r['catatan'] ?? null,
                    'created_by' => $u?->id,
                ]
            );
        }
        $this->dispatch('notify', type: 'success', message: 'Realisasi WIG bulanan berhasil disimpan.');
        $this->loadRows();
    }

    public function render()
    {
        $u = auth()->user();

        $wigsQ = Wig::orderByDesc('tahun')->orderBy('kode_wig');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $wigsQ->where('wilayah_id', $u->wilayah_id);
        }

        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }

        $target = null;
        if ($this->wig_id && $this->cabang_id) {
            $target = WigTarget::where('wig_id', $this->wig_id)
                ->where('cabang_id', $this->cabang_id)->first();
        }

        $totalRealisasi = collect($this->rows)->sum(fn ($r) => (float) ($r['nilai'] ?? 0));
        $nilaiAwal = (float) ($target?->nilai_awal ?? 0);
        $nilaiTarget = (float) ($target?->nilai_target ?? 0);
        $range = $nilaiTarget - $nilaiAwal;
        $progres = $range > 0 ? round(($totalRealisasi / $range) * 100, 2) : 0;

        return view('livewire.wig-realisasi-input', [
            'wigs' => $wigsQ->get(),
            'cabangs' => $cabangsQ->get(),
            'bulanLabels' => self::BULAN,
            'target' => $target,
            'totalRealisasi' => $totalRealisasi,
            'progres' => $progres,
        ])->layout('layouts.app');
    }
}
