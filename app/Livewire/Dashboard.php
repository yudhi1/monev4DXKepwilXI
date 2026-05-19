<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\LeadMeasureRealisasi;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use Livewire\Component;

class Dashboard extends Component
{
    public int $tahun;

    public int $bulan;

    public int $minggu;

    public ?int $cabang_id = null;

    public function mount(): void
    {
        $this->tahun = (int) date('Y');
        $this->bulan = (int) date('n');
        $this->minggu = $this->currentWeekOfMonth();
        $u = auth()->user();
        if ($u && $u->cabang_id) {
            $this->cabang_id = $u->cabang_id;
        } else {
            $this->cabang_id = Cabang::orderBy('nama')->value('id');
        }
    }

    private function currentWeekOfMonth(): int
    {
        $w = (int) ceil(date('j') / 7);

        return min(max($w, 1), 4);
    }

    /**
     * Pearson correlation. Mengembalikan ['r' => float|null, 'label' => string, 'level' => 'kuat|sedang|lemah|kurang_data'].
     */
    private function pearson(array $x, array $y): array
    {
        $n = min(count($x), count($y));
        // Drop trailing zeros (bulan yang belum diisi sama sekali) supaya tidak bias.
        while ($n > 0 && (($x[$n - 1] ?? 0) == 0) && (($y[$n - 1] ?? 0) == 0)) {
            $n--;
        }
        if ($n < 3) {
            return ['r' => null, 'label' => 'Data belum cukup', 'level' => 'kurang_data'];
        }
        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $num = 0.0;
        $dx = 0.0;
        $dy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $a = $x[$i] - $mx;
            $b = $y[$i] - $my;
            $num += $a * $b;
            $dx += $a * $a;
            $dy += $b * $b;
        }
        if ($dx == 0.0 || $dy == 0.0) {
            return ['r' => null, 'label' => 'Tidak ada variasi', 'level' => 'kurang_data'];
        }
        $r = $num / sqrt($dx * $dy);
        $r = round($r, 2);
        if ($r >= 0.6) {
            return ['r' => $r, 'label' => 'Lead efektif',   'level' => 'kuat'];
        }
        if ($r >= 0.3) {
            return ['r' => $r, 'label' => 'Lead cukup',     'level' => 'sedang'];
        }
        if ($r >= -0.3) {
            return ['r' => $r, 'label' => 'Lead lemah',     'level' => 'lemah'];
        }

