<?php

namespace App\Livewire;

use App\Models\Wilayah;
use Livewire\Component;
use Livewire\WithPagination;

class WilayahManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingId = null;

    public string $kode = '';

    public string $nama = '';

    public string $deskripsi = '';

    protected function rules(): array
    {
        return [
            'kode' => 'required|max:20|unique:wilayahs,kode,'.$this->editingId,
            'nama' => 'required|max:100',
            'deskripsi' => 'nullable|string',
        ];
    }

    public function edit(int $id): void
    {
        $w = Wilayah::findOrFail($id);
        $this->editingId = $w->id;
        $this->kode = $w->kode;
        $this->nama = $w->nama;
        $this->deskripsi = $w->deskripsi ?? '';
    }

    public function save(): void
    {
        $data = $this->validate();
        Wilayah::updateOrCreate(['id' => $this->editingId], $data);
        $this->reset();
        session()->flash('success', 'Wilayah tersimpan.');
    }

    public function delete(int $id): void
    {
        Wilayah::findOrFail($id)->delete();
        session()->flash('success', 'Wilayah dihapus.');
    }

    public function render()
    {
        return view('livewire.wilayah-management', [
            'wilayahs' => Wilayah::latest()->paginate(10),
        ])->layout('layouts.app');
    }
}
