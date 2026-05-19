<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\Wig;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LeadManagement extends Component
{
    public ?int $wig_id = null;

    public ?int $cabang_id = null;

    public int $tahun;

    // form
    public ?int $editingId = null;

    public string $kode_lead = '';

    public string $nama_lead = '';

    public ?int $lag_measure_id = null;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }
    }

    public function updatedWigId(): void
    {
        $this->resetForm();
        $this->generateKode();
    }

    public function updatedCabangId(): void
    {
        $this->resetForm();
        $this->generateKode();
    }

    private function generateKode(): void
    {
        if ($this->editingId) {
            return;
        }
        if (! $this->cabang_id || ! $this->wig_id) {
            $this->kode_lead = '';

            return;
        }
        $cabang = Cabang::find($this->cabang_id);
        $wig = Wig::find($this->wig_id);
        if (! $cabang || ! $wig) {
            return;
        }
        $bidang = strtoupper(trim((string) $wig->bidang)) ?: 'UMUM';
        $base = preg_replace('/^KC-?/i', '', $cabang->kode);
        $prefix = 'LEAD-'.$bidang.'-'.$base.'-';
        $nextNum = (int) LeadMeasure::where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('kode_lead', 'like', $prefix.'%')
            ->count() + 1;
        do {
            $kode = $prefix.str_pad((string) $nextNum, 2, '0', STR_PAD_LEFT).'-'.$this->tahun;
            $nextNum++;
        } while (LeadMeasure::where('kode_lead', $kode)->exists());
        $this->kode_lead = $kode;
    }

    protected function rules(): array
    {
        return [
            'wig_id' => 'required|exists:wigs,id',
            'cabang_id' => 'required|exists:cabangs,id',
            'lag_measure_id' => 'required|exists:lag_measures,id',
            'kode_lead' => 'required|max:50|unique:lead_measures,kode_lead,'.$this->editingId,
            'nama_lead' => 'required|string|max:2000',
        ];
    }

    public function edit(int $id): void
    {
        $l = LeadMeasure::findOrFail($id);
        $this->editingId = $l->id;
        $this->lag_measure_id = $l->lag_measure_id;
        $this->kode_lead = $l->kode_lead;
        $this->nama_lead = $l->nama_lead;
    }

    public function save(): void
    {
        try {
            $data = $this->validate();
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->implode(' ');
            $this->dispatch('notify', type: 'error', message: 'Validasi gagal: '.$msg);
            throw $e;
        }
        $data['tahun'] = $this->tahun;
        try {
            if ($this->editingId) {
                LeadMeasure::findOrFail($this->editingId)->update($data);
            } else {
                LeadMeasure::create($data);
            }
            $this->resetForm();
            $this->generateKode();
            $this->dispatch('notify', type: 'success', message: 'Lead Measure berhasil disimpan.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        LeadMeasure::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Lead Measure berhasil dihapus.');
    }

    public function toggleActive(int $id): void
    {
        $l = LeadMeasure::findOrFail($id);
        $l->is_active = ! $l->is_active;
        $l->save();
        $this->dispatch('notify', type: 'success', message: $l->is_active ? 'Lead diaktifkan.' : 'Lead dinonaktifkan.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode_lead', 'nama_lead', 'lag_measure_id']);
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
        $lags = collect();
        if ($this->wig_id && $this->cabang_id) {
            $leads = LeadMeasure::with('lagMeasure')
                ->where('wig_id', $this->wig_id)
                ->where('cabang_id', $this->cabang_id)
                ->orderBy('kode_lead')->get();

            $lags = LagMeasure::where('wig_id', $this->wig_id)
                ->where(function ($q) {
                    $q->whereNull('cabang_id')->orWhere('cabang_id', $this->cabang_id);
                })->orderBy('kode_lag')->get();
        }

        return view('livewire.lead-management', [
            'wigs' => $wigsQ->get(),
            'cabangs' => $cabangsQ->get(),
            'leads' => $leads,
            'lags' => $lags,
        ])->layout('layouts.app');
    }
}