        return ['r' => $r, 'label' => 'Berlawanan arah', 'level' => 'lemah'];
    }

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

    public function render()
    {
        $u = auth()->user();

        $cabangsQ = Cabang::with('wilayah');
        if ($u && $u->hasRole('kantor_cabang') && $u->cabang_id) {
            $cabangsQ->where('id', $u->cabang_id);
        } elseif ($u && $u->hasRole('kedeputian_wilayah') && $u->wilayah_id) {
            $cabangsQ->where('wilayah_id', $u->wilayah_id);
        }
        $cabangs = $cabangsQ->orderBy('nama')->get();

        $cabangId = $this->cabang_id;
        $wigsQ = Wig::with([
            'lagMeasures' => function ($q) use ($cabangId) {
                if ($cabangId) {
                    $q->where(function ($w) use ($cabangId) {
                        $w->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                    });
                }
            },
            'lagMeasures.leadMeasures' => function ($q) use ($cabangId) {
                $q->where('is_active', true);
                if ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                }
            },
        ])->where('tahun', $this->tahun);
        if ($u && ! $u->hasRole('admin') && $u->wilayah_id) {
            $wigsQ->where('wilayah_id', $u->wilayah_id);
        }
        $wigs = $wigsQ->orderBy('kode_wig')->get();

        [$pTahun, $pBulan, $pMinggu] = $this->prevPeriod($this->tahun, $this->bulan, $this->minggu);

        $realisasiNow = LeadMeasureRealisasi::where([
            'tahun' => $this->tahun, 'bulan' => $this->bulan, 'minggu_ke' => $this->minggu,
            'cabang_id' => $this->cabang_id,
        ])->get()->keyBy('lead_measure_id');

        $realisasiPrev = LeadMeasureRealisasi::where([
            'tahun' => $pTahun, 'bulan' => $pBulan, 'minggu_ke' => $pMinggu,
            'cabang_id' => $this->cabang_id,
        ])->get()->keyBy('lead_measure_id');

        $totalLead = 0;
        $onTrack = 0;
        $sumPct = 0;
        foreach ($wigs as $w) {
            foreach ($w->lagMeasures as $lag) {
                foreach ($lag->leadMeasures as $lead) {
                    $r = $realisasiNow->get($lead->id);
                    $pct = $r?->persentase ?? 0;
                    $totalLead++;
                    $sumPct += $pct;
                    if ($pct >= 100) {
                        $onTrack++;
                    }
                }
            }
        }
        $avgPct = $totalLead > 0 ? round($sumPct / $totalLead, 2) : 0;

        $perBulan = LeadMeasureRealisasi::where('tahun', $this->tahun)
            ->when($this->cabang_id, fn ($q) => $q->where('cabang_id', $this->cabang_id))
            ->selectRaw('bulan, AVG(persentase) as pct')
            ->groupBy('bulan')->orderBy('bulan')->pluck('pct', 'bulan')->toArray();
        $bulanData = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanData[] = round($perBulan[$i] ?? 0, 2);
        }

        $rankingQ = LeadMeasureRealisasi::where('tahun', $this->tahun)
            ->selectRaw('cabang_id, AVG(persentase) as pct')
            ->groupBy('cabang_id')->orderByDesc('pct')->with('cabang');
        if ($u && $u->hasRole('kedeputian_wilayah') && $u->wilayah_id) {
            $rankingQ->whereIn('cabang_id', $cabangs->pluck('id'));
        }
        $rankingCabang = $rankingQ->limit(10)->get();

        // WIG progress per cabang terpilih
        $wigProgress = [];
        if ($this->cabang_id) {
            $targets = WigTarget::with('wig')
                ->where('cabang_id', $this->cabang_id)
                ->whereHas('wig', fn ($q) => $q->where('tahun', $this->tahun))
                ->get();
            foreach ($targets as $t) {
                $nilaiAwal = (float) $t->nilai_awal;
                $nilaiTarget = (float) $t->nilai_target;
                $range = $nilaiTarget - $nilaiAwal;
                $kontribusi = (float) LeadMeasureRealisasi::whereHas('leadMeasure',
                    fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true))
                    ->where('cabang_id', $this->cabang_id)
                    ->sum('realisasi');
                $nilaiSekarang = $nilaiAwal + $kontribusi;
                $pct = $range > 0 ? round((($nilaiSekarang - $nilaiAwal) / $range) * 100, 2) : 0;
                $pct = max(0, $pct);

                // Realisasi WIG bulanan & rata-rata capaian aktivitas (Lead) bulanan
                $wigRealBulan = WigRealisasi::where('wig_id', $t->wig_id)
                    ->where('cabang_id', $this->cabang_id)
                    ->where('tahun', $this->tahun)
                    ->pluck('nilai', 'bulan')->toArray();
                $leadAvgBulan = LeadMeasureRealisasi::whereHas('leadMeasure',
                    fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true))
                    ->where('cabang_id', $this->cabang_id)
                    ->where('tahun', $this->tahun)
                    ->selectRaw('bulan, AVG(persentase) as pct')
                    ->groupBy('bulan')->pluck('pct', 'bulan')->toArray();

                // Lead mingguan: 12 bulan × 4 minggu (rata-rata persentase per minggu)
                $leadWeekly = LeadMeasureRealisasi::whereHas('leadMeasure',
                    fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true))
                    ->where('cabang_id', $this->cabang_id)
                    ->where('tahun', $this->tahun)
                    ->selectRaw('bulan, minggu_ke, AVG(persentase) as pct')
                    ->groupBy('bulan', 'minggu_ke')->get();
                $leadWeeklyMap = [];
                foreach ($leadWeekly as $lw) {
                    $leadWeeklyMap[$lw->bulan][$lw->minggu_ke] = round((float) $lw->pct, 2);
                }
                $leadPctMingguan = [];
                $leadLabelsMingguan = [];
                for ($b = 1; $b <= 12; $b++) {
                    for ($m = 1; $m <= 4; $m++) {
                        $leadPctMingguan[] = round((float) ($leadWeeklyMap[$b][$m] ?? 0), 2);
                        $leadLabelsMingguan[] = 'B'.$b.'-M'.$m;
                    }
                }

                $wigPctBulan = [];
                $wigDeltaBulan = [];
                $leadPctBulan = [];
                $kumulatif = 0;
                for ($b = 1; $b <= 12; $b++) {
                    $delta = (float) ($wigRealBulan[$b] ?? 0);
                    $kumulatif += $delta;
                    $wigPctBulan[] = $range > 0 ? round(($kumulatif / $range) * 100, 2) : 0;
                    $wigDeltaBulan[] = $range > 0 ? round(($delta / $range) * 100, 2) : 0;
                    $leadPctBulan[] = round((float) ($leadAvgBulan[$b] ?? 0), 2);
                }

                // Korelasi Pearson: kenaikan WIG bulanan vs % Aktivitas bulanan
                // Hanya pakai bulan yang sudah lewat (di tahun berjalan) atau seluruh tahun (tahun lampau).
                $batas = ($this->tahun == (int) date('Y')) ? min(12, (int) date('n')) : 12;
                $xs = array_slice($wigDeltaBulan, 0, $batas);
                $ys = array_slice($leadPctBulan, 0, $batas);
                $korelasi = $this->pearson($xs, $ys);

                $sisaHari = $t->tanggal_target ? now()->startOfDay()->diffInDays($t->tanggal_target, false) : null;
                $totalHari = ($t->tanggal_target && $t->wig?->created_at)
                    ? max(1, $t->wig->created_at->startOfDay()->diffInDays($t->tanggal_target))
                    : null;
                $pacePct = $totalHari ? round((($totalHari - max(0, $sisaHari ?? 0)) / $totalHari) * 100, 2) : null;
                $pace = 'unknown';
                if ($pacePct !== null) {
                    $rasio = $pacePct > 0 ? $pct / $pacePct : 1;
                    $pace = $rasio >= 1 ? 'on' : ($rasio >= 0.7 ? 'warn' : 'behind');
                }

                $wigProgress[] = [
                    'wig' => $t->wig,
                    'nilai_awal' => $nilaiAwal,
                    'nilai_sekarang' => $nilaiSekarang,
                    'nilai_target' => $nilaiTarget,
                    'satuan' => $t->satuan,
                    'tanggal_target' => $t->tanggal_target,
                    'sisa_hari' => $sisaHari !== null ? (int) $sisaHari : null,
                    'sisa_target' => max(0, $nilaiTarget - $nilaiSekarang),
                    'pct' => min($pct, 100),
                    'pct_raw' => $pct,
                    'pace_pct' => $pacePct,
                    'pace' => $pace,
                    'wig_pct_bulan' => $wigPctBulan,
                    'wig_delta_bulan' => $wigDeltaBulan,
                    'lead_pct_bulan' => $leadPctBulan,
                    'lead_pct_mingguan' => $leadPctMingguan,
                    'lead_labels_mingguan' => $leadLabelsMingguan,
                    'korelasi' => $korelasi,
                ];
            }
        }

        return view('livewire.dashboard', [
            'cabangs' => $cabangs,
            'wigs' => $wigs,
            'realisasiNow' => $realisasiNow,
            'realisasiPrev' => $realisasiPrev,
            'totalWig' => $wigs->count(),
            'totalLead' => $totalLead,
            'onTrack' => $onTrack,
            'avgPct' => $avgPct,
            'bulanData' => $bulanData,
            'rankingCabang' => $rankingCabang,
            'wigProgress' => $wigProgress,
        ])->layout('layouts.app');
    }
}
