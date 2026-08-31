<?php

/*
 | Nilai status & prioritas modul Project Management.
 |
 | Disimpan di config, bukan enum MySQL, supaya menambah/mengubah pilihan
 | tidak memerlukan migrasi. Kolom di database bertipe string pendek.
 */

return [

    /*
     | Satuan target/realisasi task. Daftarnya sengaja sama dengan modul 4DX
     | (WigCapaianController::SATUAN) supaya istilahnya seragam di kedua modul.
     */
    'satuan' => ['Rp', '%', 'orang', 'unit', 'transaksi', 'kasus', 'badan usaha', 'dokumen'],

    /* Kolom papan Kanban, urut kiri ke kanan. */
    'status_task' => [
        'backlog' => ['label' => 'Backlog', 'warna' => 'slate', 'keterangan' => 'Ide / pekerjaan belum diprioritaskan'],
        'todo' => ['label' => 'To Do', 'warna' => 'blue', 'keterangan' => 'Siap dikerjakan'],
        'in_progress' => ['label' => 'In Progress', 'warna' => 'amber', 'keterangan' => 'Sedang dikerjakan'],
        'review' => ['label' => 'Review', 'warna' => 'violet', 'keterangan' => 'Menunggu review / validasi'],
        'done' => ['label' => 'Done', 'warna' => 'emerald', 'keterangan' => 'Pekerjaan selesai'],
    ],

    /* Status task yang dianggap sudah rampung. */
    'status_selesai' => 'done',

    'status_project' => [
        'perencanaan' => ['label' => 'Perencanaan', 'warna' => 'slate'],
        'berjalan' => ['label' => 'Berjalan', 'warna' => 'blue'],
        'tertahan' => ['label' => 'Tertahan', 'warna' => 'amber'],
        'selesai' => ['label' => 'Selesai', 'warna' => 'emerald'],
        'batal' => ['label' => 'Batal', 'warna' => 'rose'],
    ],

    'prioritas' => [
        'rendah' => ['label' => 'Rendah', 'warna' => 'slate'],
        'sedang' => ['label' => 'Sedang', 'warna' => 'amber'],
        'tinggi' => ['label' => 'Tinggi', 'warna' => 'rose'],
    ],

    /*
     | Role pegawai di tingkat akun (kolom users.pm_role) — mengatur hak
     | LINTAS project. Jangan dikacaukan dengan 'peran' di bawah, yang berlaku
     | di dalam satu project tertentu.
     |
     | 'permissions' disinkronkan ke spatie setiap kali pegawai disimpan.
     */
    'role_akun' => [
        'member' => [
            'label' => 'Member',
            'keterangan' => 'Ikut project yang mendaftarkannya; tidak bisa membuat project sendiri',
            'permissions' => ['akses-pm'],
        ],
        'project_manager' => [
            'label' => 'Project Manager',
            'keterangan' => 'Boleh membuat project atas nama unit kerjanya',
            'permissions' => ['akses-pm', 'pm.project.buat'],
        ],
        'pimpinan' => [
            'label' => 'Pimpinan',
            'keterangan' => 'Melihat seluruh project tanpa harus menjadi anggota',
            'permissions' => ['akses-pm', 'pm.project.buat', 'pm.lihat-semua'],
        ],
    ],

    /* Peran seseorang di dalam satu project (kolom pm_project_members.peran). */
    'peran' => [
        'manager' => ['label' => 'Project Manager', 'keterangan' => 'Atur anggota, milestone, task, dan bobot'],
        'member' => ['label' => 'Member', 'keterangan' => 'Kerjakan task, update progres, komentar'],
        'viewer' => ['label' => 'Viewer', 'keterangan' => 'Baca saja'],
    ],

    /*
     | Ambang project health. Selisih diukur antara progress project dan
     | proporsi waktu yang sudah berlalu (ekspektasi jadwal).
     */
    'health' => [
        'ambang_kritis' => 15, // tertinggal >= 15% -> critical
    ],

];
