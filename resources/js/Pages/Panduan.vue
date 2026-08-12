<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlarmClock,
    BarChart3,
    Building2,
    ChartNoAxesCombined,
    ChevronRight,
    Lightbulb,
    MessageSquare,
    TrendingUp,
} from '@lucide/vue';
import { cn } from '@/lib/utils';

const DISIPLIN = [
    {
        nomor: 1,
        emoji: '🎯',
        judul: 'Focus on the Wildly Important Goal (WIG)',
        ringkas:
            'Fokus pada satu atau dua tujuan terpenting yang jika tercapai akan memberikan dampak terbesar bagi organisasi, bukan pada semua tujuan sekaligus.',
        butir: [
            'Setiap wilayah/cabang memiliki WIG yang jelas',
            'Dinyatakan dengan format: "Dari X menjadi Y pada tanggal Z"',
            'Disetujui bersama antara pimpinan dan tim',
        ],
    },
    {
        nomor: 2,
        emoji: '📊',
        judul: 'Act on the Lead Measures',
        ringkas:
            'Mengidentifikasi dan mengukur aktivitas kunci (Lead Measure) yang secara langsung mendorong tercapainya WIG.',
        butir: [
            'Lag Measure: hasil akhir (WIG) — bisa diukur tapi sulit dipengaruhi langsung',
            'Lead Measure: aktivitas pendorong — bisa dikendalikan tim setiap minggu',
            'Diisi realisasinya tiap minggu oleh Kantor Cabang',
        ],
    },
    {
        nomor: 3,
        emoji: '📋',
        judul: 'Keep a Compelling Scoreboard',
        ringkas:
            'Papan skor yang menarik dan mudah dibaca agar tim selalu tahu posisi mereka — apakah sedang menang atau kalah.',
        butir: [
            'Dashboard Wilayah: peringkat dan capaian seluruh cabang',
            'Dashboard Cabang: performa individual per WIG',
            'Indikator status: On Track / Waspada / Awas',
        ],
    },
    {
        nomor: 4,
        emoji: '🔄',
        judul: 'Create a Cadence of Accountability',
        ringkas:
            'Sesi WIG mingguan berdurasi singkat (15–20 menit) di mana setiap anggota tim melaporkan komitmen minggu lalu dan menetapkan komitmen baru.',
        butir: [
            'Review capaian Lead Measure tiap minggu',
            'Identifikasi kendala dan rencana tindak lanjut',
            'Komitmen individu — bukan hanya laporan angka',
        ],
    },
];

const ALUR = [
    { emoji: '🏛️', judul: 'Kedeputian Wilayah', ringkas: 'Buat WIG & Lag Measure' },
    { emoji: '📌', judul: 'Lead Measure', ringkas: 'Aktivitas mingguan per cabang' },
    { emoji: '✏️', judul: 'Kantor Cabang', ringkas: 'Input realisasi tiap minggu' },
    { emoji: '📈', judul: 'Dashboard', ringkas: 'Capaian & peringkat real-time' },
    { emoji: '🔄', judul: 'WIG Session', ringkas: 'Review & komitmen mingguan' },
];

const STRUKTUR = [
    {
        entitas: 'WIG',
        warna: 'text-primary',
        keterangan: 'Wildly Important Goal — tujuan utama tahunan',
        contoh: 'Meningkatkan peserta aktif dari 500K ke 600K',
    },
    {
        entitas: 'Lag Measure',
        warna: 'text-success',
        keterangan: 'Indikator hasil — mengukur seberapa jauh WIG tercapai',
        contoh: 'Jumlah peserta aktif terdaftar',
    },
    {
        entitas: 'Lead Measure',
        warna: 'text-warning-foreground',
        keterangan: 'Aktivitas pendorong — dikendalikan tim setiap minggu',
        contoh: 'Kunjungan perusahaan per minggu',
    },
    {
        entitas: 'Realisasi',
        warna: 'text-destructive',
        keterangan: 'Nilai aktual Lead Measure yang dicapai pada minggu tersebut',
        contoh: '12 kunjungan (dari target 15)',
    },
];

