<?php

namespace App\Http\Controllers;

use App\Http\Requests\WilayahRequest;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WilayahController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));

        $wilayahs = Wilayah::query()
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
            ))
            ->withCount('cabangs')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Wilayah/Index', [
            'wilayahs' => $wilayahs,
            'filter' => ['cari' => $cari],
        ]);
    }

    public function store(WilayahRequest $request): RedirectResponse
    {
        Wilayah::create($request->validated());

        return back()->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function update(WilayahRequest $request, Wilayah $wilayah): RedirectResponse
    {
        $wilayah->update($request->validated());

        return back()->with('success', 'Wilayah berhasil diperbarui.');
    }

    public function destroy(Wilayah $wilayah): RedirectResponse
    {
        if ($wilayah->cabangs()->exists()) {
            return back()->with('error', 'Wilayah tidak dapat dihapus karena masih memiliki kantor cabang.');
        }

        $wilayah->delete();

        return back()->with('success', 'Wilayah berhasil dihapus.');
    }
}
