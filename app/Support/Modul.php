<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pembantu baca config/modul.php: modul mana yang boleh diakses user dan
 * modul mana yang sedang dibuka.
 */
class Modul
{
    /**
     * Daftar modul yang boleh diakses user, siap dikirim ke Inertia.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function untukUser(?object $user): array
    {
        if (! $user) {
            return [];
        }

        $daftar = [];

        foreach (config('modul.daftar', []) as $kunci => $modul) {
            $permission = $modul['permission'] ?? null;

            if ($permission && ! $user->can($permission)) {
                continue;
            }

            $daftar[] = [
                'kunci' => $kunci,
                'nama' => $modul['nama'],
                'deskripsi' => $modul['deskripsi'],
                'ikon' => $modul['ikon'],
                'warna' => $modul['warna'],
                'beranda' => $modul['beranda'],
            ];
        }

        return $daftar;
    }

    /**
     * Modul yang sedang dibuka. Diisi middleware `modul`; kalau rute tidak
     * memakainya, ditebak dari awalan URL.
     */
    public static function aktif(Request $request): ?string
    {
        $dariMiddleware = $request->attributes->get('modul');

        if ($dariMiddleware) {
            return $dariMiddleware;
        }

        $path = '/'.ltrim($request->path(), '/');

        foreach (config('modul.daftar', []) as $kunci => $modul) {
            $beranda = rtrim($modul['beranda'], '/');

            if ($beranda !== '' && ($path === $beranda || str_starts_with($path, $beranda.'/'))) {
                return $kunci;
            }
        }

        return null;
    }
}
