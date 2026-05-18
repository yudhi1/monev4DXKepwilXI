<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\Wig;
use Livewire\Component;
use Livewire\WithPagination;

class LagManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingId = null;
    public ?int $wig_id = null;
    public ?int $cabang_id = null;
    public string $kode_lag = '', $nama_lag = '';
    public ?string $tanggal_target = null;
    public int $tahun;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
    }

    protected function rules(): array
    {
        return [
            'wig_id' => 'required|exists:wigs,id',
            'cabang_id' => 'required|exists:cabangs,id',
            'kode_lag' => 'required|max:50|unique:lag_measures,kode_lag,'.$this->editingId,
            'nama_lag' => 'required|string|max:2000',
            'tanggal_target' => 'nullable|date',
            'tahun' => 'required|integer',
        ];
    }

    public function updatedCabangId(): void { $this->generateKode(); }
    public function updatedTahun(): void { $this->generateKode(); }

    private function generateKode(): void
    {
        if ($this->editingId) return;
        if (! $this->cabang_id) { $this->kode_lag = ''; return; }
        $cabang = Cabang::find($this->cabang_id);
        if (! $cabang) return;
        $base = preg_replace('/^KC-?/i', '', $cabang->kode);
        $count = LagMeasure::where('cabang_id', $this->cabang_id)->where('tahun', $this->tahun)->count() + 1;
        $this->kode_lag = $base.'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT).'-'.$this->tahun;
    }

    public function edit(int $id): void
    {
        $l = LagMeasure::findOrFail($id);
        $this->editingId = $l->id;
        $this->wig_id = $l->wig_id;
        $this->cabang_id = $l->cabang_id;
        $this->kode_lag = $l->kode_lag;
        $this->nama_lag = $l->nama_lag;
        $this->tanggal_target = optional($l->tanggal_target)->format('Y-m-d');
        $this->tahun = $l->tahun;
    }

    public function save(): void
    {
        $data = $this->validate();
        LagMeasure::updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId','wig_id','cabang_id','kode_lag','nama_lag','tanggal_target']);
        $this->tahun = (int) date('Y');
        session()->flash('success', 'Lag tersimpan.');
    }

    public function delete(int $id): void
    {
        LagMeasure::findOrFail($id)->delete();
        session()->flash('success', 'Lag dihapus.');
    }

    public function render()
    {
        $q = LagMeasure::with('wig','cabang');
        $u = auth()->user();
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $q->whereHas('wig', fn($w) => $w->where('wilayah_id', $u->wilayah_id));
        }

        $cabangsQ = Cabang::orderBy('nama');
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }

        return view('livewire.lag-management', [
            'lags' => $q->latest()->paginate(10),
            'wigs' => Wig::orderBy('kode_wig')->get(),
            'cabangs' => $cabangsQ->get(),
        ])->layout('layouts.app');
    }
}
