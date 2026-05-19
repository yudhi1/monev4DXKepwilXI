<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\Wilayah;
use Livewire\Component;
use Livewire\WithPagination;

class CabangManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingId = null;

    public ?int $wilayah_id = null;

    public string $kode = '';

    public string $nama = '';

    protected function rules(): array
    {
        return [
            'wilayah_id' => 'required|exists:wilayahs,id',
            'kode' => 'required|max:20|unique:cabangs,kode,'.$this->editingId,
            'nama' => 'required|max:100',
        ];
    }

    public function edit(int $id): void
    {
        $c = Cabang::findOrFail($id);
        $this->editingId = $c->id;
        $this->wilayah_id = $c->wilayah_id;
        $this->kode = $c->kode;
        $this->nama = $c->nama;
    }

    public function save(): void
    {
        $data = $this->validate();
        Cabang::updateOrCreate(['id' => $this->editingId], $data);
        $this->reset();
        session()->flash('success', 'Cabang tersimpan.');
    }

    public function delete(int $id): void
    {
        Cabang::findOrFail($id)->delete();
        session()->flash('success', 'Cabang dihapus.');
    }

    public function render()
    {
        return view('livewire.cabang-management', [
            'cabangs' => Cabang::with('wilayah')->latest()->paginate(10),
            'wilayahs' => Wilayah::orderBy('nama')->get(),
        ])->layout('layouts.app');
    }
}
