<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\Pemda;
use Livewire\Component;

class PemdaManagement extends Component
{
    public const MAX_PEMDA_PER_CABANG = 3;

    public ?int $editingId = null;

    public ?int $cabang_id = null;

    public string $nama = '';

    public string $keterangan = '';

    public ?int $filter_cabang_id = null;

    public function mount(): void
    {
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
            $this->filter_cabang_id = $u->cabang_id;
        }
    }

    protected function rules(): array
    {
        return [
            'cabang_id' => 'required|exists:cabangs,id',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }

        $data = $this->validate();

        $existingCount = Pemda::where('cabang_id', $this->cabang_id)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->count();

        if ($existingCount >= self::MAX_PEMDA_PER_CABANG) {
            $this->dispatch('notify', type: 'error',
                message: 'Maksimal '.self::MAX_PEMDA_PER_CABANG.' Pemda per kantor cabang.');

            return;
        }

        $duplicate = Pemda::where('cabang_id', $this->cabang_id)
            ->where('nama', $this->nama)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($duplicate) {
            $this->dispatch('notify', type: 'error', message: 'Nama Pemda sudah ada untuk cabang ini.');

            return;
        }

        Pemda::updateOrCreate(['id' => $this->editingId], $data);
        $this->dispatch('notify', type: 'success', message: 'Pemda tersimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $p = Pemda::findOrFail($id);
        $this->editingId = $p->id;
        $this->cabang_id = $p->cabang_id;
        $this->nama = $p->nama;
        $this->keterangan = (string) $p->keterangan;
    }

    public function delete(int $id): void
    {
        Pemda::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Pemda dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->nama = '';
        $this->keterangan = '';
        $u = auth()->user();
        $this->cabang_id = ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) ? $u->cabang_id : null;
    }

    public function render()
    {
        $u = auth()->user();
        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangs = $cabangsQ->get();

        $pemdasQ = Pemda::with('cabang')->orderBy('cabang_id')->orderBy('nama');
        if ($this->filter_cabang_id) {
            $pemdasQ->where('cabang_id', $this->filter_cabang_id);
        } else {
            $pemdasQ->whereIn('cabang_id', $cabangs->pluck('id'));
        }

        return view('livewire.pemda-management', [
            'cabangs' => $cabangs,
            'pemdas' => $pemdasQ->get()->groupBy('cabang_id'),
            'maxPerCabang' => self::MAX_PEMDA_PER_CABANG,
        ])->layout('layouts.app');
    }
}
