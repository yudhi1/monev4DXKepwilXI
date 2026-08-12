/**
 * Warna badge per bidang WIG.
 *
 * Kelasnya ditulis lengkap (bukan dirakit dari potongan string) supaya
 * Tailwind ikut menghasilkannya saat build — kelas yang dibentuk saat
 * runtime tidak akan ada di CSS hasil build.
 */
const KELAS = {
    JPK: 'border-bidang-jpk/30 bg-bidang-jpk/10 text-bidang-jpk',
    KML: 'border-bidang-kml/30 bg-bidang-kml/10 text-bidang-kml',
    PIKEU: 'border-bidang-pikeu/35 bg-bidang-pikeu/12 text-bidang-pikeu',
    SDMUK: 'border-bidang-sdmuk/30 bg-bidang-sdmuk/10 text-bidang-sdmuk',
};

/** Kelas warna untuk satu bidang; bidang tak dikenal memakai gaya netral. */
export function kelasBidang(bidang) {
    return KELAS[bidang] ?? 'text-muted-foreground';
}
