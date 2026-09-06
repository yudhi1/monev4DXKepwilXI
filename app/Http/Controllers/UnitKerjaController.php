<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitKerjaRequest;
use App\Models\Cabang;
use App\Models\Pm\Project;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola bidang/unit kerja.
 *
 * Sebelumnya isi tabel ini hanya bisa diubah lewat seeder, sehingga bidang
 * baru atau perubahan nama tidak mungkin dilakukan tanpa menyentuh kode.
 */
class UnitKerjaController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $tingkat = (string) $request->query('tingkat', '');
        $cabang = $request->query('cabang');

        $units = UnitKerja::query()
            ->with('cabang:id,nama')
            ->withCount('users')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('kode', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
            ))
            ->when($tingkat !== '', fn ($q) => $q->where('tingkat', $tingkat))
            ->when($cabang, fn ($q) => $q->where('cabang_id', $cabang))
            ->urutTampil()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (UnitKerja $u) => [
                'id' => $u->id,
                'kode' => $u->kode,
                'nama' => $u->nama,
                'tingkat' => $u->tingkat,
                'cabang_id' => $u->cabang_id,
                'induk' => $u->tingkat === 'cabang' ? ($u->cabang?->nama ?? '-') : 'Kedeputian Wilayah',
                'urutan' => $u->urutan,
                'is_active' => (bool) $u->is_active,
                'jumlahPegawai' => $u->users_count,
            ]);

        return Inertia::render('UnitKerja/Index', [
            'units' => $units,
            'cabangs' => Cabang::where('kode', '!=', 'INTERN')->orderBy('nama')->get(['id', 'nama']),
            'filter' => ['cari' => $cari, 'tingkat' => $tingkat, 'cabang' => $cabang],
        ]);
    }

    public function store(UnitKerjaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        UnitKerja::create([
            ...$data,
            'cabang_id' => $data['tingkat'] === 'cabang' ? $data['cabang_id'] : null,
            // Wilayah diturunkan dari cabangnya, atau wilayah pertama untuk bidang pusat.
            'wilayah_id' => $data['tingkat'] === 'cabang'
                ? Cabang::whereKey($data['cabang_id'])->value('wilayah_id')
                : Wilayah::value('id'),
        ]);

        return back()->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function update(UnitKerjaRequest $request, UnitKerja $unitKerja): RedirectResponse
    {
        $data = $request->validated();

        $unitKerja->update([
            ...$data,
            'cabang_id' => $data['tingkat'] === 'cabang' ? $data['cabang_id'] : null,
            'wilayah_id' => $data['tingkat'] === 'cabang'
                ? Cabang::whereKey($data['cabang_id'])->value('wilayah_id')
                : $unitKerja->wilayah_id,
        ]);

        return back()->with('success', 'Bidang berhasil diperbarui.');
    }

    /**
     * Bidang yang masih dipakai tidak boleh dihapus.
     *
     * Menghapusnya akan melepas kaitan pegawai dan project secara diam-diam
     * (kolomnya nullOnDelete), sehingga data kehilangan asal-usulnya tanpa
     * ada yang menyadari. Menonaktifkannya adalah jalan yang benar.
     */
    public function destroy(UnitKerja $unitKerja): RedirectResponse
    {
        $pegawai = User::where('unit_kerja_id', $unitKerja->id)->count();
        $project = Project::where('unit_kerja_id', $unitKerja->id)->count();

        if ($pegawai > 0 || $project > 0) {
            return back()->with('error', sprintf(
                'Bidang ini masih dipakai %d pegawai dan %d project. Nonaktifkan saja bila sudah tidak digunakan.',
                $pegawai,
                $project
            ));
        }

        $unitKerja->delete();

        return back()->with('success', 'Bidang berhasil dihapus.');
    }
}
