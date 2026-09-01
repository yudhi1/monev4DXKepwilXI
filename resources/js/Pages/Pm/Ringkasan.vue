<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    ArrowLeft,
    ChevronDown,
    ChevronRight,
    CircleAlert,
    CircleCheckBig,
    FolderKanban,
    ListChecks,
    TriangleAlert,
    UserRound,
} from '@lucide/vue';

defineProps({
    tampil: { type: String, required: true },
    meta: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
    jumlah: { type: Object, required: true },
    opsi: { type: Object, required: true },
});

/* Tab mengikuti keempat kartu di dashboard, termasuk warnanya. */
const TAB = [
    {
        kunci: 'project',
        label: 'Projects',
        icon: FolderKanban,
        aktif: 'border-blue-500 text-blue-700 dark:text-blue-300',
    },
    {
        kunci: 'task',
        label: 'Tasks',
        icon: ListChecks,
        aktif: 'border-violet-500 text-violet-700 dark:text-violet-300',
    },
    {
        kunci: 'selesai',
        label: 'Completed',
        icon: CircleCheckBig,
        aktif: 'border-emerald-500 text-emerald-700 dark:text-emerald-300',
    },
    {
        kunci: 'terlambat',
        label: 'Overdue',
        icon: CircleAlert,
        aktif: 'border-rose-500 text-rose-700 dark:text-rose-300',
    },
];

const HEALTH = {
    on_track: { label: 'On Track', kelas: 'text-emerald-600' },
    at_risk: { label: 'At Risk', kelas: 'text-amber-600' },
    critical: { label: 'Critical', kelas: 'text-rose-600' },
};

const ganti = (kunci) => router.get('/pm/ringkasan', { tampil: kunci }, { preserveState: false });

/*
 | Semua project terbuka pada mulanya — yang dicari user justru task di
 | dalamnya, jadi yang dicatat adalah mana yang sengaja dilipat.
 */
const tertutup = ref(new Set());

const toggle = (id) => {
    const baru = new Set(tertutup.value);

    if (baru.has(id)) {
        baru.delete(id);
    } else {
        baru.add(id);
    }

    tertutup.value = baru;
};

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const angka = (nilai) => (nilai === null || nilai === undefined ? '—' : Number(nilai).toLocaleString('id-ID'));
</script>

