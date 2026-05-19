<?php

namespace App\Livewire;

use App\Models\Wig;
use App\Models\Wilayah;
use Livewire\Component;
use Livewire\WithPagination;

class WigManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $editingId = null;

    public string $kode_wig = '';

    public string $nama_wig = '';

    public string $indikator_output = '';

    public string $bidang = '';

    public int $tahun;

    public ?int $wilayah_id = null;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $u = auth()->user();
        if ($u && $u->wilayah_id) {
            $this->wilayah_id = $u->wilayah_id;
        }
    }

    protected function rules(): array
    {
        return [
            'kode_wig' => 'required|max:30|unique:wigs,kode_wig,'.$this->editingId,
            'nama_wig' => 'required|max:150',
            'indikator_output' => 'nullable|string',
            'bidang' => 'nullable|in:'.implode(',', Wig::BIDANG),
            'tahun' => 'required|integer|min:2020|max:2100',
            'wilayah_id' => 'nullable|exists:wilayahs,id',
        ];
    }

    public function edit(int $id): void
    {
        $w = Wig::findOrFail($id);
        $this->editingId = $w->id;
        $this->kode_wig = $w->kode_wig;
        $this->nama_wig = $w->nama_wig;
        $this->indikator_output = $w->indikator_output ?? '';
        $this->bidang = $w->bidang ?? '';
        $this->tahun = $w->tahun;
        $this->wilayah_id = $w->wilayah_id;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['created_by'] = auth()->id();
        Wig::updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId', 'kode_wig', 'nama_wig', 'indikator_output', 'bidang']);
        $this->tahun = (int) date('Y');
        session()->flash('success', 'WIG tersimpan.');
    }

    public function delete(int $id): void
    {
        Wig::findOrFail($id)->delete();
        session()->flash('success', 'WIG dihapus.');
    }

    public function render()
    {
        $q = Wig::with('wilayah');
        $u = auth()->user();
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $q->where('wilayah_id', $u->wilayah_id);
        }

        return view('livewire.wig-management', [
            'wigs' => $q->latest()->paginate(10),
            'wilayahs' => Wilayah::orderBy('nama')->get(),
        ])->layout('layouts.app');
    }
}
