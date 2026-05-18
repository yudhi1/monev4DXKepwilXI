<?php

namespace App\Livewire;

use App\Exports\IuranExport;
use App\Models\Cabang;
use App\Models\IuranMonitoring as IuranModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IuranMonitoring extends Component
{
    public const MAX_PEMDA_PER_MINGGU = 3;

    public int $tahun;
    public int $bulan;
    public ?int $filter_cabang_id = null;
    public ?int $filter_minggu = null;
    public string $filter_status = '';

    public ?int $editingId = null;
    public ?int $cabang_id = null;
    public int $minggu = 1;
    public string $nama_pemda = '';
    public $tagihan = 0;
    public string $status_bayar = 'belum';
    public $outstanding = 0;
    public string $pic = '';
    public string $kendala = '';
    public string $keterangan = '';
    public ?string $target_penyelesaian = null;

    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->bulan = (int) date('n');
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
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'minggu' => 'required|integer|min:1|max:5',
            'nama_pemda' => 'required|string|max:255',
            'tagihan' => 'required|numeric|min:0',
            'status_bayar' => 'required|in:sudah,sebagian,belum',
            'outstanding' => 'required|numeric|min:0',
            'pic' => 'nullable|string|max:255',
            'kendala' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'target_penyelesaian' => 'nullable|date',
        ];
    }

    public function updatedTagihan(): void { $this->recalcOutstanding(); }
    public function updatedStatusBayar(): void { $this->recalcOutstanding(); }

    private function recalcOutstanding(): void
    {
        if ($this->status_bayar === 'sudah') {
            $this->outstanding = 0;
        } elseif ($this->status_bayar === 'belum') {
            $this->outstanding = (float) $this->tagihan;
        }
    }

    public function save(): void
    {
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }

        $data = $this->validate();

        $sameWeekQ = IuranModel::where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->where('minggu', $this->minggu)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId));

        // Validasi: tidak boleh duplikat nama Pemda di minggu yang sama
        $duplicate = (clone $sameWeekQ)->whereRaw('LOWER(nama_pemda) = ?', [mb_strtolower(trim($this->nama_pemda))])->exists();
        if ($duplicate) {
            $this->dispatch('notify', type: 'error', message: 'Pemda "' . $this->nama_pemda . '" sudah ada di Minggu ' . $this->minggu . '.');
            return;
        }

        // Validasi: maksimal 3 Pemda per minggu
        $existingCount = $sameWeekQ->count();
        if ($existingCount >= self::MAX_PEMDA_PER_MINGGU) {
            $this->dispatch('notify', type: 'error',
                message: 'Maksimal ' . self::MAX_PEMDA_PER_MINGGU . ' Pemda per minggu sudah tercapai.');
            return;
        }

        $data['created_by'] = auth()->id();
        $data['no_urut'] = $this->editingId
            ? IuranModel::find($this->editingId)?->no_urut ?? ($existingCount + 1)
            : $existingCount + 1;

        if ($this->editingId) {
            IuranModel::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Data iuran diperbarui.');
        } else {
            IuranModel::create($data);
            $this->dispatch('notify', type: 'success', message: 'Data iuran ditambahkan.');
        }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $r = IuranModel::findOrFail($id);
        $this->editingId = $r->id;
        $this->cabang_id = $r->cabang_id;
        $this->tahun = $r->tahun;
        $this->bulan = $r->bulan;
        $this->minggu = $r->minggu;
        $this->nama_pemda = $r->nama_pemda;
        $this->tagihan = (float) $r->tagihan;
        $this->status_bayar = $r->status_bayar;
        $this->outstanding = (float) $r->outstanding;
        $this->pic = (string) $r->pic;
        $this->kendala = (string) $r->kendala;
        $this->keterangan = (string) $r->keterangan;
        $this->target_penyelesaian = $r->target_penyelesaian?->format('Y-m-d');
    }

    public function delete(int $id): void
    {
        IuranModel::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Data iuran dihapus.');
    }

    public function resetFilters(): void
    {
        $this->filter_minggu = null;
        $this->filter_status = '';
        $u = auth()->user();
        if (! ($u && $u->hasRole('kantor_cabang') && $u->cabang_id)) {
            $this->filter_cabang_id = null;
        }
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $u = auth()->user();
        $this->cabang_id = ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) ? $u->cabang_id : null;
        $this->minggu = 1;
        $this->nama_pemda = '';
        $this->tagihan = 0;
        $this->status_bayar = 'belum';
        $this->outstanding = 0;
        $this->pic = '';
        $this->kendala = '';
        $this->keterangan = '';
        $this->target_penyelesaian = null;
    }

    private function buildFilteredItems()
    {
        $u = auth()->user();
        $cabangsQ = Cabang::orderBy('nama');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangIds = $cabangsQ->pluck('id');

        $q = IuranModel::with('cabang')
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->whereIn('cabang_id', $cabangIds);

        if ($this->filter_cabang_id) $q->where('cabang_id', $this->filter_cabang_id);
        if ($this->filter_minggu) $q->where('minggu', $this->filter_minggu);
        if ($this->filter_status !== '') $q->where('status_bayar', $this->filter_status);

        return $q->orderBy('minggu')->orderBy('cabang_id')->orderBy('no_urut')->orderBy('id')->get();
    }

    public function exportExcel()
    {
        $items = $this->buildFilteredItems();
        $filename = 'monitoring-iuran-' . $this->tahun . '-' . str_pad($this->bulan, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return Excel::download(new IuranExport($items, self::BULAN), $filename);
    }

    public function exportPdf()
    {
        $items = $this->buildFilteredItems();
        $cabangFilter = $this->filter_cabang_id ? Cabang::find($this->filter_cabang_id)?->nama : '';
        $periode = (self::BULAN[$this->bulan] ?? '') . ' ' . $this->tahun
            . ($this->filter_minggu ? ' · Minggu ' . $this->filter_minggu : '');
        $pdf = Pdf::loadView('reports.iuran-pdf', [
            'items' => $items,
            'bulanLabels' => self::BULAN,
            'periode' => $periode,
            'cabangFilter' => $cabangFilter,
        ])->setPaper('a4', 'landscape');
        $filename = 'monitoring-iuran-' . $this->tahun . '-' . str_pad($this->bulan, 2, '0', STR_PAD_LEFT) . '.pdf';
        return response()->streamDownload(fn() => print($pdf->output()), $filename);
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

        $itemsQ = IuranModel::with('cabang')
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan);

        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $itemsQ->where('cabang_id', $u->cabang_id);
        } else {
            $itemsQ->whereIn('cabang_id', $cabangs->pluck('id'));
            if ($this->filter_cabang_id) {
                $itemsQ->where('cabang_id', $this->filter_cabang_id);
            }
        }

        if ($this->filter_minggu) {
            $itemsQ->where('minggu', $this->filter_minggu);
        }
        if ($this->filter_status !== '') {
            $itemsQ->where('status_bayar', $this->filter_status);
        }

        $items = $itemsQ->orderBy('minggu')
            ->orderBy('cabang_id')
            ->orderBy('no_urut')
            ->orderBy('id')
            ->get();

        return view('livewire.iuran-monitoring', [
            'items' => $items,
            'bulanLabels' => self::BULAN,
            'maxPemdaPerMinggu' => self::MAX_PEMDA_PER_MINGGU,
            'cabangs' => $cabangs,
        ])->layout('layouts.app');
    }
}
