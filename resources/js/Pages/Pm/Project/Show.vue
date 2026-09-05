<script setup>
import { computed, ref, watch } from 'vue';
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
    GripVertical,
    MessageSquare,
    Paperclip,
    Pencil,
    Plus,
    Trash2,
    TriangleAlert,
    UserPlus,
    UserRound,
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

/* Daftar nama PIC sebuah task, dipisah koma. */
const namaPic = (task) => (task.assignees ?? []).map((a) => a.nama).join(', ');

/* ================= Daftar task ================= */

/*
 | Tabel, tetapi dikelompokkan per status seperti papan Kanban dulu:
 | tiap status punya baris judul sendiri, dan barisnya bisa diseret.
 |
 | Menyeret ke kelompok lain = pindah status. Menyeret di dalam kelompok
 | yang sama = ubah urutan. Keduanya memakai rute `pindah` yang sama.
 */
const totalKolomTabel = computed(() => (props.izin.kelolaTask ? 9 : 8));

/* Nomor urut berjalan menembus kelompok, jadi dihitung sekali di depan. */
const nomorTask = computed(() => {
    const peta = new Map();
    let n = 0;

    props.papan.forEach((kolom) => kolom.tasks.forEach((t) => peta.set(t.id, ++n)));

    return peta;
});

const taskDiseret = ref(null);
const barisSasaran = ref(null);
const kolomSasaran = ref(null);

const mulaiSeret = (task, event) => {
    if (! props.izin.ubahProgress) {
        return;
    }

    taskDiseret.value = task;
    event.dataTransfer.effectAllowed = 'move';
    // Firefox mengabaikan drag tanpa payload.
    event.dataTransfer.setData('text/plain', String(task.id));
};

const akhiriSeret = () => {
    taskDiseret.value = null;
    barisSasaran.value = null;
    kolomSasaran.value = null;
};

const seretDiAtasBaris = (task) => {
    if (taskDiseret.value && taskDiseret.value.id !== task.id) {
        barisSasaran.value = task.id;
        kolomSasaran.value = null;
    }
};

const seretDiAtasKolom = (kunci) => {
    if (taskDiseret.value) {
        kolomSasaran.value = kunci;
        barisSasaran.value = null;
    }
};

/** Kirim perpindahan; `urutan` menentukan posisi di dalam status tujuan. */
const pindahkan = (task, status, urutan) => {
    akhiriSeret();

    if (task.status === status && task.urutan === urutan) {
        return;
    }

    router.patch(
        `/pm/projects/${props.project.id}/tasks/${task.id}/pindah`,
        { status, urutan },
        { preserveScroll: true, preserveState: false }
    );
};

/* Dijatuhkan di atas sebuah baris: ambil status dan posisi baris itu. */
const jatuhkanDiBaris = (sasaran) => {
    const task = taskDiseret.value;

    if (task && task.id !== sasaran.id) {
        pindahkan(task, sasaran.status, sasaran.urutan);
    } else {
        akhiriSeret();
    }
};

/* Dijatuhkan di kepala kelompok atau area kosongnya: taruh di dasar. */
const jatuhkanDiKolom = (kolom) => {
    const task = taskDiseret.value;

    if (task) {
        pindahkan(task, kolom.kunci, kolom.tasks.length);
    } else {
        akhiriSeret();
    }
};

/* Dropdown status tetap ada sebagai jalur yang bisa dipakai lewat papan ketik. */
const pindahStatus = (task, status) => {
    const tujuan = props.papan.find((k) => k.kunci === status);

    pindahkan(task, status, tujuan ? tujuan.tasks.length : 0);
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
    satuan: null,
    target: '',
    realisasi: '',
    milestone_id: null,
    assignees: [],
});

/* Task yang diukur angka: progress tidak lagi diisi tangan. */
const formPakaiTarget = computed(() => Number(formTask.target) > 0);

const progressHitungan = computed(() => {
    if (! formPakaiTarget.value) {
        return null;
    }

    const persen = (Number(formTask.realisasi) || 0) / Number(formTask.target) * 100;

    return Math.round(Math.max(0, Math.min(100, persen)));
});

/* Progress ikut angka realisasi supaya yang tersimpan tidak bertentangan. */
watch(progressHitungan, (nilai) => {
    if (nilai !== null) {
        formTask.progress = nilai;
    }
});

