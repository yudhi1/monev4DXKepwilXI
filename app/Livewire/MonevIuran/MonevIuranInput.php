<?php

namespace App\Livewire\MonevIuran;

use App\Models\Cabang;
use App\Models\MonevIuranRealisasi;
use App\Models\MonevSegmen;
use Livewire\Component;

class MonevIuranInput extends Component
{
    public int $tahun;

    public int $bulan;

    public ?int $cabang_id = null;

    public array $realisasiData = [];

    /** Segmen yang sedang diedit (null = tidak ada) */
    public ?int $editingSegmenId = null;

    /** Pilihan dropdown: '' = belum pilih, 'konsolidasi' = semua cabang, atau id cabang */
    public string $pilihan = '';

    /** Mode konsolidasi: total semua cabang (lihat-saja, tidak bisa diedit) */
    public bool $konsolidasi = false;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->bulan = (int) date('n');
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
            $this->pilihan = (string) $u->cabang_id;
        }
        $this->loadData();
    }

    public function updatedPilihan(): void
    {
        $this->editingSegmenId = null;

        if ($this->pilihan === 'konsolidasi') {
            $this->konsolidasi = true;
            $this->cabang_id = null;
        } elseif ($this->pilihan === '') {
            $this->konsolidasi = false;
            $this->cabang_id = null;
        } else {
            $this->konsolidasi = false;
            $this->cabang_id = (int) $this->pilihan;
        }

        $this->loadData();
    }

    public function updatedTahun()
    {
        $this->editingSegmenId = null;
        $this->loadData();
    }

    public function updatedBulan()
    {
        $this->editingSegmenId = null;
        $this->loadData();
    }

    private function scopedCabangs()
    {
        $u = auth()->user();
        $q = Cabang::orderBy('nama');

        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $q->where('id', $u->cabang_id);
        } elseif ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $q->where('wilayah_id', $u->wilayah_id);
        }

        return $q->get();
    }

    private function loadData(): void
    {
        $this->realisasiData = [];

        if ($this->konsolidasi) {
            $this->loadKonsolidasi();

            return;
        }

        if (! $this->cabang_id) {
            return;
        }

        $segmens = MonevSegmen::where('is_active', true)->orderBy('urutan')->orderBy('id')->get();

        foreach ($segmens as $segmen) {
            $r = MonevIuranRealisasi::where('cabang_id', $this->cabang_id)
                ->where('tahun', $this->tahun)
                ->where('bulan', $this->bulan)
                ->where('segmen_id', $segmen->id)
                ->first();

            $this->realisasiData[$segmen->id] = [
                'segmen_id' => $segmen->id,
                'nama' => $segmen->nama,
                'mg1' => (float) ($r?->mg1 ?? 0),
                'mg2' => (float) ($r?->mg2 ?? 0),
                'mg3' => (float) ($r?->mg3 ?? 0),
                'mg4' => (float) ($r?->mg4 ?? 0),
                'realisasi_sd_bulan_lalu' => (float) ($r?->realisasi_sd_bulan_lalu ?? 0),
                'keterangan' => (string) ($r?->keterangan ?? ''),
                'locked' => $r?->status_periode === 'final',
            ];
        }
    }

    /** Muat data konsolidasi: total seluruh cabang (sesuai hak akses) per segmen */
    private function loadKonsolidasi(): void
    {
        $cabangIds = $this->scopedCabangs()->pluck('id');
        $segmens = MonevSegmen::where('is_active', true)->orderBy('urutan')->orderBy('id')->get();

        foreach ($segmens as $segmen) {
            $agg = MonevIuranRealisasi::where('tahun', $this->tahun)
                ->where('bulan', $this->bulan)
                ->where('segmen_id', $segmen->id)
                ->whereIn('cabang_id', $cabangIds)
                ->selectRaw('SUM(mg1) m1, SUM(mg2) m2, SUM(mg3) m3, SUM(mg4) m4, SUM(realisasi_sd_bulan_lalu) sdl')
                ->first();

            $this->realisasiData[$segmen->id] = [
                'segmen_id' => $segmen->id,
                'nama' => $segmen->nama,
                'mg1' => (float) ($agg->m1 ?? 0),
                'mg2' => (float) ($agg->m2 ?? 0),
                'mg3' => (float) ($agg->m3 ?? 0),
                'mg4' => (float) ($agg->m4 ?? 0),
                'realisasi_sd_bulan_lalu' => (float) ($agg->sdl ?? 0),
                'keterangan' => '',
                'locked' => true,
            ];
        }
    }

    /** Mulai edit satu baris segmen */
    public function editRow(int $segmenId): void
    {
        if (! empty($this->realisasiData[$segmenId]['locked'])) {
            $this->dispatch('notify', type: 'error', message: 'Periode sudah Final. Tidak bisa diedit.');

            return;
        }
        $this->editingSegmenId = $segmenId;
    }

    /** Batal edit, kembalikan nilai dari database */
    public function cancelEdit(): void
    {
        $this->editingSegmenId = null;
        $this->loadData();
    }

    /** Simpan satu baris segmen */
    public function saveRow(int $segmenId): void
    {
        $u = auth()->user();
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        }

        if (! $this->cabang_id) {
            $this->dispatch('notify', type: 'error', message: 'Pilih Kantor Cabang terlebih dahulu.');

            return;
        }

        $data = $this->realisasiData[$segmenId] ?? null;
        if (! $data) {
            return;
        }

        if (! empty($data['locked'])) {
            $this->dispatch('notify', type: 'error', message: 'Periode sudah Final. Tidak bisa diedit.');

            return;
        }

        // Validasi: nominal tidak boleh negatif
        foreach (['realisasi_sd_bulan_lalu', 'mg1', 'mg2', 'mg3', 'mg4'] as $field) {
            if (! is_numeric($data[$field] ?? 0) || (float) ($data[$field] ?? 0) < 0) {
                $this->dispatch('notify', type: 'error', message: 'Nilai nominal tidak boleh negatif atau kosong.');

                return;
            }
        }

        MonevIuranRealisasi::updateOrCreate(
            [
                'cabang_id' => $this->cabang_id,
                'segmen_id' => $segmenId,
                'tahun' => $this->tahun,
                'bulan' => $this->bulan,
            ],
            [
                'mg1' => (float) ($data['mg1'] ?? 0),
                'mg2' => (float) ($data['mg2'] ?? 0),
                'mg3' => (float) ($data['mg3'] ?? 0),
                'mg4' => (float) ($data['mg4'] ?? 0),
                'realisasi_sd_bulan_lalu' => (float) ($data['realisasi_sd_bulan_lalu'] ?? 0),
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => auth()->id(),
            ]
        );

        $this->editingSegmenId = null;
        $this->dispatch('notify', type: 'success', message: 'Realisasi segmen "'.$data['nama'].'" disimpan.');
        $this->loadData();
    }

    public function lockPeriode(): void
    {
        if (! $this->cabang_id) {
            $this->dispatch('notify', type: 'error', message: 'Pilih Kantor Cabang terlebih dahulu.');

            return;
        }

        $exists = MonevIuranRealisasi::where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->exists();

        if (! $exists) {
            $this->dispatch('notify', type: 'error', message: 'Belum ada data untuk dikunci.');

            return;
        }

        MonevIuranRealisasi::where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->update([
                'status_periode' => 'final',
                'locked_at' => now(),
                'locked_by' => auth()->id(),
            ]);

        $this->editingSegmenId = null;
        $this->dispatch('notify', type: 'success', message: 'Periode terkunci (Final). Data tidak bisa diubah lagi.');
        $this->loadData();
    }

    public function unlockPeriode(): void
    {
        if (! auth()->user()->hasRole('admin')) {
            $this->dispatch('notify', type: 'error', message: 'Hanya admin yang bisa membuka kunci periode.');

            return;
        }

        MonevIuranRealisasi::where('cabang_id', $this->cabang_id)
            ->where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->update([
                'status_periode' => 'draft',
                'locked_at' => null,
                'locked_by' => null,
            ]);

        $this->dispatch('notify', type: 'success', message: 'Periode dibuka kembali (Draft).');
        $this->loadData();
    }

    public function render()
    {
        $cabangs = $this->scopedCabangs();

        $isPeriodeLocked = $this->cabang_id
            ? MonevIuranRealisasi::where('cabang_id', $this->cabang_id)
                ->where('tahun', $this->tahun)
                ->where('bulan', $this->bulan)
                ->where('status_periode', 'final')
                ->exists()
            : false;

        return view('livewire.monev-iuran.input', [
            'cabangs' => $cabangs,
            'konsolidasi' => $this->konsolidasi,
            'bulanLabels' => MonevIuranRealisasi::BULAN,
            'isPeriodeLocked' => $isPeriodeLocked,
            'bulanLalu' => MonevIuranRealisasi::BULAN[$this->bulan == 1 ? 12 : $this->bulan - 1] ?? '',
        ])->layout('layouts.app');
    }
}
