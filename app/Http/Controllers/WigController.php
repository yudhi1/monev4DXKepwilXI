<?php

namespace App\Http\Controllers;

use App\Http\Requests\WigRequest;
use App\Models\User;
use App\Models\Wig;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WigController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cari = trim((string) $request->query('cari', ''));
        $tahun = $request->query('tahun');

        $bidang = $request->query('bidang');
        $bidang = in_array($bidang, Wig::BIDANG, true) ? $bidang : null;

        $wigs = Wig::query()
            ->with('wilayah:id,nama')
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode_wig', 'like', "%{$cari}%")
                    ->orWhere('nama_wig', 'like', "%{$cari}%")
            ))
            ->when($tahun, fn ($q) => $q->where('tahun', $tahun))
            ->when($bidang, fn ($q, $b) => $q->where('bidang', $b))
            ->withCount('lagMeasures')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Wig/Index', [
            'wigs' => $wigs,
            'wilayahs' => Wilayah::orderBy('nama')->get(['id', 'nama']),
            'daftarBidang' => Wig::BIDANG,
            'daftarSifat' => config('wig.sifat'),
            'daftarArah' => config('wig.arah'),
            'bawaanSifat' => config('wig.bawaan'),
            'daftarTahun' => Wig::query()
                ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
                ->distinct()
                ->orderByDesc('tahun')
                ->pluck('tahun'),
            'filter' => ['cari' => $cari, 'tahun' => $tahun, 'bidang' => $bidang],
            'wilayahBawaan' => $user?->wilayah_id,
        ]);
    }

    public function store(WigRequest $request): RedirectResponse
    {
        Wig::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'WIG berhasil ditambahkan.');
    }

    public function update(WigRequest $request, Wig $wig): RedirectResponse
    {
        $wig->update($request->validated());

        return back()->with('success', 'WIG berhasil diperbarui.');
    }

    public function destroy(Wig $wig): RedirectResponse
    {
        if ($wig->lagMeasures()->exists()) {
            return back()->with('error', 'WIG tidak dapat dihapus karena masih memiliki Lag Measure.');
        }

        $wig->delete();

        return back()->with('success', 'WIG berhasil dihapus.');
    }

    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }
}