/* Angka besar seperti rupiah sulit dibaca tanpa pemisah ribuan. */
const angka = (nilai) => (nilai === null || nilai === '' ? '—' : Number(nilai).toLocaleString('id-ID'));

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
    formTask.satuan = task.satuan;
    formTask.target = task.target ?? '';
    formTask.realisasi = task.realisasi ?? '';
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

        <!-- ============ Daftar task ============ -->
        <div v-else-if="tab === 'tasks'">
            <p v-if="izin.ubahProgress && totalTask > 0" class="text-muted-foreground mb-3 text-xs">
                Seret baris ke kelompok lain untuk memindah statusnya, atau ke posisi lain di
                kelompok yang sama untuk mengubah urutan. Bisa juga lewat kolom Status.
            </p>

            <Card class="overflow-hidden py-0">
                <CardContent class="p-0">
                    <p v-if="totalTask === 0" class="text-muted-foreground p-12 text-center text-sm">
                        Project ini belum punya task.
                    </p>

                    <div v-else class="gulir-terlihat overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead v-if="izin.ubahProgress" class="w-10 pl-4" />
                                    <TableHead class="w-14" :class="izin.ubahProgress ? '' : 'pl-4'">#</TableHead>
                                    <TableHead>Tugas</TableHead>
                                    <TableHead class="w-44">Status</TableHead>
                                    <TableHead class="w-28">Prioritas</TableHead>
                                    <TableHead class="w-48">Progres</TableHead>
                                    <TableHead class="w-44">PIC</TableHead>
                                    <TableHead class="w-32">Tenggat</TableHead>
                                    <TableHead v-if="izin.kelolaTask" class="w-24 pr-4 text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <template v-for="kolom in papan" :key="kolom.kunci">
                                    <!-- Kepala kelompok; menjatuhkan di sini menaruh task di dasar. -->
                                    <TableRow
                                        :class="[
                                            'bg-muted/50 hover:bg-muted/50',
                                            kolomSasaran === kolom.kunci && 'bg-primary/10 hover:bg-primary/10',
                                        ]"
                                        @dragover.prevent="seretDiAtasKolom(kolom.kunci)"
                                        @drop.prevent="jatuhkanDiKolom(kolom)"
                                    >
                                        <TableCell :colspan="totalKolomTabel" class="py-2 pl-4">
                                            <span class="flex items-center gap-2">
                                                <Lencana :nilai="kolom.kunci" :peta="opsi.statusTask" />
                                                <span class="text-muted-foreground text-xs tabular-nums">
                                                    {{ kolom.tasks.length }} tugas
                                                </span>
                                                <span class="text-muted-foreground/70 text-xs">
                                                    — {{ kolom.keterangan }}
                                                </span>
                                            </span>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow
                                        v-for="t in kolom.tasks"
                                        :key="t.id"
                                        :draggable="izin.ubahProgress"
                                        :class="[
                                            izin.ubahProgress && 'cursor-grab active:cursor-grabbing',
                                            taskDiseret?.id === t.id && 'opacity-40',
                                            barisSasaran === t.id && 'bg-primary/10',
                                        ]"
                                        @dragstart="mulaiSeret(t, $event)"
                                        @dragend="akhiriSeret"
                                        @dragover.prevent="seretDiAtasBaris(t)"
                                        @drop.prevent="jatuhkanDiBaris(t)"
                                    >
                                        <TableCell v-if="izin.ubahProgress" class="pl-4">
                                            <GripVertical class="text-muted-foreground/60 size-4" />
                                        </TableCell>

                                        <TableCell :class="izin.ubahProgress ? '' : 'pl-4'">
                                            <span
                                                class="bg-primary/10 text-primary inline-flex size-7 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                                            >
                                                {{ nomorTask.get(t.id) }}
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <p class="font-medium">{{ t.judul }}</p>
                                            <p v-if="t.deskripsi" class="text-muted-foreground line-clamp-1 text-xs">
                                                {{ t.deskripsi }}
                                            </p>
                                            <p
                                                v-if="t.milestone"
                                                class="text-muted-foreground mt-0.5 flex items-center gap-1 text-xs"
                                            >
                                                <Flag class="size-3 shrink-0" />
                                                {{ t.milestone }}
                                            </p>
                                        </TableCell>

                                        <!-- Jalur yang bisa dipakai lewat papan ketik, bukan hanya tetikus. -->
                                        <TableCell>
                                            <Select
                                                v-if="izin.ubahProgress"
                                                :model-value="t.status"
                                                @update:model-value="(v) => pindahStatus(t, v)"
                                            >
                                                <SelectTrigger class="h-8"><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="(meta, kunci) in opsi.statusTask"
                                                        :key="kunci"
                                                        :value="kunci"
                                                    >
                                                        {{ meta.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Lencana v-else :nilai="t.status" :peta="opsi.statusTask" />
                                        </TableCell>

                                        <TableCell><Lencana :nilai="t.prioritas" :peta="opsi.prioritas" /></TableCell>

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
                                            <span class="flex items-center gap-1">
                                                <UserRound class="size-3 shrink-0" />
                                                <span class="truncate">{{ namaPic(t) || 'Belum ada PIC' }}</span>
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <span
                                                :class="[
                                                    'flex items-center gap-1 text-sm whitespace-nowrap',
                                                    t.terlambat ? 'font-medium text-rose-600' : 'text-muted-foreground',
                                                ]"
                                            >
                                                <TriangleAlert v-if="t.terlambat" class="size-3.5" />
                                                <CalendarClock v-else class="size-3.5" />
                                                {{ tanggal(t.deadline) }}
                                            </span>
                                        </TableCell>

                                        <TableCell v-if="izin.kelolaTask" class="pr-4">
                                            <div class="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Ubah"
                                                    @click="bukaEditTask(t)"
                                                >
                                                    <Pencil class="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Hapus"
                                                    class="text-destructive hover:text-destructive"
                                                    @click="konfirmasiHapus(t)"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Kelompok kosong tetap perlu sasaran jatuh. -->
                                    <TableRow
                                        v-if="kolom.tasks.length === 0"
                                        :class="[
                                            'hover:bg-transparent',
                                            kolomSasaran === kolom.kunci && 'bg-primary/10',
                                        ]"
                                        @dragover.prevent="seretDiAtasKolom(kolom.kunci)"
                                        @drop.prevent="jatuhkanDiKolom(kolom)"
                                    >
                                        <TableCell
                                            :colspan="totalKolomTabel"
                                            class="text-muted-foreground py-4 text-center text-xs"
                                        >
                                            Belum ada tugas di tahap ini.
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
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
            <!--
              Tinggi dibatasi layar dan isinya yang bergulir, bukan seluruh
              dialog: form ini panjang, dan kalau dialog ikut memanjang tombol
              Simpan terdorong keluar layar sampai tak terlihat.
            -->
            <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ taskDiedit ? 'Ubah Task' : 'Task Baru' }}</DialogTitle>
                    <DialogDescription>
                        Bobot menentukan porsi task ini terhadap progres dan kontribusi project.
                    </DialogDescription>
                </DialogHeader>

                <form class="flex min-h-0 flex-1 flex-col gap-4" @submit.prevent="simpanTask">
                    <div class="gulir-terlihat min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
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

                        <!--
                          Target opsional. Diisi untuk pekerjaan yang punya angka
                          (penagihan Rp, kolekting badan usaha); dikosongkan untuk
                          pekerjaan yang tak terukur angka.
                        -->
                        <div class="space-y-3 rounded-lg border p-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm">Target &amp; Realisasi</Label>
                                <span class="text-muted-foreground text-xs">opsional</span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="space-y-1.5">
                                    <Label for="satuan" class="text-muted-foreground text-xs">Satuan</Label>
                                    <Select
                                        :model-value="formTask.satuan ?? 'tanpa'"
                                        @update:model-value="(v) => (formTask.satuan = v === 'tanpa' ? null : v)"
                                    >
                                        <SelectTrigger id="satuan"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="tanpa">Tanpa satuan</SelectItem>
                                            <SelectItem v-for="sat in opsi.satuan" :key="sat" :value="sat">
                                                {{ sat }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="target" class="text-muted-foreground text-xs">Target</Label>
                                    <Input id="target" v-model="formTask.target" type="number" min="0" step="any" />
                                    <p v-if="formTask.errors.target" class="text-destructive text-sm">
                                        {{ formTask.errors.target }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="realisasi" class="text-muted-foreground text-xs">Realisasi</Label>
                                    <Input
                                        id="realisasi"
                                        v-model="formTask.realisasi"
                                        type="number"
                                        min="0"
                                        step="any"
                                        :disabled="! formPakaiTarget"
                                    />
                                    <p v-if="formTask.errors.realisasi" class="text-destructive text-sm">
                                        {{ formTask.errors.realisasi }}
                                    </p>
                                </div>
                            </div>

                            <p v-if="formPakaiTarget" class="text-muted-foreground text-xs">
                                Progress dihitung otomatis: {{ angka(formTask.realisasi || 0) }} dari
                                {{ angka(formTask.target) }} {{ formTask.satuan ?? '' }} =
                                <span class="text-foreground font-medium">{{ progressHitungan }}%</span>
                            </p>
                            <p v-else class="text-muted-foreground text-xs">
                                Kosongkan bila pekerjaan ini tidak diukur dengan angka — progress diisi manual.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label for="progress">Progress (%)</Label>
                                <Input
                                    id="progress"
                                    v-model="formTask.progress"
                                    type="number"
                                    min="0"
                                    max="100"
                                    :disabled="formPakaiTarget"
                                />
                                <p v-if="formPakaiTarget" class="text-muted-foreground text-xs">
                                    Terkunci — mengikuti realisasi.
                                </p>
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
                    </div>

                    <DialogFooter class="shrink-0 border-t pt-4">
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
