<?php

namespace App\Http\Controllers;

use App\Http\Requests\LagMeasureRequest;
use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\User;
use App\Models\Wig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LagMeasureController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cari = trim((string) $request->query('cari', ''));
        $wigId = $request->query('wig_id');

        // Bidang melekat pada WIG, jadi disaring lewat relasinya.
        $bidang = $request->query('bidang');
        $bidang = in_array($bidang, Wig::BIDANG, true) ? $bidang : null;

        $wigs = Wig::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('kode_wig')
            ->get(['id', 'kode_wig', 'nama_wig', 'bidang']);

        /*
         | Pilihan WIG pada penyaringan menyempit mengikuti bidang, dan baru
         | tersedia setelah bidang ditentukan. Daftar penuh tetap dikirim
         | terpisah untuk dropdown di dialog tambah/edit.
         */
        $wigPilihan = $bidang ? $wigs->where('bidang', $bidang)->values() : collect();

        if ($wigId && ! $wigPilihan->contains('id', (int) $wigId)) {
            $wigId = null;
        }

        $cabangs = Cabang::query()
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->where('wilayah_id', $wilayahId))
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        // Hanya cabang yang boleh diakses user yang diterima sebagai filter.
        $cabangId = $request->query('cabang_id');
        $cabangId = ($cabangId && $cabangs->contains('id', (int) $cabangId)) ? (int) $cabangId : null;

        $lags = LagMeasure::query()
            ->with(['wig:id,kode_wig,nama_wig,bidang', 'cabang:id,kode,nama'])
            // Non-admin hanya melihat lag pada WIG di wilayahnya sendiri.
            ->when($this->wilayahTerbatas($user), fn ($q, $wilayahId) => $q->whereHas('wig',
                fn ($w) => $w->where('wilayah_id', $wilayahId)
            ))
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode_lag', 'like', "%{$cari}%")
                    ->orWhere('nama_lag', 'like', "%{$cari}%")
            ))
            ->when($wigId, fn ($q) => $q->where('wig_id', $wigId))
            ->when($bidang, fn ($q, $b) => $q->whereHas('wig', fn ($w) => $w->where('bidang', $b)))
            ->when($cabangId, fn ($q, $id) => $q->where('cabang_id', $id))
            ->withCount('leadMeasures')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('LagMeasure/Index', [
            'lags' => $lags,
            'wigs' => $wigs,
            'wigPilihan' => $wigPilihan,
            'cabangs' => $cabangs,
            'daftarBidang' => Wig::BIDANG,
            'filter' => [
                'cari' => $cari,
                'wig_id' => $wigId ? (int) $wigId : null,
                'bidang' => $bidang,
                'cabang_id' => $cabangId,
            ],
        ]);
    }

    /**
     * Usulan kode lag untuk cabang + tahun tertentu. Dipanggil sekali saat
     * kombinasi itu dipilih, bukan tiap ketikan.
     */
    public function kodeSaran(Request $request): JsonResponse
    {
        $cabang = Cabang::find($request->query('cabang_id'));
        $tahun = (int) $request->query('tahun', date('Y'));

        if (! $cabang) {
            return response()->json(['kode' => '']);
        }

        return response()->json(['kode' => $this->buatKode($cabang, $tahun)]);
    }

    public function store(LagMeasureRequest $request): RedirectResponse
    {
        LagMeasure::create($request->validated());

        return back()->with('success', 'Lag Measure berhasil ditambahkan.');
    }

    public function update(LagMeasureRequest $request, LagMeasure $lagMeasure): RedirectResponse
    {
        $lagMeasure->update($request->validated());

        return back()->with('success', 'Lag Measure berhasil diperbarui.');
    }

    public function destroy(LagMeasure $lagMeasure): RedirectResponse
    {
        if ($lagMeasure->leadMeasures()->exists()) {
            return back()->with('error', 'Lag Measure tidak dapat dihapus karena masih memiliki Lead Measure.');
        }

        $lagMeasure->delete();

        return back()->with('success', 'Lag Measure berhasil dihapus.');
    }

    /**
     * Wilayah pembatas untuk user non-admin, atau null bila tidak dibatasi.
     */
    private function wilayahTerbatas(?User $user): ?int
    {
        if (! $user || $user->hasRole('admin')) {
            return null;
        }

        return $user->wilayah_id;
    }

    private function buatKode(Cabang $cabang, int $tahun): string
    {
        $dasar = preg_replace('/^KC-?/i', '', $cabang->kode);
        $urutan = LagMeasure::where('cabang_id', $cabang->id)->where('tahun', $tahun)->count() + 1;

        do {
            $kode = $dasar.'-'.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT).'-'.$tahun;
            $urutan++;
        } while (LagMeasure::where('kode_lag', $kode)->exists());

        return $kode;
    }
}