const STATUS = [
    {
        label: 'On Track',
        ringkas: 'Capaian ≥ 100% — target tercapai atau terlampaui',
        kelas: 'border-success/30 bg-success/10',
        teks: 'text-success',
    },
    {
        label: 'Waspada',
        ringkas: 'Capaian 90% – <100% — perlu perhatian segera',
        kelas: 'border-warning/40 bg-warning/10',
        teks: 'text-warning-foreground',
    },
    {
        label: 'Awas',
        ringkas: 'Capaian <90% — intervensi diperlukan',
        kelas: 'border-destructive/30 bg-destructive/10',
        teks: 'text-destructive',
    },
];

const AKSES = [
    {
        role: 'Admin',
        rincian:
            'CRUD semua data, kelola user & wilayah, monitoring seluruh wilayah & cabang, export laporan',
    },
    {
        role: 'Kedeputian Wilayah',
        rincian:
            'Buat & kelola WIG, Lag Measure, Lead Measure; monitoring capaian seluruh cabang di wilayahnya; export laporan',
    },
    {
        role: 'Kantor Cabang',
        rincian:
            'Input realisasi Lead Measure mingguan; lihat dashboard performa cabang sendiri; export laporan cabang',
    },
];

const TIPS = [
    {
        ikon: AlarmClock,
        judul: 'Konsisten Setiap Minggu',
        ringkas:
            'Input realisasi Lead Measure tepat waktu setiap minggu. Data yang telat membuat dashboard tidak akurat dan WIG Session tidak efektif.',
    },
    {
        ikon: MessageSquare,
        judul: 'WIG Session yang Singkat & Fokus',
        ringkas:
            'Lakukan WIG Session maksimal 20 menit: (1) laporkan komitmen minggu lalu, (2) update scoreboard, (3) buat komitmen minggu ini.',
    },
    {
        ikon: BarChart3,
        judul: 'Pisahkan WIG dari Whirlwind',
        ringkas:
            'Jangan biarkan aktivitas operasional harian menggeser WIG. Jadwalkan waktu khusus untuk mengerjakan Lead Measure.',
    },
    {
        ikon: TrendingUp,
        judul: 'Fokus pada Lead, Bukan Lag',
        ringkas:
            'Lag Measure adalah hasil — tidak bisa diubah. Energikan tim pada Lead Measure yang bisa dikendalikan hari ini.',
    },
];
</script>

