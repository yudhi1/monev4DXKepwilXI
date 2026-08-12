<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard 4DX per kantor cabang.
 *
 * Perhitungannya dipindahkan apa adanya dari App\Livewire\Dashboard supaya
 * angka yang tampil identik dengan versi lama. Yang berubah hanya cara
 * penyajiannya: data dikirim sebagai props Inertia, bukan dirender Blade.
 */
class DashboardCabangController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = (int) $request->query('bulan', date('n'));
        $minggu = (int) $request->query('minggu', $this->mingguBerjalan());
        $minggu = min(max($minggu, 1), 4);

        $cabangs = $this->cabangTerpilih($user);
        $cabangId = $this->tentukanCabang($request, $user, $cabangs);

        $wigs = $this->ambilWig($user, $tahun, $cabangId);

        [$pTahun, $pBulan, $pMinggu] = $this->periodeSebelumnya($tahun, $bulan, $minggu);

        $realisasiNow = LeadMeasureRealisasi::where([
            'tahun' => $tahun, 'bulan' => $bulan, 'minggu_ke' => $minggu, 'cabang_id' => $cabangId,
        ])->get()->keyBy('lead_measure_id');

        $realisasiPrev = LeadMeasureRealisasi::where([
            'tahun' => $pTahun, 'bulan' => $pBulan, 'minggu_ke' => $pMinggu, 'cabang_id' => $cabangId,
        ])->get()->keyBy('lead_measure_id');

        [$totalLead, $onTrack, $avgPct, $pohonWig] = $this->rangkumWig($wigs, $realisasiNow, $realisasiPrev);

        return Inertia::render('Dashboard/Cabang', [
            'cabangs' => $cabangs,
            'filter' => [
                'cabang_id' => $cabangId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'minggu' => $minggu,
            ],
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
            'ringkasan' => [
                'total_wig' => $wigs->count(),
                'total_lead' => $totalLead,
                'on_track' => $onTrack,
                'avg_pct' => $avgPct,
            ],
            'bulanData' => $this->capaianPerBulan($tahun, $cabangId),
            'rankingCabang' => $this->rankingCabang($user, $tahun, $cabangs),
            'wigProgress' => $this->progresWig($cabangId, $tahun, $bulan),
            'pohonWig' => $pohonWig,
        ]);
    }

    private function mingguBerjalan(): int
    {
        return min(max((int) ceil(date('j') / 7), 1), 4);
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

    private function cabangTerpilih(?User $user)
    {
        return Cabang::with('wilayah:id,nama')
            ->when(
                $user?->hasRole('kantor_cabang') && $user->cabang_id,
                fn ($q) => $q->where('id', $user->cabang_id)
            )
            ->when(
                $user?->hasRole('kedeputian_wilayah') && $user->wilayah_id,
                fn ($q) => $q->where('wilayah_id', $user->wilayah_id)
            )
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'wilayah_id']);
    }

    private function tentukanCabang(Request $request, ?User $user, $cabangs): ?int
    {
        // Kantor cabang selalu terkunci pada cabangnya sendiri.
        if ($user?->hasRole('kantor_cabang') && $user->cabang_id) {
            return $user->cabang_id;
        }

        $diminta = $request->query('cabang_id');

        if ($diminta && $cabangs->contains('id', (int) $diminta)) {
            return (int) $diminta;
        }

        return $cabangs->first()?->id;
    }

    private function ambilWig(?User $user, int $tahun, ?int $cabangId)
    {
        return Wig::with([
            'lagMeasures' => fn ($q) => $cabangId
                ? $q->where(fn ($w) => $w->whereNull('cabang_id')->orWhere('cabang_id', $cabangId))
                : $q,
            'lagMeasures.leadMeasures' => function ($q) use ($cabangId) {
                $q->where('is_active', true);

                if ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                }
            },
        ])
            ->where('tahun', $tahun)
            ->when(
                $user && ! $user->hasRole('admin') && $user->wilayah_id,
                fn ($q) => $q->where('wilayah_id', $user->wilayah_id)
            )
            ->orderBy('kode_wig')
            ->get();
    }

    /**
     * Menghitung ringkasan sekaligus menyusun pohon WIG → Lag → Lead
     * lengkap dengan status dan tren tiap Lead.
     */
    private function rangkumWig($wigs, $realisasiNow, $realisasiPrev): array
    {
        $totalLead = 0;
        $onTrack = 0;
        $sumPct = 0;
        $pohon = [];

        foreach ($wigs as $wig) {
            $daftarLag = [];

            foreach ($wig->lagMeasures as $lag) {
                $daftarLead = [];

                foreach ($lag->leadMeasures as $lead) {
                    $rNow = $realisasiNow->get($lead->id);
                    $rPrev = $realisasiPrev->get($lead->id);
                    $pctNow = (float) ($rNow?->persentase ?? 0);
                    $pctPrev = (float) ($rPrev?->persentase ?? 0);

                    $totalLead++;
                    $sumPct += $pctNow;

                    if ($pctNow >= 100) {
                        $onTrack++;
                    }

                    $selisih = $pctNow - $pctPrev;

                    $daftarLead[] = [
                        'id' => $lead->id,
                        'kode_lead' => $lead->kode_lead,
                        'nama_lead' => $lead->nama_lead,
                        'target' => $rNow?->target,
                        'realisasi' => $rNow?->realisasi,
                        'persentase' => $pctNow,
                        'persentase_sebelumnya' => $pctPrev,
                        'status' => $pctNow >= 100 ? 'on' : ($pctNow >= 70 ? 'warn' : 'behind'),
                        'tren' => abs($selisih) < 1 ? 'stabil' : ($selisih > 0 ? 'naik' : 'turun'),
                        'catatan' => $rNow?->catatan,
                    ];
                }

                $daftarLag[] = [
                    'id' => $lag->id,
                    'kode_lag' => $lag->kode_lag,
                    'nama_lag' => $lag->nama_lag,
                    'target_tahunan' => $lag->target_tahunan,
                    'satuan' => $lag->satuan,
                    'leads' => $daftarLead,
                ];
            }

            $pohon[] = [
                'id' => $wig->id,
                'kode_wig' => $wig->kode_wig,
                'nama_wig' => $wig->nama_wig,
                'bidang' => $wig->bidang,
                'lags' => $daftarLag,
            ];
        }

        $avgPct = $totalLead > 0 ? round($sumPct / $totalLead, 2) : 0;

        return [$totalLead, $onTrack, $avgPct, $pohon];
    }

    private function capaianPerBulan(int $tahun, ?int $cabangId): array
    {
        $perBulan = LeadMeasureRealisasi::where('tahun', $tahun)
            ->when($cabangId, fn ($q) => $q->where('cabang_id', $cabangId))
            ->selectRaw('bulan, AVG(persentase) as pct')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('pct', 'bulan')
            ->toArray();

        $hasil = [];

        for ($i = 1; $i <= 12; $i++) {
            $hasil[] = round((float) ($perBulan[$i] ?? 0), 2);
        }

        return $hasil;
    }

    private function rankingCabang(?User $user, int $tahun, $cabangs)
    {
        return LeadMeasureRealisasi::where('tahun', $tahun)
            ->selectRaw('cabang_id, AVG(persentase) as pct')
            ->groupBy('cabang_id')
            ->orderByDesc('pct')
            ->with('cabang:id,nama')
            ->when(
                $user?->hasRole('kedeputian_wilayah') && $user->wilayah_id,
                fn ($q) => $q->whereIn('cabang_id', $cabangs->pluck('id'))
            )
            ->limit(10)
            ->get()
            ->map(fn ($baris) => [
                'cabang_id' => $baris->cabang_id,
                'nama' => $baris->cabang?->nama ?? '—',
                'pct' => round((float) $baris->pct, 2),
            ]);
    }

    /**
     * Progres tiap WIG untuk cabang terpilih: capaian kumulatif bulanan,
     * capaian aktivitas (Lead) bulanan & mingguan, serta korelasi keduanya.
     */
    private function progresWig(?int $cabangId, int $tahun, int $bulan): array
    {
        if (! $cabangId) {
            return [];
        }

        $targets = WigTarget::with('wig')
            ->where('cabang_id', $cabangId)
            ->whereHas('wig', fn ($q) => $q->where('tahun', $tahun))
            ->get();

        $hasil = [];

        foreach ($targets as $t) {
            $nilaiAwal = (float) $t->nilai_awal;
            $nilaiTarget = (float) $t->nilai_target;
            $rentang = $nilaiTarget - $nilaiAwal;

            $kontribusi = (float) LeadMeasureRealisasi::whereHas(
                'leadMeasure',
                fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true)
            )->where('cabang_id', $cabangId)->sum('realisasi');

            $nilaiSekarang = $nilaiAwal + $kontribusi;
            $pct = $rentang > 0 ? max(0, round((($nilaiSekarang - $nilaiAwal) / $rentang) * 100, 2)) : 0;

            $wigRealBulan = WigRealisasi::where('wig_id', $t->wig_id)
                ->where('cabang_id', $cabangId)
                ->where('tahun', $tahun)
                ->pluck('nilai', 'bulan')
                ->toArray();

            $leadAvgBulan = LeadMeasureRealisasi::whereHas(
                'leadMeasure',
                fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true)
            )
                ->where('cabang_id', $cabangId)
                ->where('tahun', $tahun)
                ->selectRaw('bulan, AVG(persentase) as pct')
                ->groupBy('bulan')
                ->pluck('pct', 'bulan')
                ->toArray();

            $leadMingguan = LeadMeasureRealisasi::whereHas(
                'leadMeasure',
                fn ($q) => $q->where('wig_id', $t->wig_id)->where('is_active', true)
            )
                ->where('cabang_id', $cabangId)
                ->where('tahun', $tahun)
                ->selectRaw('bulan, minggu_ke, AVG(persentase) as pct')
                ->groupBy('bulan', 'minggu_ke')
                ->get();

            $petaMingguan = [];

            foreach ($leadMingguan as $lm) {
                $petaMingguan[$lm->bulan][$lm->minggu_ke] = round((float) $lm->pct, 2);
            }

            $leadPctMingguan = [];
            $leadLabelMingguan = [];

            for ($b = 1; $b <= 12; $b++) {
                for ($m = 1; $m <= 4; $m++) {
                    $leadPctMingguan[] = round((float) ($petaMingguan[$b][$m] ?? 0), 2);
                    $leadLabelMingguan[] = 'B'.$b.'-M'.$m;
                }
            }

            $wigPctBulan = [];
            $wigDeltaBulan = [];
            $leadPctBulan = [];
            $kumulatif = 0;

            for ($b = 1; $b <= 12; $b++) {
                $delta = (float) ($wigRealBulan[$b] ?? 0);
                $kumulatif += $delta;
                $wigPctBulan[] = $rentang > 0 ? round(($kumulatif / $rentang) * 100, 2) : 0;
                $wigDeltaBulan[] = $rentang > 0 ? round(($delta / $rentang) * 100, 2) : 0;
                $leadPctBulan[] = round((float) ($leadAvgBulan[$b] ?? 0), 2);
            }

            // Korelasi hanya memakai bulan yang sudah lewat pada tahun berjalan.
            $batas = $tahun === (int) date('Y') ? min(12, (int) date('n')) : 12;
            $korelasi = $this->pearson(
                array_slice($wigDeltaBulan, 0, $batas),
                array_slice($leadPctBulan, 0, $batas)
            );

            $sisaHari = $t->tanggal_target
                ? (int) now()->startOfDay()->diffInDays($t->tanggal_target, false)
                : null;

            $totalHari = ($t->tanggal_target && $t->wig?->created_at)
                ? max(1, $t->wig->created_at->startOfDay()->diffInDays($t->tanggal_target))
                : null;

            $pacePct = $totalHari
                ? round((($totalHari - max(0, $sisaHari ?? 0)) / $totalHari) * 100, 2)
                : null;

            $pace = 'unknown';

            if ($pacePct !== null) {
                $rasio = $pacePct > 0 ? $pct / $pacePct : 1;
                $pace = $rasio >= 1 ? 'on' : ($rasio >= 0.7 ? 'warn' : 'behind');
            }

            $hasil[] = [
                'wig_id' => $t->wig_id,
                'kode_wig' => $t->wig?->kode_wig,
                'nama_wig' => $t->wig?->nama_wig,
                'bidang' => $t->wig?->bidang,
                'nilai_awal' => $nilaiAwal,
                'nilai_sekarang' => $nilaiSekarang,
                'nilai_target' => $nilaiTarget,
                'satuan' => $t->satuan,
                'tanggal_target' => $t->tanggal_target,
                'sisa_hari' => $sisaHari,
                'pct' => min($pct, 100),
                'pct_raw' => $pct,
                'pace' => $pace,
                'pace_pct' => $pacePct,
                'wig_pct_bulan' => $wigPctBulan,
                'wig_delta_bulan' => $wigDeltaBulan,
                'lead_pct_bulan' => $leadPctBulan,
                'lead_pct_mingguan' => $leadPctMingguan,
                'lead_label_mingguan' => $leadLabelMingguan,
                'wig_pct_kini' => $wigPctBulan[$bulan - 1] ?? 0,
                'lead_pct_kini' => $leadPctBulan[$bulan - 1] ?? 0,
                'korelasi' => $korelasi,
            ];
        }

        return $hasil;
    }

    /**
     * Korelasi Pearson antara kenaikan WIG bulanan dan capaian aktivitas.
     * Bulan yang sama sekali belum terisi di ujung deret dibuang agar tidak bias.
     */
    private function pearson(array $x, array $y): array
    {
        $n = min(count($x), count($y));

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

        $r = round($num / sqrt($dx * $dy), 2);

        if ($r >= 0.6) {
            return ['r' => $r, 'label' => 'Lead efektif', 'level' => 'kuat'];
        }

        if ($r >= 0.3) {
            return ['r' => $r, 'label' => 'Lead cukup', 'level' => 'sedang'];
        }

        if ($r >= -0.3) {
            return ['r' => $r, 'label' => 'Lead lemah', 'level' => 'lemah'];
        }

        return ['r' => $r, 'label' => 'Berlawanan arah', 'level' => 'lemah'];
    }
}
