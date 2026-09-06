<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
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
import { Building2, CircleAlert, Pencil, Plus, Search, Trash2, Users, X } from '@lucide/vue';

const props = defineProps({
    projects: { type: Object, required: true },
    filter: { type: Object, required: true },
    opsi: { type: Object, required: true },
    bisaBuat: { type: Boolean, default: false },
    kandidatAnggota: { type: Array, default: () => [] },
    unitSaya: { type: Object, default: null },
});

const HEALTH = {
    on_track: { label: 'On Track', kelas: 'text-emerald-600' },
    at_risk: { label: 'At Risk', kelas: 'text-amber-600' },
    critical: { label: 'Critical', kelas: 'text-rose-600' },
};

/* --- Filter (debounce untuk pencarian, langsung untuk dropdown) --- */
const cari = ref(props.filter.cari);
const status = ref(props.filter.status || 'semua');
const prioritas = ref(props.filter.prioritas || 'semua');

const terapkan = () =>
    router.get(
        '/pm/projects',
        {
            cari: cari.value || undefined,
            status: status.value === 'semua' ? undefined : status.value,
            prioritas: prioritas.value === 'semua' ? undefined : prioritas.value,
        },
        { preserveState: true, replace: true }
    );

let timer = null;
watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(terapkan, 350);
});
watch([status, prioritas], terapkan);

/* --- Form project baru --- */
const dialogTerbuka = ref(false);

const form = useForm({
    kode: '',
    nama: '',
    deskripsi: '',
    status: 'perencanaan',
    prioritas: 'sedang',
    tanggal_mulai: '',
    tanggal_selesai: '',
    anggotas: [],
});

/*
 | Dialog yang sama dipakai untuk membuat dan mengubah. Bedanya satu:
 | pemilihan anggota hanya muncul saat membuat, karena setelah project ada
 | keanggotaan diurus di tab Members pada halaman detail.
 */
const projectDiedit = ref(null);

const bukaTambah = () => {
    projectDiedit.value = null;
    form.reset();
    form.clearErrors();
    cariAnggota.value = '';
    dialogTerbuka.value = true;
};

const bukaEdit = (p) => {
    projectDiedit.value = p;
    form.clearErrors();
    form.kode = p.kode;
    form.nama = p.nama;
    form.deskripsi = p.deskripsi ?? '';
    form.status = p.status;
    form.prioritas = p.prioritas;
    form.tanggal_mulai = p.tanggal_mulai ?? '';
    form.tanggal_selesai = p.tanggal_selesai ?? '';
    form.anggotas = [];
    dialogTerbuka.value = true;
};

/* --- Pemilihan anggota saat project dibuat --- */
const cariAnggota = ref('');

/* Kandidat dikelompokkan per kantor induk supaya lintas bidang mudah ditelusuri. */
const kandidatTersaring = computed(() => {
    const kata = cariAnggota.value.trim().toLowerCase();
    const terpilih = new Set(form.anggotas.map((a) => a.user_id));

    const cocok = props.kandidatAnggota.filter((k) => {
        if (terpilih.has(k.id)) {
            return false;
        }
        if (kata === '') {
            return true;
        }

        return [k.nama, k.unitKerja, k.induk, k.jabatan]
            .filter(Boolean)
            .some((t) => t.toLowerCase().includes(kata));
    });

    const kelompok = new Map();
    for (const k of cocok) {
        if (! kelompok.has(k.induk)) {
            kelompok.set(k.induk, []);
        }
        kelompok.get(k.induk).push(k);
    }

    return [...kelompok.entries()].map(([induk, orang]) => ({ induk, orang }));
});

const detailKandidat = (id) => props.kandidatAnggota.find((k) => k.id === id);

const tambahAnggota = (kandidat) => {
    form.anggotas = [...form.anggotas, { user_id: kandidat.id, peran: 'member' }];
};

const hapusAnggota = (userId) => {
    form.anggotas = form.anggotas.filter((a) => a.user_id !== userId);
};

const simpan = () => {
    const opsiKirim = {
        preserveScroll: true,
        onSuccess: () => {
            dialogTerbuka.value = false;
            form.reset();
        },
    };

    if (projectDiedit.value) {
        form.put('/pm/projects/' + projectDiedit.value.id, opsiKirim);
    } else {
        form.post('/pm/projects', opsiKirim);
    }
};

