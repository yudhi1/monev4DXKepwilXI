<?php

namespace App\Support\Wig;

/**
 * Perhitungan capaian WIG.
 *
 * Kembaran dari resources/js/lib/capaianWig.js — ubah keduanya bila rumusnya
 * berubah. Lihat berkas itu untuk alasan di balik tiap keputusan rumus.
 */
class Capaian
{
    /** Bulan dianggap terisi bila target atau realisasinya sudah diisi. */
    public static function terisi(array $m): bool
    {
        return (float) ($m['target'] ?? 0) > 0 || (float) ($m['realisasi'] ?? 0) > 0;
    }

    /**
     * Ringkasan nilai bulan 1..$sampai menurut sifat WIG.
     *
     * @param  array<int, array{bulan:int, target:float, realisasi:float}>  $bulan
     */
    public static function ringkas(array $bulan, int $sampai, string $kunci, string $sifat): float
    {
        $potong = array_slice($bulan, 0, $sampai);

        if ($sifat === 'posisi') {
            foreach (array_reverse($potong) as $m) {
                if (self::terisi($m)) {
                    return (float) ($m[$kunci] ?? 0);
                }
            }

            return 0.0;
        }

        if ($sifat === 'periodik') {
            $dipakai = array_filter($potong, fn ($m) => self::terisi($m));

            if ($dipakai === []) {
                return 0.0;
            }

            return array_sum(array_map(fn ($m) => (float) ($m[$kunci] ?? 0), $dipakai)) / count($dipakai);
        }

        return array_sum(array_map(fn ($m) => (float) ($m[$kunci] ?? 0), $potong));
    }

    public static function persen(float $pembilang, float $penyebut): float
    {
        return $penyebut > 0 ? round($pembilang / $penyebut * 100, 2) : 0.0;
    }

    /**
     * Capaian terhadap target tahunan.
     *
     * @param  array{nilai_awal?:float, nilai_target:float, bulan:array}  $baris
     */
    public static function persenTahunan(array $baris, int $sampai, string $sifat): float
    {
        $capaian = self::ringkas($baris['bulan'], $sampai, 'realisasi', $sifat);

        if ($sifat === 'posisi') {
            $awal = (float) ($baris['nilai_awal'] ?? 0);
            $rentang = (float) $baris['nilai_target'] - $awal;

            return $rentang != 0.0 ? round(($capaian - $awal) / $rentang * 100, 2) : 0.0;
        }

        return self::persen($capaian, (float) $baris['nilai_target']);
    }

    /** Tercapai bila melampaui target — atau menekannya, untuk WIG efisiensi. */
    public static function tercapai(float $nilai, string $arah): bool
    {
        return $arah === 'turun' ? $nilai > 0 && $nilai <= 100 : $nilai >= 100;
    }
}
