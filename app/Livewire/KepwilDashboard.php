<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\Wig;
use Livewire\Component;

class KepwilDashboard extends Component
{
    public int $tahun;

    public int $bulan;

    public int $minggu;

    public ?int $selected_cabang_id = null;

    public ?int $filter_wig_id = null;

    private function prevPeriod(int $tahun, int $bulan, int $minggu): array
    {
        if ($minggu > 1) {
            return [$tahun, $bulan, $minggu - 1];
        }
        if ($bulan > 1) {
            return [$tahun, $bulan - 1, 4];
        }

        return [$tahun - 1, 12, 4];
    }

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->bulan = (int) date('n');
        $this->minggu = min(max((int) ceil(date('j') / 7), 1), 4);
    }

    public function render()
    {
        $u = auth()->user();
        $wilayahId = $u?->wilayah_id;

        $cabangsQ = Cabang::query()->orderBy('nama');
        if ($u && ! $u->hasRole('admin') && $wilayahId) {
            $cabangsQ->where('wilayah_id', $wilayahId);
        }
        $cabangs = $cabangsQ->get();
        $cabangIds = $cabangs->pluck('id');

        $wigsQ = Wig::where('tahun', $this->tahun)->orderBy('kode_wig');
        if ($u && ! $u->hasRole('admin') && $wilayahId) {
            $wigsQ->where('wilayah_id', $wilayahId);
        }
        $wigs = $wigsQ->get();

        // === RANKING CABANG ===
        $rankingQ = LeadMeasureRealisasi::where('tahun', $this->tahun)
            ->where('bulan', $this->bulan)
            ->where('minggu_ke', $this->minggu)
            ->whereIn('cabang_id', $cabangIds)
            ->selectRaw('cabang_id, AVG(persentase) as pct, COUNT(*) as cnt')
            ->groupBy('cabang_id');

        if ($this->filter_wig_id) {
            $rankingQ->whereIn('lead_measure_id',
                LeadMeasure::where('wig_id', $this->filter_wig_id)->where('is_active', true)->pluck('id')
            );
        }

        $ranking = $rankingQ->get()->keyBy('cabang_id');

        $rankingRows = $cabangs->map(function ($c) use ($ranking) {
            $r = $ranking->get($c->id);
            $pct = $r ? round((float) $r->pct, 2) : 0;
            $status = $pct >= 100 ? ['success', '🟢', 'On Track']
                : ($pct >= 90 ? ['warning', '🟡', 'Waspada']
                : ['danger', '🔴', 'Awas']);

            return [
                'cabang' => $c,
                'pct' => $pct,
                'jumlah_lead' => $r?->cnt ?? 0,
                'status' => $status,
            ];
        })->sortByDesc('pct')->values();

        // === SUMMARY CARDS ===
        $totalCabang = $cabangs->count();
        $totalWig = $wigs->count();
        $avgWilayah = $rankingRows->avg('pct') ?: 0;
        $cabangOnTrack = $rankingRows->where('pct', '>=', 100)->count();

        // === DETAIL LEADS PER KC (cabang terpilih) ===
        if (! $this->selected_cabang_id && $rankingRows->count()) {
            $this->selected_cabang_id = $rankingRows->first()['cabang']->id;
        }

        $detailLeads = collect();
        if ($this->selected_cabang_id && $cabangIds->contains($this->selected_cabang_id)) {
            [$pTahun, $pBulan, $pMinggu] = $this->prevPeriod($this->tahun, $this->bulan, $this->minggu);

            $leadsQ = LeadMeasure::with('wig')
                ->where('cabang_id', $this->selected_cabang_id)
                ->where('is_active', true)
                ->whereIn('wig_id', $wigs->pluck('id'));
            if ($this->filter_wig_id) {
                $leadsQ->where('wig_id', $this->filter_wig_id);
            }
            $leads = $leadsQ->get();

            $realNow = LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
                ->where('cabang_id', $this->selected_cabang_id)
                ->where('tahun', $this->tahun)->where('bulan', $this->bulan)->where('minggu_ke', $this->minggu)
                ->get()->keyBy('lead_measure_id');

            $realPrev = LeadMeasureRealisasi::whereIn('lead_measure_id', $leads->pluck('id'))
                ->where('cabang_id', $this->selected_cabang_id)
                ->where('tahun', $pTahun)->where('bulan', $pBulan)->where('minggu_ke', $pMinggu)
                ->get()->keyBy('lead_measure_id');

            $detailLeads = $leads->map(function ($lead) use ($realNow, $realPrev) {
                $rNow = $realNow->get($lead->id);
                $rPrev = $realPrev->get($lead->id);
                $pctNow = (float) ($rNow?->persentase ?? 0);
                $pctPrev = (float) ($rPrev?->persentase ?? 0);
                $status = $pctNow >= 100 ? ['success', '🟢', 'On Track']
                    : ($pctNow >= 90 ? ['warning', '🟡', 'Waspada']
                    : ['danger', '🔴', 'Awas']);
                $diff = $pctNow - $pctPrev;
                $tren = abs($diff) < 1 ? ['→', 'text-secondary', 'Stabil']
                    : ($diff > 0 ? ['↗', 'text-success', 'Naik']
                    : ['↘', 'text-danger', 'Turun']);

                return [
                    'lead' => $lead,
                    'wig' => $lead->wig,
                    'target' => (float) ($rNow?->target ?? 0),
                    'realisasi' => (float) ($rNow?->realisasi ?? 0),
                    'pct' => round($pctNow, 2),
                    'pct_prev' => round($pctPrev, 2),
                    'status' => $status,
                    'tren' => $tren,
                    'keterangan' => $rNow?->catatan ?? '',
                ];
            })->sortBy('pct')->values();
        }

        return view('livewire.kepwil-dashboard', [
            'cabangs' => $cabangs,
            'wigs' => $wigs,
            'rankingRows' => $rankingRows,
            'totalCabang' => $totalCabang,
            'totalWig' => $totalWig,
            'avgWilayah' => round($avgWilayah, 2),
            'cabangOnTrack' => $cabangOnTrack,
            'detailLeads' => $detailLeads,
        ])->layout('layouts.app');
    }
}
