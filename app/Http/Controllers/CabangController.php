<?php

namespace App\Http\Controllers;

use App\Http\Requests\CabangRequest;
use App\Models\Cabang;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CabangController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $wilayahId = $request->query('wilayah_id');

        $cabangs = Cabang::query()
            ->with('wilayah:id,nama')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
            ))
            ->when($wilayahId, fn ($q) => $q->where('wilayah_id', $wilayahId))
            ->withCount('users')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Cabang/Index', [
            'cabangs' => $cabangs,
            'wilayahs' => Wilayah::orderBy('nama')->get(['id', 'kode', 'nama']),
            'filter' => ['cari' => $cari, 'wilayah_id' => $wilayahId],
        ]);
    }

    public function store(CabangRequest $request): RedirectResponse
    {
        Cabang::create($request->validated());

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function update(CabangRequest $request, Cabang $cabang): RedirectResponse
    {
        $cabang->update($request->validated());

        return back()->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Cabang $cabang): RedirectResponse
    {
        if ($cabang->users()->exists()) {
            return back()->with('error', 'Cabang tidak dapat dihapus karena masih memiliki user.');
        }

        $cabang->delete();

        return back()->with('success', 'Cabang berhasil dihapus.');
    }
}
