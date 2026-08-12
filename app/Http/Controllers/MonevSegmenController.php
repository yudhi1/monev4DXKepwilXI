<?php

namespace App\Http\Controllers;

use App\Models\MonevSegmen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MonevSegmenController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('MonevIuran/Segmen', [
            'segmens' => MonevSegmen::withCount('realisasis')
                ->orderBy('urutan')
                ->orderBy('id')
                ->get(),
            'urutanBerikutnya' => (int) (MonevSegmen::max('urutan') ?? 0) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MonevSegmen::create($this->validasi($request));

        return back()->with('success', 'Segmen berhasil ditambahkan.');
    }

    public function update(Request $request, MonevSegmen $segmen): RedirectResponse
    {
        $segmen->update($this->validasi($request, $segmen));

        return back()->with('success', 'Segmen berhasil diperbarui.');
    }

    public function destroy(MonevSegmen $segmen): RedirectResponse
    {
        if ($segmen->realisasis()->exists()) {
            return back()->with('error', 'Segmen tidak dapat dihapus karena sudah memiliki data realisasi.');
        }

        $segmen->delete();

        return back()->with('success', 'Segmen berhasil dihapus.');
    }

    private function validasi(Request $request, ?MonevSegmen $segmen = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('monev_segmens', 'nama')->ignore($segmen?->id)],
            'urutan' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [], ['nama' => 'nama segmen']);
    }
}
