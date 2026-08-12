<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Target WIG per kantor cabang. Satu WIG diisi sekaligus untuk semua cabang
 * dalam satu tabel, lalu disimpan dalam satu permintaan.
 */
class WigTargetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $wigs = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderByDesc('tahun')
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang', 'tahun']);

        $wigId = (int) ($request->query('wig_id') ?: $wigs->first()?->id);
        $wig = $wigs->firstWhere('id', $wigId);

        return Inertia::render('Wig/Target', [
            'wigs' => $wigs,
            'wig' => $wig,
            'baris' => $wig ? $this->barisTarget($user, $wigId) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wig_id' => ['required', 'exists:wigs,id'],
            'baris' => ['required', 'array'],
            'baris.*.cabang_id' => ['required', 'exists:cabangs,id'],
            'baris.*.nilai_awal' => ['nullable', 'numeric'],
            'baris.*.nilai_target' => ['nullable', 'numeric'],
            'baris.*.satuan' => ['nullable', 'string', 'max:50'],
            'baris.*.tanggal_target' => ['nullable', 'date'],
        ]);

        // Cabang di luar wilayah user tidak boleh ikut tersimpan.
        $diizinkan = $this->cabangTerpilih($request->user())->pluck('id');

        foreach ($data['baris'] as $baris) {
            if (! $diizinkan->contains($baris['cabang_id'])) {
                continue;
            }

            WigTarget::updateOrCreate(
                ['wig_id' => $data['wig_id'], 'cabang_id' => $baris['cabang_id']],
                [
                    'nilai_awal' => (float) ($baris['nilai_awal'] ?? 0),
                    'nilai_target' => (float) ($baris['nilai_target'] ?? 0),
                    'satuan' => $baris['satuan'] ?: 'Rp',
                    'tanggal_target' => $baris['tanggal_target'] ?: null,
                ]
            );
        }

        return back()->with('success', 'Target per cabang berhasil disimpan.');
    }

    private function barisTarget(?User $user, int $wigId): array
    {
        $targets = WigTarget::where('wig_id', $wigId)->get()->keyBy('cabang_id');

        return $this->cabangTerpilih($user)
            ->map(fn (Cabang $cabang) => [
                'cabang_id' => $cabang->id,
                'cabang_nama' => $cabang->nama,
                'nilai_awal' => (float) ($targets->get($cabang->id)?->nilai_awal ?? 0),
                'nilai_target' => (float) ($targets->get($cabang->id)?->nilai_target ?? 0),
                'satuan' => $targets->get($cabang->id)?->satuan ?? 'Rp',
                'tanggal_target' => $targets->get($cabang->id)?->tanggal_target?->format('Y-m-d') ?? '',
            ])
            ->values()
            ->all();
    }

    private function cabangTerpilih(?User $user)
    {
        return Cabang::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('nama')
            ->get();
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
