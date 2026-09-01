<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    CalendarClock,
    CircleAlert,
    CircleCheckBig,
    FolderKanban,
    ListChecks,
    TriangleAlert,
} from '@lucide/vue';

defineProps({
    ringkasan: { type: Object, required: true },
    projectAktif: { type: Array, required: true },
    tugasSaya: { type: Array, required: true },
    opsi: { type: Object, required: true },
});

/*
 | Tiap kartu statistik diberi rona warnanya sendiri supaya keempatnya bisa
 | dibedakan sekilas tanpa membaca labelnya. Warnanya dijaga tetap lembut
 | (tingkat 50/100) agar angka tetap jadi bagian yang paling menonjol, bukan
 | latarnya.
 |
 | Kelas ditulis utuh, bukan dirangkai seperti `bg-${warna}-50`, karena
 | Tailwind memindai berkas sebagai teks — nama kelas hasil rangkaian tidak
 | akan pernah ikut dibuatkan CSS-nya.
 */
const KARTU = [
    {
        kunci: 'project',
        label: 'Projects',
        icon: FolderKanban,
        kartu: 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/40',
        ikon: 'bg-blue-500/15 text-blue-600 dark:text-blue-300',
        angka: 'text-blue-700 dark:text-blue-200',
    },
    {
        kunci: 'task',
        label: 'Tasks',
        icon: ListChecks,
        kartu: 'border-violet-200 bg-violet-50 dark:border-violet-900 dark:bg-violet-950/40',
        ikon: 'bg-violet-500/15 text-violet-600 dark:text-violet-300',
        angka: 'text-violet-700 dark:text-violet-200',
    },
    {
        kunci: 'taskSelesai',
        label: 'Completed',
        icon: CircleCheckBig,
        kartu: 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40',
        ikon: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300',
        angka: 'text-emerald-700 dark:text-emerald-200',
    },
    {
        kunci: 'taskTerlambat',
        label: 'Overdue',
        icon: CircleAlert,
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
            <Card v-for="k in KARTU" :key="k.kunci" :class="k.kartu">
                <CardContent class="flex items-center gap-3 p-4">
                    <span :class="['flex size-10 shrink-0 items-center justify-center rounded-lg', k.ikon]">
                        <component :is="k.icon" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-muted-foreground text-xs tracking-wide uppercase">{{ k.label }}</p>
                        <p :class="['text-2xl leading-tight font-semibold tabular-nums', k.angka]">
                            {{ ringkasan[k.kunci] }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!-- Project aktif -->
            <div class="lg:col-span-2">
                <h2 class="mb-3 text-sm font-semibold tracking-wide uppercase">Project Aktif</h2>

                <div v-if="projectAktif.length === 0">
                    <Card>
                        <CardContent class="text-muted-foreground p-8 text-center text-sm">
                            Belum ada project aktif.
                        </CardContent>
                    </Card>
                </div>

                <div v-else class="space-y-3">
                    <Link v-for="p in projectAktif" :key="p.id" :href="`/pm/projects/${p.id}`" class="block">
                        <Card class="transition-shadow hover:shadow-md">
                            <CardContent class="p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-muted-foreground font-mono text-xs">{{ p.kode }}</span>
                                            <Lencana :nilai="p.status" :peta="opsi.statusProject" />
                                            <Lencana :nilai="p.prioritas" :peta="opsi.prioritas" />
                                        </div>
                                        <p class="mt-1 truncate font-medium">{{ p.nama }}</p>
                                    </div>
                                    <span :class="['text-xs font-medium', HEALTH[p.health]?.kelas]">
                                        {{ HEALTH[p.health]?.label }}
                                    </span>
                                </div>

                                <BilahProgress :nilai="p.progress" class="mt-3" />

                                <div class="text-muted-foreground mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                    <span>{{ p.jumlahSelesai }} / {{ p.jumlahTask }} task selesai</span>
                                    <span>{{ p.jumlahAnggota }} anggota</span>
                                    <span class="flex items-center gap-1">
                                        <CalendarClock class="size-3" />
                                        {{ tanggal(p.tanggal_selesai) }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>

            <!-- Tugas saya -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold tracking-wide uppercase">Tugas Saya</h2>
                    <Link href="/pm/tugas-saya" class="text-primary text-xs font-medium">Semua</Link>
                </div>

                <Card>
                    <CardContent class="p-0">
                        <p v-if="tugasSaya.length === 0" class="text-muted-foreground p-8 text-center text-sm">
                            Tidak ada tugas terbuka untuk Anda.
                        </p>

                        <ul v-else class="divide-y">
                            <li v-for="t in tugasSaya" :key="t.id" class="p-3">
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
                                                        t.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                                    ]"
                                                >
                                                    {{ tanggal(t.deadline) }}
                                                </span>
                                            </div>
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