<template>
    <Head :title="meta.judul" />

    <AppLayout>
        <template #header>
            <div>
                <Link
                    href="/pm"
                    class="text-muted-foreground hover:text-foreground mb-2 inline-flex items-center gap-1 text-sm"
                >
                    <ArrowLeft class="size-4" />
                    Dashboard
                </Link>

                <h1 class="text-2xl font-semibold tracking-tight">{{ meta.judul }}</h1>
                <p class="text-muted-foreground mt-1 text-sm">{{ meta.keterangan }}</p>

                <!-- Tab memakai warna yang sama dengan kartu asalnya di dashboard. -->
                <div class="mt-4 flex flex-wrap gap-1 border-b">
                    <button
                        v-for="t in TAB"
                        :key="t.kunci"
                        :class="[
                            '-mb-px flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                            tampil === t.kunci
                                ? t.aktif
                                : 'text-muted-foreground hover:text-foreground border-transparent',
                        ]"
                        @click="ganti(t.kunci)"
                    >
                        <component :is="t.icon" class="size-4" />
                        {{ t.label }}
                        <span class="bg-secondary text-muted-foreground rounded px-1.5 text-xs tabular-nums">
                            {{ jumlah[t.kunci] }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <!-- ============ Project beserta task di dalamnya ============ -->
        <div v-if="tampil === 'project'" class="space-y-3">
            <Card v-if="projects.length === 0">
                <CardContent class="text-muted-foreground p-12 text-center text-sm">
                    Belum ada project yang bisa Anda lihat.
                </CardContent>
            </Card>

            <Card v-for="p in projects" :key="p.id" class="overflow-hidden py-0">
                <!-- Kepala project: klik untuk melipat daftar task-nya -->
                <button
                    class="hover:bg-secondary/40 w-full px-4 py-3 text-left transition-colors"
                    @click="toggle(p.id)"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <component
                            :is="tertutup.has(p.id) ? ChevronRight : ChevronDown"
                            class="text-muted-foreground size-4 shrink-0"
                        />
                        <span class="text-muted-foreground font-mono text-xs">{{ p.kode }}</span>
                        <span class="font-medium">{{ p.nama }}</span>
                        <Lencana :nilai="p.status" :peta="opsi.statusProject" />
                        <span :class="['ml-auto text-xs font-medium', HEALTH[p.health]?.kelas]">
                            {{ HEALTH[p.health]?.label }}
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <BilahProgress :nilai="p.progress" class="max-w-xs" />
                        <span class="text-muted-foreground text-xs">
                            {{ p.jumlahSelesai }} / {{ p.jumlahTask }} task selesai
                        </span>
                        <span v-if="p.jumlahTerlambat > 0" class="text-xs font-medium text-rose-600">
                            {{ p.jumlahTerlambat }} terlambat
                        </span>
                        <span v-if="p.unitKerja" class="text-muted-foreground text-xs">{{ p.unitKerja }}</span>
                    </div>
                </button>

                <CardContent v-if="! tertutup.has(p.id)" class="border-t p-0">
                    <p v-if="p.tasks.length === 0" class="text-muted-foreground p-6 text-center text-sm">
                        Project ini belum punya task.
                    </p>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="pl-4">Task</TableHead>
                                    <TableHead class="w-32">Status</TableHead>
                                    <TableHead class="w-44">Progress</TableHead>
                                    <TableHead class="w-48">PIC</TableHead>
                                    <TableHead class="w-36 pr-4">Deadline</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="t in p.tasks" :key="t.id">
                                    <TableCell class="pl-4">
                                        <p class="font-medium">{{ t.judul }}</p>
                                        <p v-if="t.milestone" class="text-muted-foreground text-xs">
                                            {{ t.milestone }}
                                        </p>
                                    </TableCell>
                                    <TableCell><Lencana :nilai="t.status" :peta="opsi.statusTask" /></TableCell>
                                    <TableCell>
                                        <BilahProgress :nilai="t.progress" />
                                        <p
                                            v-if="t.pakaiTarget"
                                            class="text-muted-foreground mt-0.5 text-xs tabular-nums"
                                        >
                                            {{ angka(t.realisasi) }} / {{ angka(t.target) }} {{ t.satuan }}
                                        </p>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground text-sm">
                                        {{ t.assignees.join(', ') || '—' }}
                                    </TableCell>
                                    <TableCell class="pr-4">
                                        <span
                                            :class="[
                                                'flex items-center gap-1 text-sm whitespace-nowrap',
                                                t.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                            ]"
                                        >
                                            <TriangleAlert v-if="t.terlambat" class="size-3.5" />
                                            {{ tanggal(t.deadline) }}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ============ Daftar task rata, dengan kolom project asalnya ============ -->
        <Card v-else class="overflow-hidden py-0">
            <CardContent class="p-0">
                <p v-if="tasks.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                    Tidak ada task pada kelompok ini.
                </p>

                <div v-else class="gulir-terlihat overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="pl-4">Task</TableHead>
                                <TableHead class="w-64">Project</TableHead>
                                <TableHead class="w-32">Status</TableHead>
                                <TableHead class="w-28">Prioritas</TableHead>
                                <TableHead class="w-44">Progress</TableHead>
                                <TableHead class="w-48">PIC</TableHead>
                                <TableHead class="w-36 pr-4">Deadline</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="t in tasks" :key="t.id">
                                <TableCell class="pl-4">
                                    <p class="font-medium">{{ t.judul }}</p>
                                    <p v-if="t.milestone" class="text-muted-foreground text-xs">{{ t.milestone }}</p>
                                </TableCell>
                                <TableCell>
                                    <Link :href="'/pm/projects/' + t.project.id" class="text-primary text-sm">
                                        {{ t.project.nama }}
                                    </Link>
                                    <p class="text-muted-foreground font-mono text-xs">{{ t.project.kode }}</p>
                                </TableCell>
                                <TableCell><Lencana :nilai="t.status" :peta="opsi.statusTask" /></TableCell>
                                <TableCell><Lencana :nilai="t.prioritas" :peta="opsi.prioritas" /></TableCell>
                                <TableCell>
                                    <BilahProgress :nilai="t.progress" />
                                    <p v-if="t.pakaiTarget" class="text-muted-foreground mt-0.5 text-xs tabular-nums">
                                        {{ angka(t.realisasi) }} / {{ angka(t.target) }} {{ t.satuan }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <span class="text-muted-foreground flex items-center gap-1 text-sm">
                                        <UserRound class="size-3 shrink-0" />
                                        {{ t.assignees.join(', ') || '—' }}
                                    </span>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <span
                                        :class="[
                                            'flex items-center gap-1 text-sm whitespace-nowrap',
                                            t.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                        ]"
                                    >
                                        <TriangleAlert v-if="t.terlambat" class="size-3.5" />
                                        {{ tanggal(t.deadline) }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
