<?php

namespace App\Livewire\MonevIuran;

use App\Models\MonevSegmen;
use Livewire\Component;

class SegmenManagement extends Component
{
    public ?int $editingId = null;

    public string $nama = '';

    public int $urutan = 0;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'urutan' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            MonevSegmen::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Segmen diperbarui.');
        } else {
            MonevSegmen::create($data);
            $this->dispatch('notify', type: 'success', message: 'Segmen ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $s = MonevSegmen::findOrFail($id);
        $this->editingId = $s->id;
        $this->nama = $s->nama;
        $this->urutan = $s->urutan;
        $this->is_active = $s->is_active;
    }

    public function delete(int $id): void
    {
        $s = MonevSegmen::findOrFail($id);
        if ($s->realisasis()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Segmen tidak bisa dihapus karena sudah memiliki data realisasi.');

            return;
        }
        $s->delete();
        $this->dispatch('notify', type: 'success', message: 'Segmen dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->nama = '';
        $this->urutan = (int) (MonevSegmen::max('urutan') ?? 0) + 1;
        $this->is_active = true;
    }

    public function mount(): void
    {
        $this->urutan = (int) (MonevSegmen::max('urutan') ?? 0) + 1;
    }

    public function render()
    {
        return view('livewire.monev-iuran.segmen-management', [
            'segmens' => MonevSegmen::orderBy('urutan')->orderBy('id')->get(),
        ])->layout('layouts.app');
    }
}
