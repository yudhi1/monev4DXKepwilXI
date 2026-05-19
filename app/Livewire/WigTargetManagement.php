<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\Wig;
use App\Models\WigTarget;
use Livewire\Component;

class WigTargetManagement extends Component
{
    public ?int $wig_id = null;

    public array $rows = [];

    public function mount(): void
    {
        $this->wig_id = Wig::orderByDesc('tahun')->orderBy('kode_wig')->value('id');
        $this->loadRows();
    }

    public function updatedWigId(): void
    {
        $this->loadRows();
    }

    public function loadRows(): void
    {
        $this->rows = [];
        if (! $this->wig_id) {
            return;
        }

        $u = auth()->user();
        $cabangsQ = Cabang::orderBy('nama');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangs = $cabangsQ->get();

        $targets = WigTarget::where('wig_id', $this->wig_id)->get()->keyBy('cabang_id');

        foreach ($cabangs as $c) {
            $t = $targets->get($c->id);
            $this->rows[$c->id] = [
                'cabang_nama' => $c->nama,
                'nilai_awal' => $t?->nilai_awal ?? 0,
                'nilai_target' => $t?->nilai_target ?? 0,
                'satuan' => $t?->satuan ?? 'Rp',
                'tanggal_target' => optional($t?->tanggal_target)->format('Y-m-d') ?? '',
            ];
        }
    }

    public function save(): void
    {
        $this->validate(['wig_id' => 'required|exists:wigs,id']);

        foreach ($this->rows as $cabang_id => $r) {
            WigTarget::updateOrCreate(
                ['wig_id' => $this->wig_id, 'cabang_id' => $cabang_id],
                [
                    'nilai_awal' => (float) ($r['nilai_awal'] ?? 0),
                    'nilai_target' => (float) ($r['nilai_target'] ?? 0),
                    'satuan' => $r['satuan'] ?? 'Rp',
                    'tanggal_target' => ! empty($r['tanggal_target']) ? $r['tanggal_target'] : null,
                ]
            );
        }
        session()->flash('success', 'Target per cabang tersimpan.');
        $this->loadRows();
    }

    public function render()
    {
        $u = auth()->user();
        $wigsQ = Wig::orderByDesc('tahun')->orderBy('kode_wig');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $wigsQ->where('wilayah_id', $u->wilayah_id);
        }

        return view('livewire.wig-target-management', [
            'wigs' => $wigsQ->get(),
            'wig' => $this->wig_id ? Wig::find($this->wig_id) : null,
        ])->layout('layouts.app');
    }
}
