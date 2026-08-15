<?php

/*
 | Nilai status & prioritas modul Project Management.
 |
 | Disimpan di config, bukan enum MySQL, supaya menambah/mengubah pilihan
 | tidak memerlukan migrasi. Kolom di database bertipe string pendek.
 */

return [

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
