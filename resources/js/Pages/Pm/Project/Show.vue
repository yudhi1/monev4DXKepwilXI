<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    ArrowLeft,
    Building2,
    CalendarClock,
    Flag,
    MessageSquare,
    Paperclip,
    Pencil,
    Plus,
    Trash2,
    TriangleAlert,
    UserPlus,
} from '@lucide/vue';

const props = defineProps({
    project: { type: Object, required: true },
    papan: { type: Array, required: true },
    opsi: { type: Object, required: true },
    izin: { type: Object, required: true },
    kandidatAnggota: { type: Array, default: () => [] },
});

const HEALTH = {
    on_track: { label: 'On Track', kelas: 'text-emerald-600 bg-emerald-100 dark:bg-emerald-950' },
    at_risk: { label: 'At Risk', kelas: 'text-amber-600 bg-amber-100 dark:bg-amber-950' },
    critical: { label: 'Critical', kelas: 'text-rose-600 bg-rose-100 dark:bg-rose-950' },
};

const TAB = [
    { kunci: 'overview', label: 'Overview' },
    { kunci: 'tasks', label: 'Tasks' },
    { kunci: 'members', label: 'Members' },
    { kunci: 'timeline', label: 'Timeline' },
];

const tab = ref('tasks');

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const totalTask = computed(() => props.papan.reduce((n, kolom) => n + kolom.tasks.length, 0));

/* ================= Kanban: geser kartu ================= */

const kartuDiseret = ref(null);
const kolomSasaran = ref(null);

const mulaiSeret = (task, event) => {
    if (! props.izin.ubahProgress) {
        return;
    }
    kartuDiseret.value = task;
    event.dataTransfer.effectAllowed = 'move';
    // Firefox butuh payload agar drag dianggap sah.
    event.dataTransfer.setData('text/plain', String(task.id));
};

const seretMasuk = (kunciKolom) => {
    if (kartuDiseret.value) {
        kolomSasaran.value = kunciKolom;
    }
};

const jatuhkan = (kolom, indeks = null) => {
    const task = kartuDiseret.value;
    kartuDiseret.value = null;
    kolomSasaran.value = null;

    if (! task) {
        return;
    }

    const posisi = indeks ?? kolom.tasks.length;

    // Dijatuhkan tepat di tempat asalnya — tidak perlu request.
    if (task.status === kolom.kunci && task.urutan === posisi) {
        return;
    }

    router.patch(
        `/pm/projects/${props.project.id}/tasks/${task.id}/pindah`,
        { status: kolom.kunci, urutan: posisi },
        { preserveScroll: true, preserveState: false }
    );
};

/* ================= Form task ================= */

const dialogTask = ref(false);
const taskDiedit = ref(null);

const formTask = useForm({
    judul: '',
    deskripsi: '',
    status: 'backlog',
    prioritas: 'sedang',
    deadline: '',
    progress: 0,
    bobot: 1,
    milestone_id: null,
    assignees: [],
});

const bukaTambahTask = (statusAwal = 'backlog') => {
    taskDiedit.value = null;
    formTask.reset();
    formTask.clearErrors();
    formTask.status = statusAwal;
    dialogTask.value = true;
};

const bukaEditTask = (task) => {
    taskDiedit.value = task;
    formTask.clearErrors();
    formTask.judul = task.judul;
    formTask.deskripsi = task.deskripsi ?? '';
    formTask.status = task.status;
    formTask.prioritas = task.prioritas;
    formTask.deadline = task.deadline ?? '';
    formTask.progress = task.progress;
    formTask.bobot = task.bobot;
    formTask.milestone_id = task.milestone_id;
    formTask.assignees = task.assignees.map((a) => a.id);
    dialogTask.value = true;
};

const simpanTask = () => {
    const opsiKirim = {
        preserveScroll: true,
        onSuccess: () => {
            dialogTask.value = false;
            formTask.reset();
        },
    };

    if (taskDiedit.value) {
        formTask.put(`/pm/projects/${props.project.id}/tasks/${taskDiedit.value.id}`, opsiKirim);
    } else {
        formTask.post(`/pm/projects/${props.project.id}/tasks`, opsiKirim);
    }
};

