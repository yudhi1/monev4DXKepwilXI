<?php

namespace App\Http\Controllers;

use App\Exports\TemplatePegawaiExport;
use App\Http\Requests\PegawaiRequest;
use App\Imports\GenericSheetImport;
use App\Models\Cabang;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Kelola akun pegawai perorangan — pengguna modul Project Management.
 *
 * Terpisah dari UserController yang mengurus akun unit kerja Monev 4DX:
 * kedua jenis akun punya field yang berbeda, jadi layarnya pun dipisah
 * meski keduanya tersimpan di tabel `users`.
 */
class PegawaiController extends Controller
{
    /** Password awal akun pegawai baru; diganti admin lewat form edit. */
    private const PASSWORD_AWAL = 'monev2026';

    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari', ''));
        $unitKerja = $request->query('unit_kerja');
        $tingkat = $request->query('tingkat');
        $pmRole = $request->query('pm_role');

        $bidang = $request->user()->bidangTerkelola();

        $pegawais = User::query()
            ->pegawai()
            // Hanya pegawai pada bidang yang boleh dikelola akun ini.
            ->whereIn('unit_kerja_id', $bidang)
            ->with('unitKerja.cabang:id,nama')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$cari}%")
                    ->orWhere('jabatan', 'like', "%{$cari}%")
            ))
            ->when($unitKerja, fn ($q) => $q->where('unit_kerja_id', $unitKerja))
            ->when($tingkat, fn ($q) => $q->whereHas('unitKerja', fn ($u) => $u->where('tingkat', $tingkat)))
            ->when($pmRole, fn ($q) => $q->where('pm_role', $pmRole))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'jabatan' => $u->jabatan,
                'pm_role' => $u->pm_role,
                'is_active' => (bool) $u->is_active,
                'unit_kerja_id' => $u->unit_kerja_id,
                'tingkat' => $u->unitKerja?->tingkat,
                'unitKerja' => $u->unitKerja?->nama,
                'induk' => $this->induk($u->unitKerja),
            ]);

        return Inertia::render('Pegawai/Index', [
            'pegawais' => $pegawais,
            'unitKerjas' => $this->daftarUnitKerja($request->user()),
            'cabangs' => $this->daftarCabang($request->user()),
            'semuaPenempatan' => $request->user()->bisaKelolaSemuaBidang(),
            'penempatan' => $this->labelPenempatan($request->user()),
            'roleAkun' => config('pm.role_akun'),
            'filter' => [
                'cari' => $cari,
                'unit_kerja' => $unitKerja,
                'tingkat' => $tingkat,
                'pm_role' => $pmRole,
            ],
        ]);
    }

    public function store(PegawaiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $unit = UnitKerja::findOrFail($data['unit_kerja_id']);

        $pegawai = User::create([
            ...collect($data)->except('password')->all(),
            'tipe' => 'pegawai',
            'email' => $this->emailDariNama($data['name']),
            'password' => Hash::make($data['password']),
            // Wilayah/cabang diturunkan dari bidangnya, tidak diisi manual.
            'wilayah_id' => $unit->wilayah_id,
            'cabang_id' => $unit->cabang_id,
        ]);

        $pegawai->selaraskanIzinPm();

        return back()->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function update(PegawaiRequest $request, User $pegawai): RedirectResponse
    {
        abort_unless($pegawai->adalahPegawai(), 404);
        $this->pastikanDalamCakupan($request->user(), $pegawai);

        $data = $request->validated();
        $unit = UnitKerja::findOrFail($data['unit_kerja_id']);

        $payload = collect($data)->except('password')->all();
        $payload['wilayah_id'] = $unit->wilayah_id;
        $payload['cabang_id'] = $unit->cabang_id;

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $pegawai->update($payload);
        $pegawai->selaraskanIzinPm();

        return back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pegawai): RedirectResponse
    {
        abort_unless($pegawai->adalahPegawai(), 404);
        $this->pastikanDalamCakupan($request->user(), $pegawai);

        if ($pegawai->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $pegawai->delete();

        return back()->with('success', 'Pegawai berhasil dihapus.');
    }

    /**
     * Pegawai di luar penempatan pengelola tidak boleh diubah maupun dihapus.
     *
     * Daftarnya memang sudah disaring, tetapi id pegawai lain masih bisa
     * disisipkan lewat URL.
     */
    private function pastikanDalamCakupan(User $pengelola, User $pegawai): void
    {
        abort_unless(in_array($pegawai->unit_kerja_id, $pengelola->bidangTerkelola(), true), 403);
    }

    /** Unduh template Excel untuk impor pegawai. */
    public function templateImpor(): BinaryFileResponse
    {
        return Excel::download(new TemplatePegawaiExport, 'template-pegawai.xlsx');
    }

    /**
     * Impor pegawai massal dari Excel.
     *
     * Kolom: nama | jabatan | bidang | cabang | role
     * Kolom `cabang` dikosongkan untuk pegawai di kantor Kedeputian Wilayah.
     * Kolom `role` boleh kosong — bawaannya `member`.
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

        // Impor pun terbatas pada penempatan; kalau tidak, batasnya mudah dilewati.
        $unitKerjas = UnitKerja::aktif()
            ->whereIn('id', $request->user()->bidangTerkelola())
            ->with('cabang:id,kode,nama')
            ->get();
        $roleSah = array_keys(config('pm.role_akun'));

        $berhasil = 0;
        $diperbarui = 0;
        $gagal = [];

        foreach (array_slice($baris, 1) as $i => $isi) {
            $nomorBaris = $i + 2;

            $ambil = fn (string $nama) => isset($kolom[$nama]) ? trim((string) ($isi[$kolom[$nama]] ?? '')) : '';

            $nama = $ambil('nama');
            $kodeBidang = Str::upper($ambil('bidang'));
            $kodeCabang = Str::upper($ambil('cabang'));
            $jabatan = $ambil('jabatan');
            $role = Str::lower(str_replace(' ', '_', $ambil('role')));

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
                    ($kodeCabang !== '' ? " di cabang '{$kodeCabang}'" : ' di Kedeputian Wilayah').
                    ' tidak ditemukan atau di luar penempatan Anda';

                continue;
            }

            if ($role !== '' && ! in_array($role, $roleSah, true)) {
                $gagal[] = "baris {$nomorBaris}: role '{$role}' tidak dikenal";

                continue;
            }

            $atribut = [
                'tipe' => 'pegawai',
                'unit_kerja_id' => $unit->id,
                'jabatan' => $jabatan ?: null,
                'pm_role' => $role ?: 'member',
                'wilayah_id' => $unit->wilayah_id,
                'cabang_id' => $unit->cabang_id,
                'is_active' => true,
            ];

            $sudahAda = User::where('name', $nama)->first();

            if ($sudahAda && ! in_array($sudahAda->unit_kerja_id, $request->user()->bidangTerkelola(), true)) {
                $gagal[] = "baris {$nomorBaris}: '{$nama}' terdaftar di penempatan lain";

                continue;
            }

            if ($sudahAda) {
                $sudahAda->update($atribut);
                $sudahAda->selaraskanIzinPm();
                $diperbarui++;
            } else {
                $pegawai = User::create([
                    ...$atribut,
                    'name' => $nama,
                    'email' => $this->emailDariNama($nama),
                    'password' => Hash::make(self::PASSWORD_AWAL),
                ]);
                $pegawai->selaraskanIzinPm();
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

    /* ---------------------------------------------------------------- */

    /** Kantor cabang yang boleh dipilih; akun cabang hanya kantornya sendiri. */
    private function daftarCabang(User $pengelola): array
    {
        $q = Cabang::where('kode', '!=', 'INTERN');

        if (! $pengelola->bisaKelolaSemuaBidang() && $pengelola->cabang_id) {
            $q->whereKey($pengelola->cabang_id);
        }

        return $q->orderBy('nama')->get(['id', 'nama'])->all();
    }

    /** Keterangan cakupan, ditampilkan agar batasnya tidak terasa seperti bug. */
    private function labelPenempatan(User $pengelola): ?string
    {
        if ($pengelola->bisaKelolaSemuaBidang()) {
            return null;
        }

        if ($pengelola->cabang_id) {
            return $pengelola->cabang?->nama;
        }

        return 'Kedeputian Wilayah';
    }

    private function induk(?UnitKerja $unit): ?string
    {
        if (! $unit) {
            return null;
        }

        return $unit->tingkat === 'cabang'
            ? ($unit->cabang?->nama ?? '-')
            : 'Kedeputian Wilayah';
    }

    /** Unit kerja untuk dropdown bertingkat, sudah disaring ke penempatan. */
    private function daftarUnitKerja(User $pengelola): array
    {
        return UnitKerja::aktif()
            ->whereIn('id', $pengelola->bidangTerkelola())
            ->with('cabang:id,nama')
            ->urutTampil()
            ->get()
            ->map(fn (UnitKerja $u) => [
                'id' => $u->id,
                'kode' => $u->kode,
                'nama' => $u->nama,
                'tingkat' => $u->tingkat,
                'cabang_id' => $u->cabang_id,
                'induk' => $this->induk($u),
            ])
            ->all();
    }

    /** Email tidak diisi manual — diturunkan dari nama dan dijamin unik. */
    private function emailDariNama(string $nama): string
    {
        $dasar = Str::slug($nama, '.') ?: 'pegawai';
        $email = $dasar.'@monev.local';
        $i = 1;

        while (User::where('email', $email)->exists()) {
            $email = $dasar.($i++).'@monev.local';
        }

        return $email;
    }
}
