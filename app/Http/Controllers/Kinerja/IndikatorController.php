<?php

namespace App\Http\Controllers\Kinerja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kinerja\IndikatorRequest;
use App\Models\Kinerja\Indikator;
use App\Models\Kinerja\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Kelola indikator capaian kinerja; selalu di bawah satu kategori. */
class IndikatorController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $kategori = $request->query('kategori');

        $indikators = Indikator::query()
            ->with('kategori:id,nama')
            ->withCount('files')
            ->when($cari !== '', fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            ->when($kategori, fn ($q) => $q->where('kinerja_kategori_id', $kategori))
            ->urutTampil()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Indikator $i) => [
                'id' => $i->id,
                'nama' => $i->nama,
                'keterangan' => $i->keterangan,
                'urutan' => $i->urutan,
                'is_active' => $i->is_active,
                'kinerja_kategori_id' => $i->kinerja_kategori_id,
                'kategori' => $i->kategori?->nama ?? '-',
                'jumlahFile' => $i->files_count,
            ]);

        return Inertia::render('Kinerja/Indikator', [
            'indikators' => $indikators,
            'kategoris' => Kategori::aktif()->urutTampil()->get(['id', 'nama']),
            'filter' => ['cari' => $cari, 'kategori' => $kategori],
        ]);
    }

    public function store(IndikatorRequest $request): RedirectResponse
    {
        Indikator::create($request->validated());

        return back()->with('success', 'Indikator berhasil ditambahkan.');
    }

    public function update(IndikatorRequest $request, Indikator $indikator): RedirectResponse
    {
        $indikator->update($request->validated());

        return back()->with('success', 'Indikator berhasil diperbarui.');
    }

    public function destroy(Indikator $indikator): RedirectResponse
    {
        if ($indikator->files()->exists()) {
            return back()->with('error', 'Indikator ini masih memiliki file capaian. Hapus filenya dulu.');
        }

        $indikator->delete();

        return back()->with('success', 'Indikator berhasil dihapus.');
    }
}