const toggleAssignee = (userId, dipilih) => {
    formTask.assignees = dipilih
        ? [...formTask.assignees, userId]
        : formTask.assignees.filter((id) => id !== userId);
};

/* ================= Hapus task ================= */

const dialogHapus = ref(false);
const taskDihapus = ref(null);

const konfirmasiHapus = (task) => {
    taskDihapus.value = task;
    dialogHapus.value = true;
};

const hapusTask = () => {
    if (! taskDihapus.value) {
        return;
    }
    router.delete(`/pm/projects/${props.project.id}/tasks/${taskDihapus.value.id}`, {
        preserveScroll: true,
        onFinish: () => (taskDihapus.value = null),
    });
};

/* ================= Anggota ================= */

const formAnggota = useForm({ user_id: '', peran: 'member' });

const tambahAnggota = () =>
    formAnggota.post(`/pm/projects/${props.project.id}/anggota`, {
        preserveScroll: true,
        onSuccess: () => formAnggota.reset(),
    });

const ubahPeran = (anggota, peran) =>
    router.put(
        `/pm/projects/${props.project.id}/anggota/${anggota.id}`,
        { peran },
        { preserveScroll: true }
    );

const keluarkanAnggota = (anggota) =>
    router.delete(`/pm/projects/${props.project.id}/anggota/${anggota.id}`, { preserveScroll: true });

/* Kandidat yang belum menjadi anggota. */
const kandidatTersisa = computed(() => {
    const sudah = new Set(props.project.anggotas.map((a) => a.user_id));

    return props.kandidatAnggota.filter((k) => ! sudah.has(k.id));
});
</script>

