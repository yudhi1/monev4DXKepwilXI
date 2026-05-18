<?php

namespace App\Support;

class Format
{
    /**
     * Format angka sesuai satuan.
     * - Satuan berisi "%"  → tampilkan apa adanya + "%"  (mis. 9.5 → "9,5%")
     * - Satuan diawali "Rp" atau kosong → ribuan tanpa desimal (mis. 19969779397 → "Rp 19.969.779.397")
     * - Satuan lain (unit, transaksi, dst) → ribuan tanpa desimal + suffix satuan
     */
    public static function nilai(float|int|string|null $value, ?string $satuan = null): string
    {
        $value = (float) $value;
        $satuan = trim((string) $satuan);

        if ($satuan !== '' && str_contains($satuan, '%')) {
            $formatted = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
            return $formatted.'%';
        }

        if ($satuan === '' || stripos($satuan, 'rp') === 0) {
            return 'Rp '.number_format($value, 0, ',', '.');
        }

        return number_format($value, 0, ',', '.').' '.$satuan;
    }
}
