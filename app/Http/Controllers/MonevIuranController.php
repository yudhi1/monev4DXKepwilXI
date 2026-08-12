<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\MonevIuranRealisasi;
use App\Models\MonevSegmen;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Input realisasi iuran per segmen, per cabang, per bulan.
 *
 * Dua mode:
 * - Satu cabang  → baris bisa diedit, kecuali periodenya sudah Final.
 * - Konsolidasi  → total seluruh cabang yang boleh diakses; lihat-saja.
 */
class MonevIuranController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));
        $bulan = min(max((int) $request->query('bulan', date('n')), 1), 12);

        $cabangs = $this->cabangTerpilih($user);
        [$pilihan, $cabangId, $konsolidasi] = $this->tentukanPilihan($request, $user, $cabangs);

        $terkunci = $cabangId
            ? MonevIuranRealisasi::where('cabang_id', $cabangId)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->where('status_periode', 'final')
                ->exists()
            : false;

        return Inertia::render('MonevIuran/Input', [
            'cabangs' => $cabangs,
            'baris' => $konsolidasi
                ? $this->barisKonsolidasi($cabangs, $tahun, $bulan)
                : $this->barisCabang($cabangId, $tahun, $bulan),
            'namaBulan' => array_values(MonevIuranRealisasi::BULAN),
            'bulanLalu' => MonevIuranRealisasi::BULAN[$bulan === 1 ? 12 : $bulan - 1],
            'filter' => [
                'pilihan' => $pilihan,
                'cabang_id' => $cabangId,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
            'konsolidasi' => $konsolidasi,
            'periodeTerkunci' => $terkunci,
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
            'bisaBukaKunci' => (bool) $user?->hasRole('admin'),
        ]);
    }

    /** Menyimpan satu baris segmen. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'segmen_id' => ['required', 'exists:monev_segmens,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'realisasi_sd_bulan_lalu' => ['required', 'numeric', 'min:0'],
            'mg1' => ['required', 'numeric', 'min:0'],
            'mg2' => ['required', 'numeric', 'min:0'],
            'mg3' => ['required', 'numeric', 'min:0'],
            'mg4' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'realisasi_sd_bulan_lalu' => 'realisasi s.d. bulan lalu',
            'mg1' => 'minggu 1',
            'mg2' => 'minggu 2',
            'mg3' => 'minggu 3',
            'mg4' => 'minggu 4',
        ]);

        $user = $request->user();

        if ($user->hasRole('kantor_cabang') && $user->cabang_id && (int) $data['cabang_id'] !== $user->cabang_id) {
            return back()->with('error', 'Tidak diizinkan input untuk cabang lain.');
        }

        if ($this->periodeFinal($data['cabang_id'], $data['tahun'], $data['bulan'])) {
            return back()->with('error', 'Periode sudah Final. Data tidak bisa diubah.');
        }

        MonevIuranRealisasi::updateOrCreate(
            [
                'cabang_id' => $data['cabang_id'],
                'segmen_id' => $data['segmen_id'],
                'tahun' => $data['tahun'],
                'bulan' => $data['bulan'],
            ],
            [
                'mg1' => $data['mg1'],
                'mg2' => $data['mg2'],
                'mg3' => $data['mg3'],
                'mg4' => $data['mg4'],
                'realisasi_sd_bulan_lalu' => $data['realisasi_sd_bulan_lalu'],
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => $user->id,
            ]
        );

        return back()->with('success', 'Realisasi segmen berhasil disimpan.');
    }

    public function kunci(Request $request): RedirectResponse
    {
        $data = $this->validasiPeriode($request);
        $user = $request->user();

        if ($user->hasRole('kantor_cabang') && $user->cabang_id && (int) $data['cabang_id'] !== $user->cabang_id) {
            return back()->with('error', 'Tidak diizinkan mengunci periode cabang lain.');
        }

        $query = MonevIuranRealisasi::where('cabang_id', $data['cabang_id'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan']);

        if (! $query->exists()) {
            return back()->with('error', 'Belum ada data untuk dikunci.');
        }

        $query->update([
            'status_periode' => 'final',
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);

        return back()->with('success', 'Periode terkunci (Final). Data tidak bisa diubah lagi.');
    }

    public function bukaKunci(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            return back()->with('error', 'Hanya admin yang bisa membuka kunci periode.');
        }

        $data = $this->validasiPeriode($request);

        MonevIuranRealisasi::where('cabang_id', $data['cabang_id'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->update([
                'status_periode' => 'draft',
                'locked_at' => null,
                'locked_by' => null,
            ]);

        return back()->with('success', 'Periode dibuka kembali (Draft).');
    }

    private function validasiPeriode(Request $request): array
    {
        return $request->validate([
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
        ]);
    }

    private function periodeFinal(int $cabangId, int $tahun, int $bulan): bool
    {
        return MonevIuranRealisasi::where('cabang_id', $cabangId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status_periode', 'final')
            ->exists();
    }

    private function segmenAktif()
    {
        return MonevSegmen::where('is_active', true)->orderBy('urutan')->orderBy('id')->get();
    }

    private function barisCabang(?int $cabangId, int $tahun, int $bulan): array
    {
        if (! $cabangId) {
            return [];
        }

        $tersimpan = MonevIuranRealisasi::where('cabang_id', $cabangId)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('segmen_id');

        return $this->segmenAktif()->map(function (MonevSegmen $segmen) use ($tersimpan) {
            $r = $tersimpan->get($segmen->id);

            return [
                'segmen_id' => $segmen->id,
                'nama' => $segmen->nama,
                'mg1' => (float) ($r?->mg1 ?? 0),
                'mg2' => (float) ($r?->mg2 ?? 0),
                'mg3' => (float) ($r?->mg3 ?? 0),
                'mg4' => (float) ($r?->mg4 ?? 0),
                'realisasi_sd_bulan_lalu' => (float) ($r?->realisasi_sd_bulan_lalu ?? 0),
                'keterangan' => (string) ($r?->keterangan ?? ''),
                'terkunci' => $r?->status_periode === 'final',
            ];
        })->all();
    }

    private function barisKonsolidasi($cabangs, int $tahun, int $bulan): array
    {
        $cabangIds = $cabangs->pluck('id');

        $total = MonevIuranRealisasi::where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('cabang_id', $cabangIds)
            ->selectRaw('segmen_id, SUM(mg1) m1, SUM(mg2) m2, SUM(mg3) m3, SUM(mg4) m4, SUM(realisasi_sd_bulan_lalu) sdl')
            ->groupBy('segmen_id')
            ->get()
            ->keyBy('segmen_id');

        return $this->segmenAktif()->map(function (MonevSegmen $segmen) use ($total) {
            $agg = $total->get($segmen->id);

            return [
                'segmen_id' => $segmen->id,
                'nama' => $segmen->nama,
                'mg1' => (float) ($agg->m1 ?? 0),
                'mg2' => (float) ($agg->m2 ?? 0),
                'mg3' => (float) ($agg->m3 ?? 0),
                'mg4' => (float) ($agg->m4 ?? 0),
                'realisasi_sd_bulan_lalu' => (float) ($agg->sdl ?? 0),
                'keterangan' => '',
                'terkunci' => true,
            ];
        })->all();
    }

    /**
     * @return array{0: string, 1: int|null, 2: bool} [pilihan, cabang_id, konsolidasi]
     */
    private function tentukanPilihan(Request $request, ?User $user, $cabangs): array
    {
        if ($user?->hasRole('kantor_cabang') && $user->cabang_id) {
            return [(string) $user->cabang_id, $user->cabang_id, false];
        }

        $pilihan = (string) $request->query('pilihan', '');

        if ($pilihan === 'konsolidasi') {
            return ['konsolidasi', null, true];
        }

        if ($pilihan !== '' && $cabangs->contains('id', (int) $pilihan)) {
            return [$pilihan, (int) $pilihan, false];
        }

        return ['', null, false];
    }

    private function cabangTerpilih(?User $user)
    {
        return Cabang::query()
            ->when(
                $user?->hasRole('kantor_cabang') && $user->cabang_id,
                fn ($q) => $q->where('id', $user->cabang_id),
                fn ($q) => $q->when($this->wilayahTerbatas($user), fn ($sub, $wilayahId) => $sub->where('wilayah_id', $wilayahId))
            )
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