<template>
    <Head :title="project.nama" />

    <AppLayout>
        <template #header>
            <div>
                <Link href="/pm/projects" class="text-muted-foreground hover:text-foreground mb-2 inline-flex items-center gap-1 text-sm">
                    <ArrowLeft class="size-4" />
                    Projects
                </Link>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-muted-foreground font-mono text-xs">{{ project.kode }}</span>
                            <Lencana :nilai="project.status" :peta="opsi.statusProject" />
                            <Lencana :nilai="project.prioritas" :peta="opsi.prioritas" />
                            <span
                                :class="[
                                    'rounded-md px-2 py-0.5 text-xs font-medium',
                                    HEALTH[project.health]?.kelas,
                                ]"
                            >
                                {{ HEALTH[project.health]?.label }}
                            </span>
                        </div>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ project.nama }}</h1>
                        <p v-if="project.unitKerja" class="text-muted-foreground mt-1 flex items-center gap-1 text-sm">
                            <Building2 class="size-3.5 shrink-0" />
                            {{ project.unitKerja }} &middot; {{ project.unitKerjaInduk }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ project.progress }}% selesai &middot; PM: {{ project.pemilik }} &middot;
                            {{ tanggal(project.tanggal_mulai) }} – {{ tanggal(project.tanggal_selesai) }}
                        </p>
                    </div>

                    <Button v-if="izin.kelolaTask" @click="bukaTambahTask()">
                        <Plus class="mr-1.5 size-4" />
                        Task Baru
                    </Button>
                </div>

                <!-- Tab -->
                <div class="mt-4 flex gap-1 border-b">
                    <button
                        v-for="t in TAB"
                        :key="t.kunci"
                        :class="[
                            '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                            tab === t.kunci
                                ? 'border-primary text-foreground'
                                : 'text-muted-foreground hover:text-foreground border-transparent',
                        ]"
                        @click="tab = t.kunci"
                    >
                        {{ t.label }}
                    </button>
                </div>
            </div>
        </template>

        <!-- ============ Overview ============ -->
        <div v-if="tab === 'overview'" class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardContent class="p-6">
                    <h2 class="mb-2 text-sm font-semibold tracking-wide uppercase">Deskripsi</h2>
                    <p class="text-muted-foreground text-sm whitespace-pre-line">
                        {{ project.deskripsi || 'Belum ada deskripsi.' }}
                    </p>

                    <h2 class="mt-6 mb-2 text-sm font-semibold tracking-wide uppercase">Progress</h2>
                    <BilahProgress :nilai="project.progress" tinggi="h-2.5" />
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-3 p-6 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Total task</span>
                        <span class="font-medium tabular-nums">{{ project.jumlahTask }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Selesai</span>
                        <span class="font-medium tabular-nums">{{ project.jumlahSelesai }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Terlambat</span>
                        <span
                            :class="['font-medium tabular-nums', project.jumlahTerlambat > 0 && 'text-rose-600']"
                        >
                            {{ project.jumlahTerlambat }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Anggota</span>
                        <span class="font-medium tabular-nums">{{ project.jumlahAnggota }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Peran Anda</span>
                        <Lencana :nilai="project.peranSaya" :peta="opsi.peran" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ============ Kanban ============ -->
        <div v-else-if="tab === 'tasks'">
            <p v-if="izin.ubahProgress" class="text-muted-foreground mb-3 text-xs">
                Seret kartu untuk memindahkannya antar kolom. Kartu yang masuk kolom
                <span class="font-medium">Done</span> otomatis menjadi 100%.
            </p>

            <!--
              Papan digulir mendatar dengan lebar kolom tetap. Grid tidak dipakai
              di sini karena kolom yang harus mempertahankan lebar minimum akan
              saling meluber begitu jumlah kolom melebihi lebar layar.
            -->
            <div class="gulir-terlihat flex gap-4 overflow-x-auto pb-3">
                <!-- Kolom dibiarkan meregang sama tinggi supaya area jatuhnya luas. -->
                <div
                    v-for="kolom in papan"
                    :key="kolom.kunci"
                    :class="[
                        'bg-muted/40 flex w-[17.5rem] shrink-0 flex-col rounded-lg border transition-colors',
                        kolomSasaran === kolom.kunci && 'border-primary bg-primary/5',
                    ]"
                    @dragover.prevent="seretMasuk(kolom.kunci)"
                    @drop.prevent="jatuhkan(kolom)"
                >
                    <div class="flex shrink-0 items-center justify-between gap-2 border-b px-3 py-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="truncate text-sm font-medium">{{ kolom.label }}</span>
                            <span
                                class="bg-secondary text-muted-foreground shrink-0 rounded px-1.5 text-xs tabular-nums"
                            >
                                {{ kolom.tasks.length }}
                            </span>
                        </div>
                        <button
                            v-if="izin.kelolaTask"
                            class="text-muted-foreground hover:text-foreground shrink-0"
                            :title="`Tambah task di ${kolom.label}`"
                            @click="bukaTambahTask(kolom.kunci)"
                        >
                            <Plus class="size-4" />
                        </button>
                    </div>

                    <div class="min-h-[6rem] flex-1 space-y-2 p-2">
                        <article
                            v-for="(task, i) in kolom.tasks"
                            :key="task.id"
                            :draggable="izin.ubahProgress"
                            :class="[
                                'bg-background group rounded-md border p-3 shadow-sm',
                                izin.ubahProgress && 'cursor-grab active:cursor-grabbing',
                                kartuDiseret?.id === task.id && 'opacity-50',
                            ]"
                            @dragstart="mulaiSeret(task, $event)"
                            @drop.stop.prevent="jatuhkan(kolom, i)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 flex-wrap gap-1">
                                    <Lencana :nilai="task.prioritas" :peta="opsi.prioritas" />
                                    <span
                                        v-if="task.milestone"
                                        class="bg-secondary text-muted-foreground inline-flex max-w-full items-center gap-1 rounded px-1.5 py-0.5 text-xs"
                                    >
                                        <Flag class="size-3 shrink-0" />
                                        <span class="truncate">{{ task.milestone }}</span>
                                    </span>
                                </div>

                                <div
                                    v-if="izin.kelolaTask"
                                    class="flex shrink-0 gap-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <button class="text-muted-foreground hover:text-foreground" @click="bukaEditTask(task)">
                                        <Pencil class="size-3.5" />
                                    </button>
                                    <button class="text-muted-foreground hover:text-destructive" @click="konfirmasiHapus(task)">
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </div>
                            </div>

                            <p class="mt-1.5 text-sm leading-snug font-medium break-words">{{ task.judul }}</p>
                            <p v-if="task.deskripsi" class="text-muted-foreground mt-0.5 line-clamp-2 text-xs break-words">
                                {{ task.deskripsi }}
                            </p>

                            <BilahProgress :nilai="task.progress" class="mt-2.5" />

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="flex min-w-0 -space-x-1.5">
                                    <span
                                        v-for="a in task.assignees"
                                        :key="a.id"
                                        :title="a.nama"
                                        class="bg-secondary ring-background flex size-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold ring-2"
                                    >
                                        {{ a.nama?.charAt(0).toUpperCase() }}
                                    </span>
                                    <span v-if="task.assignees.length === 0" class="text-muted-foreground truncate text-xs">
                                        Belum ada PIC
                                    </span>
                                </div>

                                <span
                                    v-if="task.deadline"
                                    :class="[
                                        'flex shrink-0 items-center gap-1 text-xs whitespace-nowrap',
                                        task.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                    ]"
                                >
                                    <TriangleAlert v-if="task.terlambat" class="size-3" />
                                    <CalendarClock v-else class="size-3" />
                                    {{ tanggal(task.deadline) }}
                                </span>
                            </div>
                        </article>

                        <p
                            v-if="kolom.tasks.length === 0"
                            class="text-muted-foreground px-2 py-6 text-center text-xs text-balance"
                        >
                            {{ kolom.keterangan }}
                        </p>
                    </div>
                </div>
            </div>

            <p v-if="totalTask === 0" class="text-muted-foreground mt-6 text-center text-sm">
                Project ini belum punya task.
            </p>
        </div>

        <!-- ============ Anggota ============ -->
        <div v-else-if="tab === 'members'" class="space-y-4">
            <Card v-if="izin.kelola && kandidatTersisa.length > 0">
                <CardContent class="flex flex-wrap items-end gap-3 p-4">
                    <div class="min-w-[14rem] flex-1 space-y-1.5">
                        <Label>Tambah anggota</Label>
                        <Select v-model="formAnggota.user_id">
                            <SelectTrigger><SelectValue placeholder="Pilih pegawai..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="k in kandidatTersisa" :key="k.id" :value="String(k.id)">
                                    {{ k.nama }}
                                    <span class="text-muted-foreground text-xs">
                                        — {{ k.unitKerja }}, {{ k.induk }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="w-44 space-y-1.5">
                        <Label>Peran</Label>
                        <Select v-model="formAnggota.peran">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(meta, kunci) in opsi.peran" :key="kunci" :value="kunci">
                                    {{ meta.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button :disabled="! formAnggota.user_id || formAnggota.processing" @click="tambahAnggota">
                        <UserPlus class="mr-1.5 size-4" />
                        Tambah
                    </Button>
                </CardContent>
            </Card>

            <Card class="overflow-hidden py-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="pl-4">Nama</TableHead>
                                <TableHead>Unit Kerja</TableHead>
                                <TableHead class="w-48">Peran</TableHead>
                                <TableHead class="w-32 text-right">Kontribusi</TableHead>
                                <TableHead v-if="izin.kelola" class="w-20 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="a in project.anggotas" :key="a.id">
                                <TableCell class="pl-4">
                                    <p class="font-medium">{{ a.nama }}</p>
                                    <p v-if="a.jabatan" class="text-muted-foreground text-xs">{{ a.jabatan }}</p>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    <template v-if="a.unitKerja">
                                        <p class="text-sm">{{ a.unitKerja }}</p>
                                        <p class="text-xs">{{ a.induk }}</p>
                                    </template>
                                    <span v-else class="text-sm">{{ a.email }}</span>
                                </TableCell>
                                <TableCell>
                                    <Select
                                        v-if="izin.kelola"
                                        :model-value="a.peran"
                                        @update:model-value="(v) => ubahPeran(a, v)"
                                    >
                                        <SelectTrigger class="h-8"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="(meta, kunci) in opsi.peran" :key="kunci" :value="kunci">
                                                {{ meta.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Lencana v-else :nilai="a.peran" :peta="opsi.peran" />
                                </TableCell>
                                <TableCell class="text-right tabular-nums">{{ a.kontribusi }}%</TableCell>
                                <TableCell v-if="izin.kelola" class="pr-4 text-right">
                                    <button
                                        class="text-muted-foreground hover:text-destructive"
                                        @click="keluarkanAnggota(a)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <p class="text-muted-foreground text-xs">
                Kontribusi = Σ(bobot task × progress) dibagi total bobot project. Task dengan
                beberapa PIC dibagi rata di antara mereka.
            </p>
        </div>

        <!-- ============ Timeline ============ -->
        <div v-else-if="tab === 'timeline'">
            <Card v-if="project.milestones.length === 0">
                <CardContent class="text-muted-foreground p-12 text-center text-sm">
                    Belum ada milestone pada project ini.
                </CardContent>
            </Card>

            <ol v-else class="relative space-y-4 border-l pl-6">
                <li v-for="m in project.milestones" :key="m.id" class="relative">
                    <span class="bg-primary ring-background absolute top-1.5 -left-[1.65rem] size-3 rounded-full ring-4" />
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-medium">{{ m.nama }}</p>
                                <span class="text-muted-foreground text-xs">{{ tanggal(m.target_tanggal) }}</span>
                            </div>
                            <p v-if="m.deskripsi" class="text-muted-foreground mt-1 text-sm">{{ m.deskripsi }}</p>
                            <p class="text-muted-foreground mt-2 text-xs">{{ m.jumlahTask }} task</p>
                        </CardContent>
                    </Card>
                </li>
            </ol>

            <p class="text-muted-foreground mt-6 flex items-center gap-2 text-xs">
                <Paperclip class="size-3.5" />
                Tab Files dan Activity menyusul pada tahap Kolaborasi.
                <MessageSquare class="size-3.5" />
            </p>
        </div>

        <!-- ============ Dialog task ============ -->
        <Dialog v-model:open="dialogTask">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ taskDiedit ? 'Ubah Task' : 'Task Baru' }}</DialogTitle>
                    <DialogDescription>
                        Bobot menentukan porsi task ini terhadap progres dan kontribusi project.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpanTask">
                    <div class="space-y-1.5">
                        <Label for="judul">Judul</Label>
                        <Input id="judul" v-model="formTask.judul" />
                        <p v-if="formTask.errors.judul" class="text-destructive text-sm">{{ formTask.errors.judul }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="deskripsi-task">Deskripsi</Label>
                        <Textarea id="deskripsi-task" v-model="formTask.deskripsi" rows="3" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label>Status</Label>
                            <Select v-model="formTask.status">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(meta, kunci) in opsi.statusTask" :key="kunci" :value="kunci">
                                        {{ meta.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Prioritas</Label>
                            <Select v-model="formTask.prioritas">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                                        {{ meta.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="deadline">Deadline</Label>
                            <Input id="deadline" v-model="formTask.deadline" type="date" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label for="progress">Progress (%)</Label>
                            <Input id="progress" v-model="formTask.progress" type="number" min="0" max="100" />
                            <p v-if="formTask.errors.progress" class="text-destructive text-sm">
                                {{ formTask.errors.progress }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="bobot">Bobot</Label>
                            <Input id="bobot" v-model="formTask.bobot" type="number" min="0" step="0.5" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Milestone</Label>
                            <Select
                                :model-value="formTask.milestone_id ? String(formTask.milestone_id) : 'tanpa'"
                                @update:model-value="(v) => (formTask.milestone_id = v === 'tanpa' ? null : Number(v))"
                            >
                                <SelectTrigger><SelectValue placeholder="Tanpa milestone" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tanpa">Tanpa milestone</SelectItem>
                                    <SelectItem v-for="m in project.milestones" :key="m.id" :value="String(m.id)">
                                        {{ m.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>PIC / Assignee</Label>
                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-md border p-3">
                            <label
                                v-for="a in project.anggotas"
                                :key="a.user_id"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    :model-value="formTask.assignees.includes(a.user_id)"
                                    @update:model-value="(v) => toggleAssignee(a.user_id, v)"
                                />
                                {{ a.nama }}
                                <span class="text-muted-foreground text-xs">({{ opsi.peran[a.peran]?.label }})</span>
                            </label>
                            <p v-if="project.anggotas.length === 0" class="text-muted-foreground text-sm">
                                Tambahkan anggota project terlebih dahulu.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogTask = false">Batal</Button>
                        <Button type="submit" :disabled="formTask.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ============ Konfirmasi hapus ============ -->
        <AlertDialog v-model:open="dialogHapus">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus task ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Task <span class="font-medium">{{ taskDihapus?.judul }}</span> akan dihapus permanen
                        beserta penugasan PIC-nya.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="hapusTask">Hapus</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
