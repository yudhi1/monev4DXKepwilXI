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
