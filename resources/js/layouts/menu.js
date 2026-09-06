/*
 | Definisi menu sidebar per modul aplikasi.
 |
 | Dipisahkan dari AppLayout supaya menambah modul tidak membuat layout
 | membengkak. `roles: null` berarti menu terbuka untuk semua role.
 */

import {
    BookOpen,
    Building2,
    CircleCheckBig,
    FileText,
    FolderKanban,
    Gauge,
    Landmark,
    LayoutDashboard,
    Map,
    Star,
    Target,
    TrendingDown,
    TrendingUp,
    UserRound,
    Users,
} from '@lucide/vue';

/** Modul Monev 4DX — masih di URL root sampai Fase 2 restrukturisasi dikerjakan. */
export const menu4dx = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: null },
    {
        label: 'WIG',
        icon: Target,
        roles: ['admin', 'kedeputian_wilayah'],
        items: [
            { label: 'Input Data WIG', href: '/wigs' },
            { label: 'Target & Realisasi WIG', href: '/wig-capaian' },
        ],
    },
    { label: 'Lag Measure', href: '/lag-measures', icon: TrendingDown, roles: ['admin', 'kedeputian_wilayah'] },
    { label: 'Realisasi WIG', href: '/wig-capaian', icon: Target, roles: ['kantor_cabang'] },
    {
        label: 'Lead Measure',
        icon: TrendingUp,
        roles: null,
        items: [
            { label: 'Input Data Lead Measure', href: '/lead-measures' },
            { label: 'Input Realisasi', href: '/realisasi' },
        ],
    },
    {
        label: 'Prioritas',
        icon: Star,
        roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'],
        items: [
            { label: 'Iuran', href: '/monitoring-prioritas/iuran' },
            { label: 'Master Segmen', href: '/monev-iuran/segmen', roles: ['admin', 'kedeputian_wilayah'] },
            { label: 'Input Realisasi Iuran', href: '/monev-iuran/input' },
        ],
    },
    {
        label: 'Monitoring Kinerja',
        icon: Gauge,
        roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'],
        items: [
            { label: 'Capaian Total APC', href: '/monitoring-kinerja/total' },
            { label: 'Peserta Aktif', href: '/monitoring-kinerja/peserta-aktif' },
            { label: 'Tingkat Kepuasan', href: '/monitoring-kinerja/kepuasan' },
            { label: 'Penerimaan Iuran', href: '/monitoring-kinerja/penerimaan-iuran' },
            { label: 'Realisasi Biaya Manfaat', href: '/monitoring-kinerja/biaya-manfaat' },
            { label: 'Biaya Operasional', href: '/monitoring-kinerja/biaya-operasional' },
        ],
    },
    { label: 'Laporan', href: '/laporan', icon: FileText, roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'] },
    { label: 'Panduan', href: '/panduan', icon: BookOpen, roles: null },
];

/** Modul Project Management. */
export const menuPm = [
    { label: 'Dashboard', href: '/pm', icon: LayoutDashboard, roles: null },
    { label: 'Projects', href: '/pm/projects', icon: FolderKanban, roles: null },
    // Bukan "Tugas Saya": halamannya juga memuat tugas tim bagi yang berhak.
    // Judul di dalamnya yang menyesuaikan lingkup terpilih.
    { label: 'Tugas', href: '/pm/tugas-saya', icon: CircleCheckBig, roles: null },
];

/*
 | Modul Master Data. Berdiri sendiri karena isinya melayani kedua modul —
 | dulu ia menu di dalam 4DX, sehingga menambah Pegawai (urusan PM) harus
 | lewat Monev 4DX.
 */
export const menuMaster = [
    { label: 'Akun Monev 4DX', href: '/users', icon: Users, roles: ['admin'] },
    // Satu-satunya menu di sini yang terbuka bagi akun 4DX, dan hanya
    // untuk bidang di penempatannya sendiri.
    { label: 'Akun Project Management', href: '/pegawai', icon: UserRound, roles: null },
    { label: 'Bidang / Unit Kerja', href: '/unit-kerja', icon: Building2, roles: ['admin'] },
    { label: 'Wilayah', href: '/wilayahs', icon: Map, roles: ['admin'] },
    { label: 'Cabang', href: '/cabangs', icon: Landmark, roles: ['admin'] },
];

export const MENU_MODUL = {
    '4dx': menu4dx,
    master: menuMaster,
    pm: menuPm,
};

/** Identitas yang tampil di kepala sidebar per modul. */
export const BRAND_MODUL = {
    '4dx': { judul: 'Monev', sub: '4DX', beranda: '/dashboard', ikon: 'ChartNoAxesCombined' },
    master: { judul: 'Master', sub: 'Data', beranda: '/pegawai', ikon: 'Database' },
    pm: { judul: 'Project', sub: 'Management', beranda: '/pm', ikon: 'FolderKanban' },
};
