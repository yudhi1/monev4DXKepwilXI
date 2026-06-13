<?php

namespace App\Livewire\MonitoringKinerja;

use App\Imports\GenericSheetImport;
use App\Models\MonevApcUpload;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ApcDashboard extends Component
{
    use WithFileUploads;

    public string $indikator = 'total';

    public $file;

    public ?int $tahun = null;

    public function mount(string $indikator): void
    {
        abort_unless(array_key_exists($indikator, MonevApcUpload::INDIKATOR), 404);
        $this->indikator = $indikator;
        $this->tahun = (int) date('Y');
    }

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'tahun' => 'nullable|integer|min:2000|max:2100',
        ];
    }

    protected function canManage(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'kedeputian_wilayah']);
    }

    public function upload(): void
    {
        if (! $this->canManage()) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak punya akses untuk mengupload data.');

            return;
        }

        $this->validate();

        $sheets = Excel::toArray(new GenericSheetImport, $this->file);
        $rows = $sheets[0] ?? [];

        $path = $this->file->store('apc-uploads');

        MonevApcUpload::create([
            'indikator' => $this->indikator,
            'tahun' => $this->tahun,
            'original_name' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'sheet_json' => $rows,
            'rows_count' => max(0, count($rows) - 1),
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset('file');
        $this->dispatch('notify', type: 'success', message: 'File Excel berhasil diupload.');
    }

    public function download(int $id)
    {
        $upload = MonevApcUpload::where('indikator', $this->indikator)->findOrFail($id);

        return Storage::download($upload->file_path, $upload->original_name);
    }

    public function hapus(int $id): void
    {
        if (! $this->canManage()) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak punya akses untuk menghapus data.');

            return;
        }

        $upload = MonevApcUpload::where('indikator', $this->indikator)->findOrFail($id);
        Storage::delete($upload->file_path);
        $upload->delete();

        $this->dispatch('notify', type: 'success', message: 'Data upload dihapus.');
    }

    public function render()
    {
        $latest = MonevApcUpload::where('indikator', $this->indikator)->latest()->first();
        $history = MonevApcUpload::where('indikator', $this->indikator)
            ->with('uploader')
            ->latest()
            ->take(15)
            ->get();

        return view('livewire.monitoring-kinerja.apc-dashboard', [
            'label' => MonevApcUpload::INDIKATOR[$this->indikator],
            'semuaIndikator' => MonevApcUpload::INDIKATOR,
            'latest' => $latest,
            'history' => $history,
            'canManage' => $this->canManage(),
        ])->layout('layouts.app');
    }
}
