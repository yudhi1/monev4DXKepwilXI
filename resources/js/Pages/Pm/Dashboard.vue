<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import {
    CalendarClock,
    ChevronRight,
    CircleAlert,
    FolderKanban,
    ListChecks,
    TrendingUp,
    TriangleAlert,
    Users,
} from '@lucide/vue';

defineProps({
    ringkasan: { type: Object, required: true },
    sebaran: { type: Array, required: true },
    projectAktif: { type: Array, required: true },
    perluPerhatian: { type: Array, required: true },
    opsi: { type: Object, required: true },
});

/*
 | Empat kartu ini sengaja bukan sekadar pencacah.
 |
 | Sebelumnya isinya Projects, Tasks, Completed, dan Overdue — dua di
 | antaranya hampir selalu 0 pada project yang baru jalan, dan "Tasks 12"
 | tidak memberi tahu apa pun yang bisa ditindaklanjuti. Yang dipilih di
 | sini menjawab pertanyaan berbeda: sejauh mana, punya saya berapa, apa
 | yang mendesak, dan apa yang sudah lewat.
 */
const KARTU = [
    {
        kunci: 'progres',
        label: 'Progres',
        icon: TrendingUp,
        akhiran: '%',
        tautan: '/pm/ringkasan?tampil=project',
        kartu: 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/40',
        ikon: 'bg-blue-500/15 text-blue-600 dark:text-blue-300',
        angka: 'text-blue-700 dark:text-blue-200',
    },
    {
        kunci: 'tugasSaya',
        label: 'Tugas Saya',
        icon: ListChecks,
        tautan: '/pm/tugas-saya',
        kartu: 'border-violet-200 bg-violet-50 dark:border-violet-900 dark:bg-violet-950/40',
        ikon: 'bg-violet-500/15 text-violet-600 dark:text-violet-300',
        angka: 'text-violet-700 dark:text-violet-200',
    },
    {
        kunci: 'jatuhTempo',
        label: 'Jatuh Tempo 7 Hari',
        icon: CalendarClock,
        tautan: '/pm/ringkasan?tampil=jatuh-tempo',
        kartu: 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40',
        ikon: 'bg-amber-500/15 text-amber-600 dark:text-amber-300',
        angka: 'text-amber-700 dark:text-amber-200',
    },
    {
        kunci: 'terlambat',
        label: 'Terlambat',
        icon: CircleAlert,
        tautan: '/pm/ringkasan?tampil=terlambat',
        kartu: 'border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/40',
        ikon: 'bg-rose-500/15 text-rose-600 dark:text-rose-300',
        angka: 'text-rose-700 dark:text-rose-200',
    },
];

const HEALTH = {
    on_track: { label: 'On Track', kelas: 'text-emerald-600' },
    at_risk: { label: 'At Risk', kelas: 'text-amber-600' },
    critical: { label: 'Critical', kelas: 'text-rose-600' },
};

/* Warna bilah sebaran mengikuti warna status di config/pm.php. */
const WARNA_SEBARAN = {
    slate: 'bg-slate-400',
    blue: 'bg-blue-500',
    amber: 'bg-amber-500',
    violet: 'bg-violet-500',
    emerald: 'bg-emerald-500',
};

/** Keterangan singkat di bawah tiap angka, supaya angkanya punya konteks. */
const keterangan = (kunci, r) => {
    if (kunci === 'progres') {
        return `${r.taskSelesai} dari ${r.task} tugas selesai`;
    }
    if (kunci === 'tugasSaya') {
        return 'belum selesai';
    }
    if (kunci === 'jatuhTempo') {
        return 'sepekan ke depan';
    }

    return r.terlambat > 0 ? 'perlu segera ditangani' : 'tidak ada yang lewat tenggat';
};

