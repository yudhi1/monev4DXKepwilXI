/**
 * Warna badge status aktif/nonaktif.
 *
 * Nonaktif ditandai merah, bukan abu-abu, supaya baris yang tidak ikut
 * dihitung tidak terlewat saat menelusuri tabel.
 *
 * Kelasnya ditulis lengkap agar Tailwind menghasilkannya saat build.
 */
export function kelasAktif(aktif) {
    return aktif
        ? 'border-success/30 bg-success/10 text-success'
        : 'border-destructive/30 bg-destructive/10 text-destructive';
}
