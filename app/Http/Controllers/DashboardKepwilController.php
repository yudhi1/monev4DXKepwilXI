<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard kedeputian wilayah: peringkat kantor cabang pada satu periode
 * mingguan, plus rincian Lead Measure untuk cabang yang dipilih.
 *
 * Rincian dapat ditampilkan untuk dua minggu berdampingan agar perkembangan
 * antar minggu terlihat langsung tanpa berpindah halaman.
 *
 * Perhitungannya dipindahkan apa adanya dari App\Livewire\KepwilDashboard.
 */
class DashboardKepwilController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);
        $minggu = min(max((int) $request->query('minggu', (int) ceil(date('j') / 7)), 1), LeadMeasureRealisasi::JUMLAH_MINGGU);

        // Minggu pembanding bersifat opsional dan harus berbeda dari minggu utama.
        $mingguBanding = $request->query('minggu_banding');
        $mingguBanding = $mingguBanding ? min(max((int) $mingguBanding, 1), LeadMeasureRealisasi::JUMLAH_MINGGU) : null;

        if ($mingguBanding === $minggu) {
            $mingguBanding = null;
        }

        $cabangs = Cabang::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        $wigs = Wig::query()
            ->where('tahun', $tahun)
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang']);

        $wigId = $request->query('wig_id') ? (int) $request->query('wig_id') : null;

        if ($wigId && ! $wigs->contains('id', $wigId)) {
            $wigId = null;
        }

        $peringkat = $this->peringkatCabang($cabangs, $wigId, $tahun, $bulan, $minggu);

        /*
         | Tanpa pilihan, rincian menampilkan seluruh unit kerja. Sebelumnya
         | cabang peringkat teratas dipilih otomatis, sehingga terlihat seakan
         | hanya satu unit kerja yang punya Lead Measure.
         */
        $cabangDipilih = $request->query('cabang_id') ? (int) $request->query('cabang_id') : null;

        if ($cabangDipilih && ! $cabangs->contains('id', $cabangDipilih)) {
            $cabangDipilih = null;
        }

        // Daftar LAG menyempit mengikuti WIG dan cabang yang sedang dipilih.
        $lags = $this->lagTerpilih($wigId, $cabangDipilih);
        $lagId = $request->query('lag_id') ? (int) $request->query('lag_id') : null;

        if ($lagId && ! $lags->contains('id', $lagId)) {
            $lagId = null;
        }

        $konteks = ['wig_id' => $wigId, 'lag_id' => $lagId];

        return Inertia::render('Dashboard/Kepwil', [
            'cabangs' => $cabangs,
            'wigs' => $wigs,
            'lags' => $lags,
            'jumlahMinggu' => LeadMeasureRealisasi::JUMLAH_MINGGU,
            'peringkat' => $peringkat,
            'ringkasan' => [
                'total_cabang' => $cabangs->count(),
                'total_wig' => $wigs->count(),
                'rata_wilayah' => round(collect($peringkat)->avg('pct') ?: 0, 2),
                'cabang_on_track' => collect($peringkat)->where('pct', '>=', 100)->count(),
            ],
            'detailLead' => $this->detailLead($cabangDipilih, $cabangs, $wigs, $konteks, $tahun, $bulan, $minggu),
            'detailLeadBanding' => $mingguBanding
                ? $this->detailLead($cabangDipilih, $cabangs, $wigs, $konteks, $tahun, $bulan, $mingguBanding)
                : null,
            'sasaran' => $this->sasaran($wigs, $wigId, $lagId, $cabangDipilih),
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'minggu' => $minggu,
                'minggu_banding' => $mingguBanding,
                'wig_id' => $wigId,
                'lag_id' => $lagId,
                'cabang_id' => $cabangDipilih,
            ],
        ]);
    }

    /**
     * Ringkasan sasaran yang sedang ditinjau: kalimat WIG, kalimat LAG, dan
     * daftar Lead Measure-nya — meniru kepala tabel pada laporan cetak.
     */
    private function sasaran($wigs, ?int $wigId, ?int $lagId, ?int $cabangId): ?array
    {
        $wig = $wigId ? $wigs->firstWhere('id', $wigId) : null;

        if (! $wig) {
            return null;
        }

        $lag = $lagId ? LagMeasure::find($lagId) : null;

        $leads = LeadMeasure::query()
            ->where('wig_id', $wigId)
            ->where('is_active', true)
            ->when($cabangId, fn ($q) => $q->where('cabang_id', $cabangId))
            ->when($lagId, fn ($q) => $q->where('lag_measure_id', $lagId))
            ->orderBy('kode_lead')
            ->get(['id', 'kode_lead', 'nama_lead']);

        return [
            'wig' => ['kode' => $wig->kode_wig, 'nama' => $wig->nama_wig],
            'lag' => $lag ? ['kode' => $lag->kode_lag, 'nama' => $lag->nama_lag] : null,
            'leads' => $leads->map(fn (LeadMeasure $l) => [
                'kode' => $l->kode_lead,
                'nama' => $l->nama_lead,
            ])->all(),
        ];
    }

    private function lagTerpilih(?int $wigId, ?int $cabangId)
    {
        if (! $wigId) {
            return collect();
        }

        return LagMeasure::where('wig_id', $wigId)
            ->when($cabangId, fn ($q) => $q->where(
                fn ($sub) => $sub->whereNull('cabang_id')->orWhere('cabang_id', $cabangId)
            ))
            ->orderBy('kode_lag')
            ->get(['id', 'kode_lag', 'nama_lag']);
    }

    private function peringkatCabang($cabangs, ?int $wigId, int $tahun, int $bulan, int $minggu): array
    {
        $agregat = LeadMeasureRealisasi::where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('minggu_ke', $minggu)
            ->whereIn('cabang_id', $cabangs->pluck('id'))
            ->when($wigId, fn ($q) => $q->whereIn(
                'lead_measure_id',
                LeadMeasure::where('wig_id', $wigId)->where('is_active', true)->pluck('id')
            ))
            ->selectRaw('cabang_id, AVG(persentase) as pct, COUNT(*) as cnt')
            ->groupBy('cabang_id')
            ->get()
            ->keyBy('cabang_id');

        return $cabangs
            ->map(function (Cabang $cabang) use ($agregat) {
                $baris = $agregat->get($cabang->id);
                $pct = $baris ? round((float) $baris->pct, 2) : 0;

                return [
                    'cabang_id' => $cabang->id,
                    'nama' => $cabang->nama,
                    'kode' => $cabang->kode,
                    'pct' => $pct,
                    'jumlah_lead' => (int) ($baris->cnt ?? 0),
                    'status' => $this->status($pct),
                ];
            })
            ->sortByDesc('pct')
            ->values()
            ->all();
    }

    private function detailLead(?int $cabangId, $cabangs, $wigs, array $konteks, int $tahun, int $bulan, int $minggu): array
    {
        // Tanpa cabang tertentu, seluruh unit kerja dalam jangkauan user ikut ditampilkan.
        $cabangIds = $cabangId ? [$cabangId] : $cabangs->pluck('id')->all();

        if ($cabangIds === []) {
            return [];
        }

        [$pTahun, $pBulan, $pMinggu] = $this->periodeSebelumnya($tahun, $bulan, $minggu);

        $leads = LeadMeasure::with(['wig:id,kode_wig,nama_wig', 'lagMeasure:id,kode_lag', 'cabang:id,nama'])
            ->whereIn('cabang_id', $cabangIds)
            ->where('is_active', true)
            ->whereIn('wig_id', $wigs->pluck('id'))
            ->when($konteks['wig_id'], fn ($q, $id) => $q->where('wig_id', $id))
            ->when($konteks['lag_id'], fn ($q, $id) => $q->where('lag_measure_id', $id))
            ->get();

        $ambil = fn (int $t, int $b, int $m) => LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
            ->whereIn('cabang_id', $cabangIds)
            ->where('tahun', $t)
            ->where('bulan', $b)
            ->where('minggu_ke', $m)
            ->get()
            ->keyBy('lead_measure_id');

        $sekarang = $ambil($tahun, $bulan, $minggu);
        $sebelumnya = $ambil($pTahun, $pBulan, $pMinggu);

        return $leads
            ->map(function (LeadMeasure $lead) use ($sekarang, $sebelumnya) {
                $rNow = $sekarang->get($lead->id);
                $rPrev = $sebelumnya->get($lead->id);
                $pctNow = round((float) ($rNow?->persentase ?? 0), 2);
                $pctPrev = round((float) ($rPrev?->persentase ?? 0), 2);
                $selisih = $pctNow - $pctPrev;

                return [
                    'id' => $lead->id,
                    'kode_lead' => $lead->kode_lead,
                    'nama_lead' => $lead->nama_lead,
                    'cabang' => $lead->cabang?->nama,
                    'wig' => $lead->wig?->kode_wig,
                    'wig_nama' => $lead->wig?->nama_wig,
                    'lag' => $lead->lagMeasure?->kode_lag,
                    'target' => (float) ($rNow?->target ?? 0),
                    'realisasi' => (float) ($rNow?->realisasi ?? 0),
                    'pct' => $pctNow,
                    'pct_sebelumnya' => $pctPrev,
                    'status' => $this->status($pctNow),
                    'tren' => abs($selisih) < 1 ? 'stabil' : ($selisih > 0 ? 'naik' : 'turun'),
                    'keterangan' => $rNow?->catatan ?? '',
                ];
            })
            ->sortBy('pct')
            ->values()
            ->all();
    }

    /** Ambang status mengikuti versi lama: ≥100 on track, ≥90 waspada, sisanya awas. */
    private function status(float $pct): string
    {
        return $pct >= 100 ? 'on' : ($pct >= 90 ? 'waspada' : 'awas');
    }

    private function periodeSebelumnya(int $tahun, int $bulan, int $minggu): array
    {
        if ($minggu > 1) {
            return [$tahun, $bulan, $minggu - 1];
        }

        if ($bulan > 1) {
            return [$tahun, $bulan - 1, LeadMeasureRealisasi::JUMLAH_MINGGU];
        }

        return [$tahun - 1, 12, LeadMeasureRealisasi::JUMLAH_MINGGU];
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