/* "3 hari lagi" lebih cepat dipahami daripada tanggal saat menakar urgensi. */
const sisa = (t) => {
    if (t.sisaHari === null || t.sisaHari === undefined) {
        return 'Tanpa tenggat';
    }
    if (t.sisaHari < 0) {
        return `Terlambat ${Math.abs(t.sisaHari)} hari`;
    }
    if (t.sisaHari === 0) {
        return 'Jatuh tempo hari ini';
    }

    return `${t.sisaHari} hari lagi`;
};

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Dashboard Project" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Ringkasan project yang sedang berjalan dan pekerjaan Anda.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/pm/projects">
                        <FolderKanban class="mr-1.5 size-4" />
                        Lihat Semua Project
                    </Link>
                </Button>
            </div>
        </template>

        <!-- Kartu statistik -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link v-for="k in KARTU" :key="k.kunci" :href="k.tautan" class="block">
                <Card :class="[k.kartu, 'h-full transition-shadow hover:shadow-md']">
                    <CardContent class="flex items-start gap-3 p-4">
                        <span :class="['flex size-10 shrink-0 items-center justify-center rounded-lg', k.ikon]">
                            <component :is="k.icon" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-muted-foreground text-xs tracking-wide uppercase">{{ k.label }}</p>
                            <p :class="['text-2xl leading-tight font-semibold tabular-nums', k.angka]">
                                {{ ringkasan[k.kunci] }}{{ k.akhiran ?? '' }}
                            </p>
                            <p class="text-muted-foreground mt-0.5 truncate text-xs">
                                {{ keterangan(k.kunci, ringkasan) }}
                            </p>
                        </div>
                        <ChevronRight class="text-muted-foreground mt-1 size-4 shrink-0" />
                    </CardContent>
                </Card>
            </Link>
        </div>

        <!--
          Sebaran status dalam satu bilah: menjawab "pekerjaan menumpuk di
          tahap mana" tanpa memakan ruang selayar grafik.
        -->
        <Card v-if="ringkasan.task > 0" class="mt-4">
            <CardContent class="p-4">
                <div class="mb-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="text-sm font-medium">Sebaran Tugas</span>
                    <span class="text-muted-foreground text-xs">{{ ringkasan.task }} tugas</span>
                </div>

                <div class="bg-secondary flex h-2.5 overflow-hidden rounded-full">
                    <div
                        v-for="b in sebaran.filter((x) => x.jumlah > 0)"
                        :key="b.kunci"
                        :class="WARNA_SEBARAN[b.warna] ?? 'bg-slate-400'"
                        :style="{ width: (b.jumlah / ringkasan.task) * 100 + '%' }"
                        :title="`${b.label}: ${b.jumlah}`"
                    />
                </div>

                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    <span
                        v-for="b in sebaran"
                        :key="b.kunci"
                        class="text-muted-foreground flex items-center gap-1.5 text-xs"
                    >
                        <span :class="['size-2 rounded-full', WARNA_SEBARAN[b.warna] ?? 'bg-slate-400']" />
                        {{ b.label }}
                        <span class="text-foreground font-medium tabular-nums">{{ b.jumlah }}</span>
                    </span>
                </div>
            </CardContent>
        </Card>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!--
              Tabel, bukan kartu: satu project satu baris supaya nama anggota,
              jumlah tugas, dan progres berjajar dan mudah dibandingkan.
            -->
            <div class="lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold tracking-wide uppercase">Project Aktif</h2>
                    <Link href="/pm/projects" class="text-primary text-xs font-medium">Semua</Link>
                </div>

                <Card class="overflow-hidden py-0">
                    <CardContent class="p-0">
                        <p v-if="projectAktif.length === 0" class="text-muted-foreground p-8 text-center text-sm">
                            Belum ada project aktif.
                        </p>

                        <div v-else class="gulir-terlihat overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="pl-4">Project</TableHead>
                                        <TableHead class="w-56">Anggota</TableHead>
                                        <TableHead class="w-24 text-center">Tugas</TableHead>
                                        <TableHead class="w-44">Progres</TableHead>
                                        <TableHead class="w-24 pr-4 text-right">Kondisi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="p in projectAktif" :key="p.id">
                                        <TableCell class="pl-4">
                                            <Link
                                                :href="`/pm/projects/${p.id}`"
                                                class="font-medium hover:underline"
                                            >
                                                {{ p.nama }}
                                            </Link>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                <span class="text-muted-foreground font-mono text-xs">
                                                    {{ p.kode }}
                                                </span>
                                                <Lencana :nilai="p.status" :peta="opsi.statusProject" />
                                                <Lencana :nilai="p.prioritas" :peta="opsi.prioritas" />
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            <span
                                                class="text-muted-foreground flex items-start gap-1 text-sm"
                                                :title="p.anggotas.join(', ')"
                                            >
                                                <Users class="mt-0.5 size-3 shrink-0" />
                                                <span class="line-clamp-2">
                                                    {{ p.anggotas.join(', ') || '—' }}
                                                </span>
                                            </span>
                                        </TableCell>

                                        <TableCell class="text-center text-sm tabular-nums">
                                            {{ p.jumlahSelesai }} / {{ p.jumlahTask }}
                                            <p class="text-muted-foreground text-xs">selesai</p>
                                        </TableCell>

                                        <TableCell><BilahProgress :nilai="p.progress" /></TableCell>

                                        <TableCell class="pr-4 text-right">
                                            <span
                                                :class="[
                                                    'text-xs font-medium whitespace-nowrap',
                                                    HEALTH[p.health]?.kelas,
                                                ]"
                                            >
                                                {{ HEALTH[p.health]?.label }}
                                            </span>
                                            <p class="text-muted-foreground text-xs whitespace-nowrap">
                                                {{ tanggal(p.tanggal_selesai) }}
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Perlu perhatian -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold tracking-wide uppercase">Perlu Perhatian</h2>
                    <Link href="/pm/tugas-saya" class="text-primary text-xs font-medium">Semua</Link>
                </div>

                <Card>
                    <CardContent class="p-0">
                        <p v-if="perluPerhatian.length === 0" class="text-muted-foreground p-8 text-center text-sm">
                            Tidak ada tugas terbuka untuk Anda.
                        </p>

                        <ul v-else class="divide-y">
                            <li v-for="t in perluPerhatian" :key="t.id" class="p-3">
                                <Link :href="`/pm/projects/${t.project.id}`" class="block">
                                    <div class="flex items-start gap-2">
                                        <TriangleAlert v-if="t.terlambat" class="mt-0.5 size-4 shrink-0 text-rose-600" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium">{{ t.judul }}</p>
                                            <p class="text-muted-foreground truncate text-xs">{{ t.project.nama }}</p>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                <Lencana :nilai="t.status" :peta="opsi.statusTask" />
                                                <span
                                                    :class="[
                                                        'text-xs',
                                                        t.terlambat
                                                            ? 'font-medium text-rose-600'
                                                            : t.sisaHari !== null && t.sisaHari <= 2
                                                              ? 'font-medium text-amber-600'
                                                              : 'text-muted-foreground',
                                                    ]"
                                                >
                                                    {{ sisa(t) }}
                                                </span>
                                            </div>
                                            <BilahProgress :nilai="t.progress" class="mt-2" />
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
