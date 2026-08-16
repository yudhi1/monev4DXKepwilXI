<?php

namespace App\Http\Controllers;

use App\Exports\TemplatePegawaiExport;
use App\Http\Requests\UserRequest;
use App\Imports\GenericSheetImport;
use App\Models\Cabang;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    /** Password awal akun pegawai hasil impor; diganti admin lewat form edit. */
    private const PASSWORD_AWAL = 'monev2026';

    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $role = $request->query('role');
        $unitKerja = $request->query('unit_kerja');

        $users = User::query()
            ->with(['roles:id,name', 'permissions:id,name', 'wilayah:id,nama', 'cabang:id,nama', 'unitKerja.cabang:id,nama'])
            ->when($cari !== '', fn ($q) => $q->where('name', 'like', "%{$cari}%"))
            ->when($role, fn ($q) => $q->whereHas('roles', fn ($sub) => $sub->where('name', $role)))
            ->when($unitKerja, fn ($q) => $q->where('unit_kerja_id', $unitKerja))
            // Yang aktif didahulukan; user nonaktif turun ke bawah.
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'alamat' => $user->alamat,
                'role' => $user->getRoleNames()->first(),
                'wilayah_id' => $user->wilayah_id,
                'cabang_id' => $user->cabang_id,
                'unit_kerja_id' => $user->unit_kerja_id,
                'jabatan' => $user->jabatan,
                'lihat_semua_project' => $user->hasDirectPermission('pm.lihat-semua'),
                'wilayah' => $user->wilayah?->nama,
                'cabang' => $user->cabang?->nama,
                'unitKerja' => $user->unitKerja?->nama,
                'unitKerjaInduk' => $user->unitKerja?->tingkat === 'cabang'
                    ? $user->unitKerja?->cabang?->nama
                    : 'Kedeputian Wilayah',
            ]);

        return Inertia::render('User/Index', [
            'users' => $users,
            'wilayahs' => Wilayah::orderBy('nama')->get(['id', 'nama']),
            'cabangs' => Cabang::orderBy('nama')->get(['id', 'nama', 'wilayah_id']),
            'unitKerjas' => $this->daftarUnitKerja(),
            'filter' => ['cari' => $cari, 'role' => $role, 'unit_kerja' => $unitKerja],
        ]);
    }

    /** Unit kerja untuk dropdown, sudah berlabel kantor induknya. */
    private function daftarUnitKerja(): array
    {
        return UnitKerja::aktif()
            ->with('cabang:id,nama')
            ->urutTampil()
            ->get()
            ->map(fn (UnitKerja $u) => [
                'id' => $u->id,
                'nama' => $u->nama,
                'tingkat' => $u->tingkat,
                'induk' => $u->tingkat === 'cabang' ? ($u->cabang?->nama ?? '-') : 'Kedeputian Wilayah',
            ])
            ->all();
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except(['password', 'role', 'lihat_semua_project'])->all(),
            'email' => $this->emailDariNama($data['name']),
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);
        $this->aturHakPimpinan($user, (bool) ($data['lihat_semua_project'] ?? false));

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $payload = collect($data)->except(['password', 'role', 'lihat_semua_project'])->all();

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);
        $this->aturHakPimpinan($user, (bool) ($data['lihat_semua_project'] ?? false));

        return back()->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hak melihat seluruh project diberikan langsung ke user, bukan lewat
     * role — karena role `kedeputian_wilayah` kini juga dipakai staf bidang,
     * yang tidak boleh melihat pekerjaan seluruh organisasi.
     */
    private function aturHakPimpinan(User $user, bool $pimpinan): void
    {
        if ($pimpinan) {
            $user->givePermissionTo('pm.lihat-semua');

            return;
        }

        if ($user->hasDirectPermission('pm.lihat-semua')) {
            $user->revokePermissionTo('pm.lihat-semua');
        }
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    /** Unduh template Excel untuk impor pegawai. */
    public function templateImpor(): BinaryFileResponse
    {
        return Excel::download(new TemplatePegawaiExport, 'template-pegawai.xlsx');
    }

    /**
     * Impor pegawai massal dari Excel.
     *
     * Kolom: nama | jabatan | bidang | cabang
     * Kolom `cabang` dikosongkan untuk pegawai di kantor Kedeputian Wilayah.
     * Role tidak diminta — diturunkan dari tingkat unit kerjanya.
     */
    public function impor(Request $request): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']],
            [],
            ['file' => 'berkas Excel']
        );

        $baris = Excel::toArray(new GenericSheetImport, $request->file('file'))[0] ?? [];

        if (count($baris) < 2) {
            return back()->with('error', 'Berkas kosong atau hanya berisi baris judul.');
        }

        // Header dicari berdasarkan nama kolom, bukan posisinya.
        $header = array_map(fn ($k) => Str::of((string) $k)->lower()->trim()->toString(), $baris[0]);
        $kolom = array_flip($header);

        foreach (['nama', 'bidang'] as $wajib) {
            if (! isset($kolom[$wajib])) {
                return back()->with('error', "Kolom '{$wajib}' tidak ditemukan pada berkas. Unduh template terlebih dahulu.");
            }
        }

        $unitKerjas = UnitKerja::aktif()->with('cabang:id,kode,nama')->get();

        $berhasil = 0;
        $diperbarui = 0;
        $gagal = [];

        foreach (array_slice($baris, 1) as $i => $isi) {
            $nomorBaris = $i + 2;

            $nama = trim((string) ($isi[$kolom['nama']] ?? ''));
            $kodeBidang = Str::upper(trim((string) ($isi[$kolom['bidang']] ?? '')));
            $kodeCabang = isset($kolom['cabang']) ? Str::upper(trim((string) ($isi[$kolom['cabang']] ?? ''))) : '';
            $jabatan = isset($kolom['jabatan']) ? trim((string) ($isi[$kolom['jabatan']] ?? '')) : null;

            // Baris kosong dan baris keterangan pada template dilewati diam-diam.
            if ($nama === '' || $kodeBidang === '') {
                continue;
            }

            $unit = $unitKerjas->first(function (UnitKerja $u) use ($kodeBidang, $kodeCabang) {
                if ($u->kode !== $kodeBidang) {
                    return false;
                }

                return $kodeCabang === ''
                    ? $u->cabang_id === null
                    : Str::upper((string) $u->cabang?->kode) === $kodeCabang;
            });

            if (! $unit) {
                $gagal[] = "baris {$nomorBaris}: bidang '{$kodeBidang}'".
                    ($kodeCabang !== '' ? " di cabang '{$kodeCabang}'" : ' di Kedeputian Wilayah').' tidak ditemukan';

                continue;
            }

            $sudahAda = User::where('name', $nama)->first();

            $atribut = [
                'unit_kerja_id' => $unit->id,
                'jabatan' => $jabatan ?: null,
                'wilayah_id' => $unit->wilayah_id,
                'cabang_id' => $unit->cabang_id,
                'is_active' => true,
            ];

            if ($sudahAda) {
                $sudahAda->update($atribut);
                $diperbarui++;
            } else {
                $user = User::create([
                    ...$atribut,
                    'name' => $nama,
                    'email' => $this->emailDariNama($nama),
                    'password' => Hash::make(self::PASSWORD_AWAL),
                ]);
                $user->syncRoles([$unit->tingkat === 'wilayah' ? 'kedeputian_wilayah' : 'kantor_cabang']);
                $berhasil++;
            }
        }

        $pesan = "{$berhasil} pegawai baru ditambahkan, {$diperbarui} diperbarui.";

        if ($berhasil > 0) {
            $pesan .= ' Password awal: '.self::PASSWORD_AWAL;
        }

        if ($gagal !== []) {
            return back()
                ->with('success', $pesan)
                ->with('error', count($gagal).' baris gagal — '.implode('; ', array_slice($gagal, 0, 5))
                    .(count($gagal) > 5 ? ' …' : ''));
        }

        return back()->with('success', $pesan);
    }

    /**
     * Email tidak diisi manual oleh admin — diturunkan dari nama dan
     * dijamin unik, sama seperti perilaku UserManagement (Livewire).
     */
    private function emailDariNama(string $nama): string
    {
        $dasar = Str::slug($nama, '.') ?: 'user';
        $email = $dasar.'@monev.local';
        $i = 1;

        while (User::where('email', $email)->exists()) {
            $email = $dasar.($i++).'@monev.local';
        }

        return $email;
    }
}