<template>
    <Head title="Panduan" />

    <AppLayout>
        <!-- Hero -->
        <div class="from-primary to-success mb-8 rounded-xl bg-gradient-to-br p-8 text-white">
            <div class="flex items-start gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-white/20 text-3xl">
                    🎯
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Panduan Monitoring Kinerja Berbasis 4DX</h1>
                    <p class="mt-2 max-w-3xl text-sm text-white/85">
                        4 Disciplines of Execution — kerangka kerja eksekusi strategi yang memastikan seluruh unit
                        fokus pada hal yang paling penting dan mengukur kemajuan secara konsisten.
                    </p>
                    <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs">
                        <Building2 class="size-3.5" />
                        Kedeputian Wilayah XI — BPJS Kesehatan
                    </span>
                </div>
            </div>
        </div>

        <!-- Apa itu 4DX -->
        <section class="mb-8">
            <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Apa itu 4DX?</h2>
            <Card>
                <CardContent class="space-y-4">
                    <p class="text-sm">
                        <strong>4 Disciplines of Execution (4DX)</strong> adalah metodologi manajemen eksekusi yang
                        dikembangkan oleh FranklinCovey, dirancang untuk membantu organisasi mengeksekusi strategi
                        terpenting mereka secara konsisten — di tengah pusaran aktivitas operasional sehari-hari
                        (<em>the whirlwind</em>).
                    </p>

                    <div class="bg-accent/40 flex gap-3 rounded-lg border p-4">
                        <Lightbulb class="text-warning-foreground mt-0.5 size-5 shrink-0" />
                        <p class="text-sm">
                            <strong>Prinsip utama:</strong> Strategi yang hebat tidak otomatis menghasilkan eksekusi
                            yang hebat. 4DX menjembatani kesenjangan antara <em>perencanaan</em> dan
                            <em>hasil nyata</em> melalui 4 disiplin yang berurutan dan saling menguatkan.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- 4 Disiplin -->
        <section class="mb-8">
            <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">4 Disiplin Utama</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="d in DISIPLIN" :key="d.nomor" class="h-full">
                    <CardContent class="flex h-full flex-col">
                        <span class="text-3xl">{{ d.emoji }}</span>
                        <Badge variant="secondary" class="mt-3 w-fit">Disiplin {{ d.nomor }}</Badge>
                        <h3 class="mt-2 text-sm font-semibold">{{ d.judul }}</h3>
                        <p class="text-muted-foreground mt-2 text-sm">{{ d.ringkas }}</p>
                        <ul class="text-muted-foreground mt-3 space-y-1.5 text-sm">
                            <li v-for="butir in d.butir" :key="butir" class="flex gap-2">
                                <ChevronRight class="mt-0.5 size-3.5 shrink-0 opacity-50" />
                                <span>{{ butir }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Alur -->
        <section class="mb-8">
            <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Alur Monitoring di Sistem Ini</h2>
            <Card>
                <CardContent>
                    <div class="flex flex-col items-stretch gap-2 lg:flex-row lg:items-center">
                        <template v-for="(langkah, i) in ALUR" :key="langkah.judul">
                            <div class="bg-muted/50 flex-1 rounded-lg p-4 text-center">
                                <div class="text-2xl">{{ langkah.emoji }}</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ langkah.judul }}</p>
                                <p class="text-muted-foreground mt-0.5 text-xs">{{ langkah.ringkas }}</p>
                            </div>
                            <ChevronRight
                                v-if="i < ALUR.length - 1"
                                class="text-muted-foreground mx-auto size-5 shrink-0 rotate-90 opacity-50 lg:rotate-0"
                            />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Struktur data & status -->
        <section class="mb-8 grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Struktur Data Monitoring</h2>
                <Card class="overflow-hidden py-0">
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="w-36 pl-4">Entitas</TableHead>
                                        <TableHead>Keterangan</TableHead>
                                        <TableHead>Contoh</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="baris in STRUKTUR" :key="baris.entitas">
                                        <TableCell :class="cn('pl-4 text-sm font-semibold', baris.warna)">
                                            {{ baris.entitas }}
                                        </TableCell>
                                        <TableCell class="text-sm">{{ baris.keterangan }}</TableCell>
                                        <TableCell class="text-muted-foreground text-sm">{{ baris.contoh }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="lg:col-span-2">
                <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Indikator Status Capaian</h2>
                <Card class="h-[calc(100%-3.25rem)]">
                    <CardContent class="space-y-3">
                        <div
                            v-for="s in STATUS"
                            :key="s.label"
                            :class="cn('flex items-center gap-3 rounded-lg border p-3', s.kelas)"
                        >
                            <span :class="cn('flex size-9 shrink-0 items-center justify-center rounded-full bg-white/60', s.teks)">
                                <ChartNoAxesCombined class="size-4" />
                            </span>
                            <div>
                                <p :class="cn('text-sm font-semibold', s.teks)">{{ s.label }}</p>
                                <p class="text-muted-foreground text-sm">{{ s.ringkas }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Hak akses -->
        <section class="mb-8">
            <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Hak Akses per Role</h2>
            <Card class="overflow-hidden py-0">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="w-52 pl-4">Role</TableHead>
                                    <TableHead>Yang Bisa Dilakukan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="baris in AKSES" :key="baris.role">
                                    <TableCell class="pl-4">
                                        <Badge variant="secondary">{{ baris.role }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ baris.rincian }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Tips -->
        <section>
            <h2 class="border-primary mb-4 border-l-4 pl-3 text-lg font-semibold">Tips Eksekusi 4DX yang Efektif</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <Card v-for="tip in TIPS" :key="tip.judul">
                    <CardContent class="flex gap-3">
                        <component :is="tip.ikon" class="text-primary mt-0.5 size-5 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold">{{ tip.judul }}</p>
                            <p class="text-muted-foreground mt-1 text-sm">{{ tip.ringkas }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </AppLayout>
</template>
