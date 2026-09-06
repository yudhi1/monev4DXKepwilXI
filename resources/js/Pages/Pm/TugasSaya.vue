<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Download, TriangleAlert, UserRound } from '@lucide/vue';

const props = defineProps({
    tasks: { type: Array, required: true },
    filter: { type: Object, required: true },
    bisaLihatTim: { type: Boolean, default: false },
    daftarPic: { type: Array, default: () => [] },
    daftarProject: { type: Array, default: () => [] },
    opsi: { type: Object, required: true },
});

const lingkup = ref(props.filter.lingkup ?? 'saya');
const status = ref(props.filter.status || 'semua');
const prioritas = ref(props.filter.prioritas || 'semua');
const pic = ref(props.filter.pic ? String(props.filter.pic) : 'semua');
const project = ref(props.filter.project ? String(props.filter.project) : 'semua');

/* Satu tempat merakit parameter, dipakai penyaringan maupun tautan ekspor. */
const parameter = computed(() => ({
    lingkup: lingkup.value === 'saya' ? undefined : 'tim',
    status: status.value === 'semua' ? undefined : status.value,
    prioritas: prioritas.value === 'semua' ? undefined : prioritas.value,
    pic: lingkup.value === 'tim' && pic.value !== 'semua' ? pic.value : undefined,
    project: lingkup.value === 'tim' && project.value !== 'semua' ? project.value : undefined,
}));

watch([lingkup, status, prioritas, pic, project], () =>
    router.get('/pm/tugas-saya', parameter.value, { preserveState: true, replace: true })
);

/* Berkas harus mengikuti saringan yang sedang tampil, jadi ikut membawa parameter. */
const tautanEkspor = computed(() => {
    const q = Object.entries(parameter.value)
        .filter(([, v]) => v !== undefined)
        .map(([k, v]) => `${k}=${encodeURIComponent(v)}`)
        .join('&');

    return '/pm/tugas-saya/ekspor' + (q ? `?${q}` : '');
});

const judul = computed(() => (lingkup.value === 'tim' ? 'Tugas Tim' : 'Tugas Saya'));

/*
 | Warna sisa waktu: merah bila lewat, kuning bila tinggal tiga hari atau
 | kurang. Ambang tiga hari dipilih agar yang mendesak menonjol tanpa membuat
 | seluruh kolom berwarna.
 */
const warnaSisa = (t) => {
    if (t.status === 'done') {
        return 'text-muted-foreground';
    }

    if (t.terlambat) {
        return 'font-medium text-rose-600';
    }

    if (t.sisaHari !== null && t.sisaHari <= 3) {
        return 'font-medium text-amber-600';
    }

    return 'text-muted-foreground';
};

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const angka = (nilai) => (nilai === null || nilai === undefined ? '—' : Number(nilai).toLocaleString('id-ID'));
</script>

<template>
    <Head :title="judul" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ judul }}</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        <template v-if="lingkup === 'tim'">
                            Seluruh tugas pada project yang Anda kelola, siapa pun PIC-nya.
                        </template>
                        <template v-else>
                            Tugas yang ditugaskan kepada Anda, lintas project. Yang paling dekat
                            tenggatnya berada di atas.
                        </template>
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <!--
                      Unduhan berkas harus lewat <a>, bukan <Link> Inertia:
                      Inertia menunggu balasan JSON dan berkas Excel tidak
                      akan pernah tersimpan.
                    -->
                    <a :href="tautanEkspor">
                        <Download class="mr-1.5 size-4" />
                        Ekspor Excel
                    </a>
                </Button>
            </div>
        </template>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <!-- Pengalih lingkup hanya muncul bagi yang memang mengelola project. -->
            <Select v-if="bisaLihatTim" v-model="lingkup">
                <SelectTrigger class="w-44"><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="saya">Tugas saya</SelectItem>
                    <SelectItem value="tim">Tugas tim</SelectItem>
                </SelectContent>
            </Select>

            <Select v-if="lingkup === 'tim'" v-model="project">
                <SelectTrigger class="w-56"><SelectValue placeholder="Semua project" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua project</SelectItem>
                    <SelectItem v-for="p in daftarProject" :key="p.id" :value="String(p.id)">
                        {{ p.nama }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-if="lingkup === 'tim'" v-model="pic">
                <SelectTrigger class="w-48"><SelectValue placeholder="Semua PIC" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua PIC</SelectItem>
                    <SelectItem v-for="o in daftarPic" :key="o.id" :value="String(o.id)">
                        {{ o.nama }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="status">
                <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua status</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.statusTask" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="prioritas">
                <SelectTrigger class="w-40"><SelectValue placeholder="Prioritas" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua prioritas</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <span class="text-muted-foreground ml-auto text-sm">{{ tasks.length }} tugas</span>
        </div>

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <p v-if="tasks.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                    Tidak ada tugas yang cocok dengan saringan ini.
                </p>

                <div v-else class="gulir-terlihat overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-12 pl-4">#</TableHead>
                                <TableHead>Tugas</TableHead>
                                <TableHead class="w-52">Project</TableHead>
                                <TableHead v-if="lingkup === 'tim'" class="w-40">PIC</TableHead>
                                <TableHead class="w-32">Status</TableHead>
                                <TableHead class="w-28">Prioritas</TableHead>
                                <TableHead class="w-44">Progres</TableHead>
                                <TableHead class="w-36">Tenggat</TableHead>
                                <TableHead class="w-36 pr-4">Sisa Waktu</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(t, index) in tasks" :key="t.id">
                                <TableCell class="pl-4">
                                    <span
                                        class="bg-primary/10 text-primary inline-flex size-7 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                </TableCell>

                                <TableCell>
                                    <p class="font-medium">{{ t.judul }}</p>
                                    <p v-if="t.milestone" class="text-muted-foreground text-xs">{{ t.milestone }}</p>
                                </TableCell>

                                <TableCell>
                                    <Link :href="`/pm/projects/${t.project.id}`" class="text-primary text-sm">
                                        {{ t.project.nama }}
                                    </Link>
                                    <p class="text-muted-foreground font-mono text-xs">{{ t.project.kode }}</p>
                                </TableCell>

                                <TableCell v-if="lingkup === 'tim'" class="text-muted-foreground text-sm">
                                    <span class="flex items-center gap-1">
                                        <UserRound class="size-3 shrink-0" />
                                        <span class="truncate">{{ t.pic.join(', ') || '—' }}</span>
                                    </span>
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

                                <!--
                                  Jarak ke tenggat dieja, bukan dibiarkan
                                  dihitung sendiri dari tanggal. Kalimatnya
                                  datang dari server supaya sama persis dengan
                                  hasil export.
                                -->
                                <TableCell class="pr-4">
                                    <span
                                        :class="[
                                            'text-sm whitespace-nowrap',
                                            warnaSisa(t),
                                        ]"
                                    >
                                        {{ t.keteranganTenggat }}
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
