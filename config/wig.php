<?php

/*
 | Sifat pengukuran WIG.
 |
 | Tidak semua WIG boleh dijumlahkan antar bulan. Penerimaan iuran memang
 | bertambah tiap bulan, tetapi jumlah peserta aktif adalah posisi — bukan
 | tambahan — dan kepatuhan listrik adalah kadar yang hanya berlaku di bulan
 | itu. Menjumlahkan ketiganya dengan cara yang sama menghasilkan angka
 | yang keliru, maka tiap WIG menyatakan sifatnya sendiri.
 |
 | Disimpan sebagai string biasa, bukan enum MySQL, agar daftarnya bisa
 | bertambah tanpa migration.
 */

return [

    'sifat' => [
        'akumulatif' => [
            'label' => 'Akumulatif',
            'keterangan' => 'Angka tiap bulan menambah. Capaian s.d. bulan = penjumlahan.',
            'contoh' => 'Penerimaan iuran, biaya pelayanan.',
        ],
        'posisi' => [
            'label' => 'Posisi',
            'keterangan' => 'Angka tiap bulan adalah keadaan saat itu. Capaian s.d. bulan = nilai bulan terakhir yang terisi.',
            'contoh' => 'Jumlah peserta aktif.',
        ],
        'periodik' => [
            'label' => 'Periodik',
            'keterangan' => 'Angka hanya berlaku untuk bulannya sendiri. Capaian s.d. bulan = rata-rata bulan yang sudah terisi.',
            'contoh' => 'Persentase kepatuhan, tingkat kehadiran.',
        ],
    ],

    'arah' => [
        'naik' => [
            'label' => 'Naik lebih baik',
            'keterangan' => 'Realisasi di atas target berarti tercapai.',
        ],
        'turun' => [
            'label' => 'Turun lebih baik',
            'keterangan' => 'Realisasi di bawah target berarti tercapai — dipakai untuk efisiensi biaya.',
        ],
    ],

    'bawaan' => [
        'sifat' => 'akumulatif',
        'arah' => 'naik',
    ],

];
