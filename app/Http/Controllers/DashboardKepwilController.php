<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
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
 * Perhitungannya dipindahkan apa adanya dari App\Livewire\KepwilDashboard.
 */
class DashboardKepwilController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);
        $minggu = min(max((int) $request->query('minggu', (int) ceil(date('j') / 7)), 1), 4);

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

        $peringkat = $this->peringkatCabang($cabangs, $wigId, $tahun, $bulan, $minggu);

        // Bila belum ada pilihan, ambil cabang teratas.
        $cabangDipilih = $request->query('cabang_id') ? (int) $request->query('cabang_id') : null;

        if (! $cabangDipilih || ! $cabangs->contains('id', $cabangDipilih)) {
            $cabangDipilih = $peringkat[0]['cabang_id'] ?? null;
        }

        return Inertia::render('Dashboard/Kepwil', [
            'cabangs' => $cabangs,
            'wigs' => $wigs,
            'peringkat' => $peringkat,
            'ringkasan' => [
                'total_cabang' => $cabangs->count(),
                'total_wig' => $wigs->count(),
                'rata_wilayah' => round(collect($peringkat)->avg('pct') ?: 0, 2),
                'cabang_on_track' => collect($peringkat)->where('pct', '>=', 100)->count(),
            ],
            'detailLead' => $this->detailLead($cabangDipilih, $cabangs, $wigs, $wigId, $tahun, $bulan, $minggu),
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'minggu' => $minggu,
                'wig_id' => $wigId,
                'cabang_id' => $cabangDipilih,
            ],
        ]);
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

    private function detailLead(?int $cabangId, $cabangs, $wigs, ?int $wigId, int $tahun, int $bulan, int $minggu): array
    {
        if (! $cabangId || ! $cabangs->contains('id', $cabangId)) {
            return [];
        }

        [$pTahun, $pBulan, $pMinggu] = $this->periodeSebelumnya($tahun, $bulan, $minggu);

        $leads = LeadMeasure::with('wig:id,kode_wig,nama_wig')
            ->where('cabang_id', $cabangId)
            ->where('is_active', true)
            ->whereIn('wig_id', $wigs->pluck('id'))
            ->when($wigId, fn ($q) => $q->where('wig_id', $wigId))
            ->get();

        $ambil = fn (int $t, int $b, int $m) => LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
            ->where('cabang_id', $cabangId)
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
                    'wig' => $lead->wig?->kode_wig,
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
            return [$tahun, $bulan - 1, 4];
        }

        return [$tahun - 1, 12, 4];
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
