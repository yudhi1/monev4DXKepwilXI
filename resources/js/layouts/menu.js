/*
 | Definisi menu sidebar per modul aplikasi.
 |
 | Dipisahkan dari AppLayout supaya menambah modul tidak membuat layout
 | membengkak. `roles: null` berarti menu terbuka untuk semua role.
 */

import {
    BookOpen,
    CircleCheckBig,
    Database,
    FileText,
    FolderKanban,
    Gauge,
    LayoutDashboard,
    Star,
    Target,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';

/** Modul Monev 4DX — masih di URL root sampai Fase 2 restrukturisasi dikerjakan. */
export const menu4dx = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: null },
    {
        label: 'Master',
        icon: Database,
        roles: ['admin'],
        items: [
            { label: 'User', href: '/users' },
            { label: 'Wilayah', href: '/wilayahs' },
            { label: 'Cabang', href: '/cabangs' },
        ],
    },
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
    { label: 'Tugas Saya', href: '/pm/tugas-saya', icon: CircleCheckBig, roles: null },
];

export const MENU_MODUL = {
    '4dx': menu4dx,
    pm: menuPm,
};

/** Identitas yang tampil di kepala sidebar per modul. */
export const BRAND_MODUL = {
    '4dx': { judul: 'Monev', sub: '4DX', beranda: '/dashboard' },
    pm: { judul: 'Project', sub: 'Management', beranda: '/pm' },
};
