<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\Wig;
use Livewire\Component;

class RealisasiInput extends Component
{
    public ?int $wig_id = null;

    public ?int $cabang_id = null;

    public int $tahun;

    public int $bulan;

    public int $minggu = 1;

    /** rows[lead_id][minggu_ke] = ['target' => x, 'realisasi' => y] */
    public array $rows = [];

    /** editing[lead_id] = true untuk lead yang sedang diedit */
    public array $editing = [];

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->bulan = (int) date('n');
        $u = auth()->user();
        if ($u && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }
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

    public function updatedBulan(): void
    {
        $this->loadRows();
    }

    public function loadRows(): void
    {
        $this->rows = [];
        if (! $this->wig_id || ! $this->cabang_id) {
            return;
        }

        $leads = LeadMeasure::where('wig_id', $this->wig_id)
            ->where('cabang_id', $this->cabang_id)
            ->where('is_active', true)
            ->get();

        $existing = LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
            ->where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->get()
            ->groupBy('lead_measure_id');

        foreach ($leads as $lead) {
            $perLead = $existing->get($lead->id) ?? collect();
            $byMinggu = $perLead->keyBy('minggu_ke');
            for ($m = 1; $m <= 4; $m++) {
                $r = $byMinggu->get($m);
                $this->rows[$lead->id][$m] = [
                    'target' => $r?->target ?? 0,
                    'realisasi' => $r?->realisasi ?? 0,
                    'keterangan' => $r?->catatan ?? '',
                ];
            }
        }
    }

    public function toggleEdit(int $lead_id): void
    {
        $this->editing[$lead_id] = ! ($this->editing[$lead_id] ?? false);
    }

    public function saveLead(int $lead_id): void
    {
        if (! isset($this->rows[$lead_id])) {
            return;
        }

        foreach ($this->rows[$lead_id] as $minggu => $vals) {
            LeadMeasureRealisasi::updateOrCreate(
                [
                    'lead_measure_id' => $lead_id,
                    'cabang_id' => $this->cabang_id,
                    'tahun' => $this->tahun,
                    'bulan' => $this->bulan,
                    'minggu_ke' => $minggu,
                ],
                [
                    'target' => (float) ($vals['target'] ?? 0),
                    'realisasi' => (float) ($vals['realisasi'] ?? 0),
                    'catatan' => $vals['keterangan'] ?? null,
                    'created_by' => auth()->id(),
                ]
            );
        }
        unset($this->editing[$lead_id]);
        $this->loadRows();
        $this->dispatch('lead-saved', leadId: $lead_id);
        session()->flash('success', 'Lead tersimpan.');
    }

    public function cancelEdit(int $lead_id): void
    {
        unset($this->editing[$lead_id]);
        $this->loadRows();
    }

    public function render()
    {
        $u = auth()->user();

        $wigsQ = Wig::orderBy('kode_wig');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $wigsQ->where('wilayah_id', $u->wilayah_id);
        }

        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }

        $leads = collect();
        if ($this->wig_id && $this->cabang_id) {
            $leads = LeadMeasure::with('lagMeasure')
                ->where('wig_id', $this->wig_id)
                ->where('cabang_id', $this->cabang_id)
                ->where('is_active', true)
                ->orderBy('kode_lead')->get();
        }

        return view('livewire.realisasi-input', [
            'wigs' => $wigsQ->get(),
            'cabangs' => $cabangsQ->get(),
            'leads' => $leads,
        ])->layout('layouts.app');
    }
}
