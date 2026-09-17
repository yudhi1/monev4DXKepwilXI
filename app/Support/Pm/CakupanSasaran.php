<?php

namespace App\Support\Pm;

use App\Models\Cabang;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sejauh mana seorang Project Manager boleh menujukan quiz-nya.
 *
 * PM kantor cabang hanya menjangkau kantornya sendiri: pilihannya kantor
 * cabangnya, atau pegawai tertentu di kantor itu. PM Kedeputian Wilayah dan
 * admin menjangkau semuanya, termasuk menyasar per bidang lintas cabang.
 *
 * Semua aturannya dikumpulkan di sini karena dipakai dua kali dengan taruhan
 * berbeda: menyusun pilihan di layar, dan memvalidasi apa yang dikirim balik.
 * Kalau keduanya menulis aturannya sendiri-sendiri, yang satu akan menyaring
 * lebih longgar daripada yang lain tanpa ada yang menyadarinya.
 */
class CakupanSasaran
{
    /** Jenis tujuan yang boleh dipakai user ini. */
    public static function tipe(User $user): array
    {
        return self::penuh($user)
            ? ['semua', 'cabang', 'unit_kerja', 'pegawai']
            : ['cabang', 'pegawai'];
    }

    /**
     * Id yang sah untuk sebuah jenis tujuan.
     *
     * Jenis di luar jangkauan user mengembalikan daftar kosong, sehingga
     * apa pun yang dikirim untuk jenis itu pasti ditolak validasi.
     *
     * @return array<int, int>
     */
    public static function idSah(User $user, ?string $tipe): array
    {
        if (! in_array($tipe, self::tipe($user), true)) {
            return [];
        }

        return match ($tipe) {
            'cabang' => self::penuh($user)
                ? Cabang::where('kode', '!=', 'INTERN')->pluck('id')->all()
                : array_filter([$user->cabang_id]),

            'unit_kerja' => self::penuh($user)
                ? UnitKerja::aktif()->pluck('id')->all()
                : [],

            'pegawai' => self::pegawaiTerjangkau($user)->pluck('id')->all(),

            default => [],
        };
    }

    /** Daftar pilihan tujuan untuk ditampilkan di layar. */
    public static function pilihan(User $user): array
    {
        $cabang = Cabang::where('kode', '!=', 'INTERN')
            ->when(! self::penuh($user), fn ($q) => $q->whereKey($user->cabang_id))
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->all();

        $unitKerja = self::penuh($user)
            ? UnitKerja::aktif()
                ->with('cabang:id,nama')
                ->urutTampil()
                ->get()
                ->map(fn (UnitKerja $u) => [
                    'id' => $u->id,
                    'nama' => $u->nama,
                    'induk' => $u->tingkat === 'cabang'
                        ? ($u->cabang?->nama ?? '-')
                        : 'Kedeputian Wilayah',
                ])
                ->all()
            : [];

        $pegawai = self::pegawaiTerjangkau($user)
            ->with('unitKerja:id,nama')
            ->orderBy('name')
            ->get(['id', 'name', 'npp', 'unit_kerja_id'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'npp' => $u->npp,
                'induk' => $u->unitKerja?->nama ?? '—',
            ])
            ->all();

        return [
            'cabang' => $cabang,
            'unit_kerja' => $unitKerja,
            'pegawai' => $pegawai,
        ];
    }

    /**
     * Admin dan orang Kedeputian Wilayah menjangkau seluruh organisasi.
     *
     * Penanda "orang cabang" adalah cabang_id-nya terisi — sama seperti yang
     * dipakai User::bidangTerkelola() untuk membatasi pengelolaan pegawai.
     */
    private static function penuh(User $user): bool
    {
        return $user->can('pm.kelola') || $user->cabang_id === null;
    }

    /** @return Builder<User> */
    private static function pegawaiTerjangkau(User $user)
    {
        return User::query()
            ->pegawai()
            ->where('is_active', true)
            ->when(! self::penuh($user), fn ($q) => $q->where('cabang_id', $user->cabang_id));
    }
}
