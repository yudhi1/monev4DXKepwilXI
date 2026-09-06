<?php

/*
 | Daftar modul aplikasi. Satu sumber kebenaran untuk halaman pemilih modul
 | (/apps), switcher di header, dan middleware `modul`.
 |
 | `permission` diperiksa dengan spatie/laravel-permission. Modul tanpa
 | permission (null) terbuka untuk semua user yang sudah login.
 */

return [

    'default' => '4dx',

    'daftar' => [

        '4dx' => [
            'nama' => 'Monev 4DX',
            'deskripsi' => 'Monitoring WIG, Lag & Lead Measure, serta capaian kantor cabang.',
            'ikon' => 'ChartNoAxesCombined',
            'warna' => 'primary',
            'beranda' => '/dashboard',
            'permission' => 'akses-4dx',
        ],

        /*
         | Master Data berdiri sendiri, bukan menu di dalam 4DX, karena isinya
         | melayani kedua modul: akun, pegawai, dan struktur organisasi.
         | Menempatkannya di dalam salah satu modul membuat modul itu seolah
         | pemilik data bersama — dan sempat menghasilkan kejanggalan nyata:
         | menambah Pegawai (urusan PM) mengharuskan admin masuk ke 4DX.
         */
        'master' => [
            'nama' => 'Master Data',
            'deskripsi' => 'Akun, pegawai, bidang, wilayah, dan kantor cabang.',
            'ikon' => 'Database',
            'warna' => 'slate',
            /*
             | Beranda modul WAJIB terbuka bagi semua pemegang permission-nya.
             | Sebelumnya diarahkan ke /users yang khusus admin, sehingga akun
             | kantor cabang menabrak 403 tepat setelah mengklik kartunya.
             | /pegawai adalah satu-satunya halaman di modul ini yang terbuka
             | untuk seluruh pemegang akses-master.
             */
            'beranda' => '/pegawai',
            'permission' => 'akses-master',
        ],

        'pm' => [
            'nama' => 'Project Management',
            'deskripsi' => 'Pengelolaan project, task, tim, progres, dan kontribusi.',
            'ikon' => 'FolderKanban',
            'warna' => 'success',
            'beranda' => '/pm',
            'permission' => 'akses-pm',
        ],

    ],

];
