<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Input realisasi WIG bulanan (12 baris sekaligus) untuk satu kombinasi
 * WIG + cabang + tahun.
 */
class WigRealisasiController extends Controller
{
    private const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tahun = (int) $request->query('tahun', date('Y'));

        $wigs = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderByDesc('tahun')
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang', 'tahun']);

        $cabangs = $this->cabangTerpilih($user);

        $wigId = (int) ($request->query('wig_id') ?: $wigs->first()?->id);
        $cabangId = $this->tentukanCabang($request, $user, $cabangs);

        $baris = $this->barisBulanan($wigId, $cabangId, $tahun);

        $target = ($wigId && $cabangId)
            ? WigTarget::where('wig_id', $wigId)->where('cabang_id', $cabangId)->first()
            : null;

        return Inertia::render('Wig/Realisasi', [
            'wigs' => $wigs,
            'cabangs' => $cabangs,
            'baris' => $baris,
            'namaBulan' => self::BULAN,
            'target' => $target,
            'ringkasan' => $this->ringkasan($baris, $target),
            'filter' => ['wig_id' => $wigId ?: null, 'cabang_id' => $cabangId, 'tahun' => $tahun],
            'terkunciCabang' => (bool) $user?->hasRole('kantor_cabang'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wig_id' => ['required', 'exists:wigs,id'],
            'cabang_id' => ['required', 'exists:cabangs,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'baris' => ['required', 'array', 'size:12'],
            'baris.*.bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'baris.*.nilai' => ['nullable', 'numeric'],
            'baris.*.catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        // User kantor cabang hanya boleh mengisi cabangnya sendiri.
        if ($user->hasRole('kantor_cabang') && $user->cabang_id && (int) $data['cabang_id'] !== $user->cabang_id) {
            return back()->with('error', 'Tidak diizinkan input untuk cabang lain.');
        }

        foreach ($data['baris'] as $baris) {
            WigRealisasi::updateOrCreate(
                [
                    'wig_id' => $data['wig_id'],
                    'cabang_id' => $data['cabang_id'],
                    'tahun' => $data['tahun'],
                    'bulan' => $baris['bulan'],
                ],
                [
                    'nilai' => (float) ($baris['nilai'] ?? 0),
                    'catatan' => $baris['catatan'] ?? null,
                    'created_by' => $user->id,
                ]
            );
        }

        return back()->with('success', 'Realisasi WIG bulanan berhasil disimpan.');
    }

    private function barisBulanan(?int $wigId, ?int $cabangId, int $tahun): array
    {
        $tersimpan = ($wigId && $cabangId)
            ? WigRealisasi::where('wig_id', $wigId)
                ->where('cabang_id', $cabangId)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy('bulan')
            : collect();

        $baris = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $baris[] = [
                'bulan' => $bulan,
                'nilai' => (float) ($tersimpan->get($bulan)?->nilai ?? 0),
                'catatan' => (string) ($tersimpan->get($bulan)?->catatan ?? ''),
            ];
        }

        return $baris;
    }

    /**
     * Progres memakai rentang awal→target. Bila tidak ada rentang naik
     * (target ≤ awal), diukur langsung terhadap target.
     */
    private function ringkasan(array $baris, ?WigTarget $target): array
    {
        $total = array_sum(array_column($baris, 'nilai'));
        $nilaiAwal = (float) ($target?->nilai_awal ?? 0);
        $nilaiTarget = (float) ($target?->nilai_target ?? 0);
        $rentang = $nilaiTarget - $nilaiAwal;

        if ($rentang > 0) {
            $progres = round(($total / $rentang) * 100, 2);
        } elseif ($nilaiTarget > 0) {
            $progres = round(($total / $nilaiTarget) * 100, 2);
        } else {
            $progres = 0;
        }

        return [
            'total_realisasi' => $total,
            'progres' => $progres,
            'nilai_sekarang' => $nilaiAwal + $total,
        ];
    }

    private function tentukanCabang(Request $request, ?User $user, $cabangs): ?int
    {
        if ($user?->hasRole('kantor_cabang') && $user->cabang_id) {
            return $user->cabang_id;
        }

        $diminta = $request->query('cabang_id');

        if ($diminta && $cabangs->contains('id', (int) $diminta)) {
            return (int) $diminta;
        }

        return $cabangs->first()?->id;
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
