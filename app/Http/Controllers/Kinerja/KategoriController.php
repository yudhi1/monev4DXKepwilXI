<?php

namespace App\Http\Controllers\Kinerja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kinerja\KategoriRequest;
use App\Models\Kinerja\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Kelola kategori capaian kinerja — lapis pertama sebelum indikator. */
class KategoriController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));

        $kategoris = Kategori::query()
            ->withCount(['indikators'])
            ->when($cari !== '', fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            ->urutTampil()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Kategori $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'keterangan' => $k->keterangan,
                'urutan' => $k->urutan,
                'is_active' => $k->is_active,
                'jumlahIndikator' => $k->indikators_count,
            ]);

        return Inertia::render('Kinerja/Kategori', [
            'kategoris' => $kategoris,
            'filter' => ['cari' => $cari],
        ]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        Kategori::create($request->validated());

        return back()->with('success', 'Kategori capaian berhasil ditambahkan.');
    }

    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return back()->with('success', 'Kategori capaian berhasil diperbarui.');
    }

    /**
     * Kategori yang masih punya indikator tidak boleh dihapus.
     *
     * Relasinya cascade, jadi menghapusnya akan ikut melenyapkan indikator
     * beserta seluruh berkas capaian di bawahnya tanpa peringatan.
     */
    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->indikators()->exists()) {
            return back()->with('error', 'Kategori ini masih memiliki indikator. Hapus atau pindahkan indikatornya dulu.');
        }

        $kategori->delete();

        return back()->with('success', 'Kategori capaian berhasil dihapus.');
    }
}
