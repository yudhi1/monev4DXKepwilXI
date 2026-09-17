/*
 | Perhitungan capaian WIG.
 |
 | Sifat menentukan cara angka bulanan diringkas menjadi capaian "s.d. bulan";
 | arah menentukan penilaiannya, bukan aritmetikanya. Persentase tetap
 | realisasi ÷ target apa pun arahnya — untuk WIG efisiensi angka itu dibaca
 | sebagai "berapa persen pagu sudah terpakai", sehingga di bawah 100% justru
 | baik. Membalik rumusnya (target ÷ realisasi) akan memberi 173% di tengah
 | tahun hanya karena tahunnya belum selesai.
 |
 | Kembarannya di sisi server ada di App\Support\Wig\Capaian — ubah keduanya
 | bila rumus ini berubah.
 */

/** Bulan dianggap terisi bila target atau realisasinya sudah diisi. */
const terisi = (m) => Number(m.target || 0) > 0 || Number(m.realisasi || 0) > 0;

const jumlah = (bulan, kunci) => bulan.reduce((n, m) => n + Number(m[kunci] || 0), 0);

/**
 * Ringkasan nilai bulan 1..sampai menurut sifat WIG.
 *
 * @param {Array}  bulan  dua belas entri {bulan, target, realisasi}
 * @param {number} sampai banyak bulan yang diperhitungkan (1..12)
 */
export function ringkas(bulan, sampai, kunci, sifat) {
    const potong = bulan.slice(0, sampai);

    if (sifat === 'posisi') {
        // Posisi terakhir yang pernah tercatat; bulan kosong di ujung diabaikan.
        const terakhir = [...potong].reverse().find(terisi);

        return terakhir ? Number(terakhir[kunci] || 0) : 0;
    }

    if (sifat === 'periodik') {
        // Bulan yang belum diisi tidak boleh ikut membagi dan menyeret rata-rata.
        const dipakai = potong.filter(terisi);

        return dipakai.length ? jumlah(dipakai, kunci) / dipakai.length : 0;
    }

    return jumlah(potong, kunci);
}

export function persen(pembilang, penyebut) {
    return Number(penyebut) > 0 ? Math.round((pembilang / penyebut) * 10000) / 100 : 0;
}

/**
 * Capaian terhadap target tahunan.
 *
 * WIG bersifat posisi diukur dari jarak yang sudah ditempuh sejak nilai awal,
 * bukan dari besar angkanya — peserta aktif 1,35 juta dengan target 1,39 juta
 * bukan berarti sudah 97% tercapai bila berangkatnya memang dari 1,35 juta.
 */
export function persenTahunan(baris, sampai, sifat) {
    const capaian = ringkas(baris.bulan, sampai, 'realisasi', sifat);

    if (sifat === 'posisi') {
        const awal = Number(baris.nilai_awal || 0);
        const rentang = Number(baris.nilai_target || 0) - awal;

        return rentang !== 0 ? Math.round(((capaian - awal) / rentang) * 10000) / 100 : 0;
    }

    return persen(capaian, Number(baris.nilai_target || 0));
}

/** Tercapai bila melampaui target — atau menekannya, untuk WIG efisiensi. */
export function tercapai(nilai, arah) {
    return arah === 'turun' ? nilai > 0 && nilai <= 100 : nilai >= 100;
}

export function warnaPersen(nilai, arah) {
    if (arah === 'turun') {
        if (nilai === 0) {
            return 'text-muted-foreground';
        }

        return nilai <= 100 ? 'text-success' : nilai <= 110 ? 'text-warning-foreground' : 'text-destructive';
    }

    return nilai >= 100 ? 'text-success' : nilai >= 90 ? 'text-warning-foreground' : 'text-destructive';
}

/** Label kolom target tahunan; WIG periodik tidak punya total setahun. */
export function labelTarget(sifat, tahun) {
    return sifat === 'periodik' ? 'Target Bulanan' : `Target ${tahun}`;
}