/* --- Hapus project --- */
const projectDihapus = ref(null);
const dialogHapus = ref(false);

const konfirmasiHapus = (p) => {
    projectDihapus.value = p;
    dialogHapus.value = true;
};

const hapus = () => {
    const sasaran = projectDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete('/pm/projects/' + sasaran.id, {
        preserveScroll: true,
        onFinish: () => (projectDihapus.value = null),
    });
};

/* Seluruh baris dapat diklik; nama project tetap tautan sungguhan demi papan ketik. */
const buka = (id) => router.visit('/pm/projects/' + id);

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Projects" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Project yang Anda ikuti beserta progres dan kesehatannya.
                    </p>
                </div>
                <Button v-if="bisaBuat" @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Project Baru
                </Button>
            </div>
        </template>

        <!-- Filter -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative w-full max-w-xs">
                <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input v-model="cari" placeholder="Cari kode atau nama project..." class="pl-9" />
            </div>

            <Select v-model="status">
                <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua status</SelectItem>
                    <!-- Gabungan, bukan status tunggal; dipakai kartu dashboard. -->
                    <SelectItem value="aktif">Sedang dikerjakan</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.statusProject" :key="kunci" :value="kunci">
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

            <span class="text-muted-foreground ml-auto text-sm">{{ projects.total }} project</span>
        </div>

        <!--
          Tabel, bukan kartu: satu project cukup satu baris, sehingga puluhan
          project tetap terbaca dalam satu layar dan mudah dibandingkan kolom
          per kolom.
        -->
        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <p v-if="projects.data.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                    Tidak ada project yang cocok dengan filter ini.
                </p>

                <div v-else class="gulir-terlihat overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-28">Kode</TableHead>
                                <TableHead>Project</TableHead>
                                <TableHead class="w-32">Status</TableHead>
                                <TableHead class="w-28">Prioritas</TableHead>
                                <TableHead class="w-44">Progres</TableHead>
                                <TableHead class="w-32 text-center">Tugas Selesai</TableHead>
                                <TableHead class="w-24 text-center">Anggota</TableHead>
                                <TableHead class="w-32">Tenggat</TableHead>
                                <TableHead class="w-24 text-right">Kondisi</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(p, index) in projects.data"
                                :key="p.id"
                                class="cursor-pointer"
                                @click="buka(p.id)"
                            >
                                <!--
                                  Nomor diambil dari `from` milik paginator, bukan
                                  index halaman, supaya urutannya menyambung di
                                  halaman kedua dan seterusnya.
                                -->
                                <TableCell class="pl-4">
                                    <span
                                        class="bg-primary/10 text-primary inline-flex size-7 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                                    >
                                        {{ projects.from + index }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-muted-foreground font-mono text-xs">
                                    {{ p.kode }}
                                </TableCell>

                                <TableCell>
                                    <!-- Tautan sungguhan agar tetap bisa dijangkau lewat papan ketik. -->
                                    <Link :href="'/pm/projects/' + p.id" class="font-medium hover:underline">
                                        {{ p.nama }}
                                    </Link>
                                    <p
                                        v-if="p.unitKerja"
                                        class="text-muted-foreground mt-0.5 flex items-center gap-1 text-xs"
                                    >
                                        <Building2 class="size-3 shrink-0" />
                                        <span class="truncate">{{ p.unitKerja }} · {{ p.unitKerjaInduk }}</span>
                                    </p>
                                    <Lencana
                                        v-if="p.peranSaya"
                                        :nilai="p.peranSaya"
                                        :peta="opsi.peran"
                                        class="mt-1"
                                    />
                                </TableCell>

                                <TableCell><Lencana :nilai="p.status" :peta="opsi.statusProject" /></TableCell>
                                <TableCell><Lencana :nilai="p.prioritas" :peta="opsi.prioritas" /></TableCell>
                                <TableCell><BilahProgress :nilai="p.progress" /></TableCell>

                                <!--
                                  "1 / 4" saja terbaca ambigu — bisa disangka
                                  rasio apa pun. Kata "dari" membuatnya jelas.
                                -->
                                <TableCell class="text-center text-sm">
                                    <span class="tabular-nums">
                                        {{ p.jumlahSelesai }} <span class="text-muted-foreground">dari</span>
                                        {{ p.jumlahTask }}
                                    </span>
                                    <p v-if="p.jumlahTerlambat > 0" class="text-xs font-medium text-rose-600">
                                        {{ p.jumlahTerlambat }} terlambat
                                    </p>
                                    <!--
                                      Menjelaskan mengapa "selesai" bisa
                                      berdampingan dengan progres rendah.
                                    -->
                                    <p
                                        v-if="p.jumlahRealisasiTertinggal > 0"
                                        class="mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-amber-600"
                                        :title="
                                            p.jumlahRealisasiTertinggal +
                                            ' tugas ditandai selesai, tetapi realisasinya belum mencapai target — karena itu progres project belum penuh.'
                                        "
                                    >
                                        <CircleAlert class="size-3 shrink-0" />
                                        {{ p.jumlahRealisasiTertinggal }} realisasi belum diisi
                                    </p>
                                </TableCell>

                                <TableCell class="text-muted-foreground text-center text-sm tabular-nums">
                                    <span class="inline-flex items-center gap-1">
                                        <Users class="size-3" />
                                        {{ p.jumlahAnggota }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-muted-foreground text-sm whitespace-nowrap">
                                    {{ tanggal(p.tanggal_selesai) }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <span :class="['text-xs font-medium whitespace-nowrap', HEALTH[p.health]?.kelas]">
                                        {{ HEALTH[p.health]?.label }}
                                    </span>
                                </TableCell>

                                <!--
                                  Klik tombol tidak boleh ikut membuka project,
                                  jadi peristiwanya dihentikan di sini.
                                -->
                                <TableCell class="pr-4" @click.stop>
                                    <div v-if="p.bisaKelola" class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Ubah" @click="bukaEdit(p)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(p)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                    <span v-else class="text-muted-foreground block text-right text-xs">—</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <Paginasi :data="projects" />
            </CardContent>
        </Card>


        <!-- Dialog project baru -->
        <Dialog v-model:open="dialogTerbuka">
            <!-- Sama seperti dialog task: isi yang bergulir, tombol tetap terlihat. -->
            <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-3xl">
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ projectDiedit ? 'Ubah Project' : 'Project Baru' }}</DialogTitle>
                    <DialogDescription>
                        <template v-if="projectDiedit">
                            Anggota tim diatur di tab Members pada halaman project.
                        </template>
                        <template v-else>
                            <template v-if="unitSaya">
                                Project ini dimiliki
                                <span class="font-medium">{{ unitSaya.nama }} — {{ unitSaya.induk }}</span>.
                            </template>
                            Anda otomatis menjadi Project Manager-nya.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <form class="flex min-h-0 flex-1 flex-col gap-4" @submit.prevent="simpan">
                    <div class="gulir-terlihat min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label for="kode">Kode</Label>
                                <Input id="kode" v-model="form.kode" placeholder="PRJ-001" />
                                <p v-if="form.errors.kode" class="text-destructive text-sm">{{ form.errors.kode }}</p>
                            </div>
                            <div class="space-y-1.5 sm:col-span-2">
                                <Label for="nama">Nama Project</Label>
                                <Input id="nama" v-model="form.nama" />
                                <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="deskripsi">Deskripsi</Label>
                            <Textarea id="deskripsi" v-model="form.deskripsi" rows="3" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label>Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(meta, kunci) in opsi.statusProject" :key="kunci" :value="kunci">
                                            {{ meta.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Prioritas</Label>
                                <Select v-model="form.prioritas">
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                                            {{ meta.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="mulai">Tanggal Mulai</Label>
                                <Input id="mulai" v-model="form.tanggal_mulai" type="date" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="selesai">Tanggal Selesai</Label>
                                <Input id="selesai" v-model="form.tanggal_selesai" type="date" />
                                <p v-if="form.errors.tanggal_selesai" class="text-destructive text-sm">
                                    {{ form.errors.tanggal_selesai }}
                                </p>
                            </div>
                        </div>

                        <!--
                          Anggota ditentukan sejak awal, bukan setelah project jadi.
                          Boleh lintas bidang dan lintas level (Kepwil ⇄ kantor cabang).
                        -->
                        <div v-if="! projectDiedit" class="space-y-2 border-t pt-4">
                            <div class="flex items-center justify-between">
                                <Label>Anggota Tim</Label>
                                <span class="text-muted-foreground text-xs">
                                    {{ form.anggotas.length }} dipilih (di luar Anda)
                                </span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <!-- Kiri: kandidat -->
                                <div class="rounded-md border">
                                    <div class="relative border-b p-2">
                                        <Search class="text-muted-foreground absolute top-1/2 left-4 size-3.5 -translate-y-1/2" />
                                        <Input
                                            v-model="cariAnggota"
                                            placeholder="Cari nama, bidang, atau kantor..."
                                            class="h-8 pl-8 text-sm"
                                        />
                                    </div>

                                    <div class="max-h-56 overflow-y-auto p-1">
                                        <p
                                            v-if="kandidatTersaring.length === 0"
                                            class="text-muted-foreground p-4 text-center text-xs"
                                        >
                                            {{
                                                kandidatAnggota.length === 0
                                                    ? 'Belum ada data pegawai. Admin dapat menambahkannya lewat Master → User.'
                                                    : 'Tidak ada pegawai yang cocok.'
                                            }}
                                        </p>

                                        <div v-for="k in kandidatTersaring" :key="k.induk" class="mb-1">
                                            <p class="text-muted-foreground px-2 py-1 text-xs font-semibold">
                                                {{ k.induk }}
                                            </p>
                                            <button
                                                v-for="orang in k.orang"
                                                :key="orang.id"
                                                type="button"
                                                class="hover:bg-secondary flex w-full items-center gap-2 rounded px-2 py-1.5 text-left"
                                                @click="tambahAnggota(orang)"
                                            >
                                                <Plus class="text-muted-foreground size-3.5 shrink-0" />
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm">{{ orang.nama }}</span>
                                                    <span class="text-muted-foreground block truncate text-xs">
                                                        {{ orang.unitKerja }}{{ orang.jabatan ? ` · ${orang.jabatan}` : '' }}
                                                    </span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kanan: yang sudah dipilih -->
                                <div class="rounded-md border">
                                    <p class="text-muted-foreground border-b px-3 py-2 text-xs font-semibold">
                                        Tim project
                                    </p>
                                    <div class="max-h-56 space-y-1 overflow-y-auto p-2">
                                        <div class="bg-secondary/60 flex items-center gap-2 rounded px-2 py-1.5">
                                            <span class="min-w-0 flex-1 truncate text-sm">Anda</span>
                                            <span class="text-muted-foreground shrink-0 text-xs">Project Manager</span>
                                        </div>

                                        <div
                                            v-for="a in form.anggotas"
                                            :key="a.user_id"
                                            class="flex items-center gap-2 rounded px-2 py-1"
                                        >
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm">
                                                    {{ detailKandidat(a.user_id)?.nama }}
                                                </span>
                                                <span class="text-muted-foreground block truncate text-xs">
                                                    {{ detailKandidat(a.user_id)?.unitKerja }}
                                                </span>
                                            </span>

                                            <Select v-model="a.peran">
                                                <SelectTrigger class="h-7 w-28 shrink-0 text-xs">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="(meta, kunci) in opsi.peran"
                                                        :key="kunci"
                                                        :value="kunci"
                                                    >
                                                        {{ meta.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>

                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-destructive shrink-0"
                                                @click="hapusAnggota(a.user_id)"
                                            >
                                                <X class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="shrink-0 border-t pt-4">
                        <Button type="button" variant="outline" @click="dialogTerbuka = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <!-- ===== Konfirmasi hapus ===== -->
        <AlertDialog v-model:open="dialogHapus">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus project ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span class="font-medium">{{ projectDihapus?.nama }}</span> akan dihapus permanen
                        beserta seluruh task, milestone, dan keanggotaannya
                        <template v-if="projectDihapus?.jumlahTask">
                            — termasuk {{ projectDihapus.jumlahTask }} task di dalamnya.
                        </template>
                        Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive hover:bg-destructive/90" @click="hapus">
                        Ya, Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
