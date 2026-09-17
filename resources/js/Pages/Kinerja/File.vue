<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronDown, Download, FileText, Layers, Pencil, Search, Trash2, Upload } from '@lucide/vue';

const props = defineProps({
    kelompok: { type: Array, required: true },
    jumlahFile: { type: Number, required: true },
    kategoris: { type: Array, required: true },
    indikators: { type: Array, required: true },
    daftarBulan: { type: Array, required: true },
    filter: { type: Object, required: true },
    bisaKelola: { type: Boolean, default: false },
    tahunBawaan: { type: Number, required: true },
    bulanBawaan: { type: Number, required: true },
});

/* --- Filter --- */
/*
 | "semua" jadi nilai penanda karena SelectItem reka-ui menolak value string
 | kosong; kosong baginya berarti "belum memilih", bukan "tanpa saringan".
 */
const cari = ref(props.filter.cari);
const fKategori = ref(props.filter.kategori ? String(props.filter.kategori) : 'semua');
const fIndikator = ref(props.filter.indikator ? String(props.filter.indikator) : 'semua');
const fBulan = ref(props.filter.bulan ? String(props.filter.bulan) : 'semua');
const fTahun = ref(String(props.filter.tahun));
let timer = null;

/** Indikator pada saringan ikut kategori yang sedang dipilih. */
const indikatorFilter = computed(() =>
    fKategori.value === 'semua'
        ? props.indikators
        : props.indikators.filter((i) => String(i.kinerja_kategori_id) === fKategori.value)
);

const muat = () => {
    router.get(
        '/kinerja/file',
        {
            cari: cari.value || undefined,
            kategori: fKategori.value === 'semua' ? undefined : fKategori.value,
            indikator: fIndikator.value === 'semua' ? undefined : fIndikator.value,
            bulan: fBulan.value === 'semua' ? undefined : fBulan.value,
            tahun: fTahun.value,
        },
        { preserveState: true, replace: true }
    );
};

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muat, 350);
});

/* Ganti kategori melepas indikator terpilih — ia bisa bukan milik kategori baru. */
watch(fKategori, () => {
    fIndikator.value = 'semua';
    muat();
});

watch([fIndikator, fBulan, fTahun], muat);

/* --- Lipat/buka kartu kategori --- */
/*
 | Seluruh kategori terlipat saat halaman dibuka, jadi yang disimpan adalah
 | kategori yang DIBUKA. Daftar kategori terbaca sekaligus dalam satu layar,
 | dan isinya baru dimunculkan begitu diklik.
 */
const terbuka = reactive({});

const lipatToggle = (id) => {
    terbuka[id] = ! terbuka[id];
};

/* --- Form unggah / ubah --- */
const dialogTerbuka = ref(false);
const fileDiedit = ref(null);
const inputFile = ref(null);

const form = useForm({
    kinerja_kategori_id: '',
    kinerja_indikator_id: '',
    nama: '',
    file: null,
    keterangan: '',
    bulan: String(props.bulanBawaan),
    tahun: String(props.tahunBawaan),
});

/** Indikator di dalam formulir hanya yang milik kategori terpilih. */
const indikatorForm = computed(() =>
    props.indikators.filter((i) => String(i.kinerja_kategori_id) === form.kinerja_kategori_id)
);

/*
 | Pilihan indikator dikosongkan setiap kategori berganti. Tanpa ini, indikator
 | milik kategori lama tetap terkirim dan ditolak validasi di server —
 | kesalahan yang tak terlihat sebabnya oleh pengguna.
 */
watch(
    () => form.kinerja_kategori_id,
    (baru, lama) => {
        if (lama !== undefined && baru !== lama) {
            form.kinerja_indikator_id = '';
        }
    }
);

const pilihBerkas = (event) => {
    form.file = event.target.files?.[0] ?? null;
};

const bersihkanInputFile = () => {
    if (inputFile.value) {
        inputFile.value.value = '';
    }
};

/** Dipanggil dari tombol utama maupun dari tombol di dalam kartu kategori. */
const bukaTambah = (kategoriId = null, indikatorId = null) => {
    fileDiedit.value = null;
    form.reset();
    form.clearErrors();
    form.bulan = String(props.bulanBawaan);
    form.tahun = String(props.tahunBawaan);

    const awalan = kategoriId ?? (fKategori.value !== 'semua' ? fKategori.value : null);

    if (awalan) {
        form.kinerja_kategori_id = String(awalan);
    }

    if (indikatorId) {
        form.kinerja_indikator_id = String(indikatorId);
    }

    bersihkanInputFile();
    dialogTerbuka.value = true;
};

const bukaEdit = (file) => {
    fileDiedit.value = file;
    form.clearErrors();
    form.kinerja_kategori_id = String(file.kinerja_kategori_id);
    form.kinerja_indikator_id = String(file.kinerja_indikator_id);
    form.nama = file.nama;
    form.file = null;
    form.keterangan = file.keterangan ?? '';
    form.bulan = String(file.bulan);
    form.tahun = String(file.tahun);
    bersihkanInputFile();
    dialogTerbuka.value = true;
};

/*
 | Keduanya dikirim lewat POST. Unggahan berkas harus multipart, dan PUT tidak
 | membawa body multipart di PHP — jadi perubahan memakai penyamaran _method.
 */
const simpan = () => {
    const opsi = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            dialogTerbuka.value = false;
            form.reset();
            bersihkanInputFile();
        },
    };

    if (fileDiedit.value) {
        form
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(`/kinerja/file/${fileDiedit.value.id}`, opsi);
    } else {
        form.post('/kinerja/file', opsi);
    }
};

/* --- Hapus --- */
const fileDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    fileDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = fileDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/kinerja/file/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (fileDihapus.value = null),
    });
};

/* Lima tahun ke belakang cukup: berkas capaian tidak diarsipkan lebih lama. */
const daftarTahun = Array.from({ length: 6 }, (_, i) => props.tahunBawaan - i);

const belumSiap = computed(() => props.kategoris.length === 0 || props.indikators.length === 0);

const adaSaringan = computed(
    () =>
        Boolean(props.filter.cari) ||
        Boolean(props.filter.kategori) ||
        Boolean(props.filter.indikator) ||
        Boolean(props.filter.bulan)
);
</script>

<template>
    <Head title="File Capaian Kinerja" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">File Capaian Kinerja</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Berkas capaian per indikator dan bulan. Maksimal 2 MB per file.
                    </p>
                </div>
                <Button v-if="bisaKelola" :disabled="belumSiap" @click="bukaTambah()">
                    <Upload class="mr-1.5 size-4" />
                    Upload File
                </Button>
            </div>
        </template>

        <!-- Tanpa kategori dan indikator, tidak ada tempat menautkan berkas. -->
        <Card v-if="bisaKelola && belumSiap" class="mb-4">
            <CardContent class="text-muted-foreground flex flex-wrap items-center gap-3 text-sm">
                <span>Lengkapi kategori dan indikator lebih dulu sebelum mengunggah file.</span>
                <Button as-child size="sm" variant="outline">
                    <Link href="/kinerja/kategori">Kategori Capaian</Link>
                </Button>
                <Button as-child size="sm" variant="outline">
                    <Link href="/kinerja/indikator">Indikator</Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Saringan berlaku untuk seluruh kartu di bawahnya. -->
        <Card class="mb-4 py-0">
            <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari nama file atau keterangan..." class="pl-9" />
                </div>

                <Select v-model="fKategori">
                    <SelectTrigger class="w-56"><SelectValue placeholder="Semua kategori" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua kategori</SelectItem>
                        <SelectItem v-for="k in kategoris" :key="k.id" :value="String(k.id)">{{ k.nama }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="fIndikator">
                    <SelectTrigger class="w-56"><SelectValue placeholder="Semua indikator" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua indikator</SelectItem>
                        <SelectItem v-for="i in indikatorFilter" :key="i.id" :value="String(i.id)">
                            {{ i.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="fBulan">
                    <SelectTrigger class="w-40"><SelectValue placeholder="Semua bulan" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua bulan</SelectItem>
                        <SelectItem v-for="b in daftarBulan" :key="b.nomor" :value="String(b.nomor)">
                            {{ b.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="fTahun">
                    <SelectTrigger class="w-28"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="t in daftarTahun" :key="t" :value="String(t)">{{ t }}</SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ jumlahFile }} file</span>
            </div>
        </Card>

        <!-- Satu kartu per kategori; isinya dikelompokkan lagi per indikator. -->
        <div class="space-y-4">
            <Card v-for="kategori in kelompok" :key="kategori.id" class="overflow-hidden py-0">
                <button
                    type="button"
                    class="bg-primary/10 hover:bg-primary/20 flex w-full items-center gap-3 px-4 py-3 text-left transition-colors"
                    @click="lipatToggle(kategori.id)"
                >
                    <span class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                        <Layers class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-semibold tracking-tight">{{ kategori.nama }}</h2>
                        <p class="text-muted-foreground text-xs">
                            {{ kategori.indikators.length }} indikator · {{ kategori.jumlahFile }} file
                        </p>
                    </div>
                    <ChevronDown
                        :class="[
                            'text-muted-foreground size-4 shrink-0 transition-transform',
                            ! terbuka[kategori.id] && '-rotate-90',
                        ]"
                    />
                </button>

                <CardContent v-show="terbuka[kategori.id]" class="space-y-4 border-t p-4">
                    <div
                        v-for="(indikator, nomor) in kategori.indikators"
                        :key="indikator.id"
                        class="space-y-2"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Nomor urut indikator di dalam kategorinya. -->
                            <span
                                class="bg-primary text-primary-foreground flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                            >
                                {{ nomor + 1 }}
                            </span>
                            <h3 class="text-sm font-medium">{{ indikator.nama }}</h3>
                            <Badge variant="secondary">{{ indikator.files.length }} file</Badge>
                            <Button
                                v-if="bisaKelola"
                                variant="ghost"
                                size="sm"
                                class="text-muted-foreground ml-auto h-7"
                                @click="bukaTambah(kategori.id, indikator.id)"
                            >
                                <Upload class="mr-1 size-3.5" />
                                Tambah
                            </Button>
                        </div>

                        <!--
                            Satu tabel per indikator. Kolom kategori dan indikator
                            tidak ada di sini karena keduanya sudah jadi judul di
                            atasnya — mengulanginya tiap baris hanya menyita lebar.
                        -->
                        <div class="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <!--
                                        Warna header dipakai sebagai pemisah: tiap indikator punya
                                        tabelnya sendiri, dan tanpa latar ini deretan tabel yang
                                        beruntun terbaca seperti satu tabel panjang.
                                    -->
                                    <TableRow class="bg-primary/15 hover:bg-primary/15">
                                        <TableHead class="w-14 pl-4">#</TableHead>
                                        <TableHead>Nama File</TableHead>
                                        <TableHead class="w-28">Bulan</TableHead>
                                        <TableHead>Keterangan</TableHead>
                                        <TableHead class="w-44">Diunggah</TableHead>
                                        <TableHead class="w-28 pr-4 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(file, urutan) in indikator.files" :key="file.id">
                                        <!-- Nomor dihitung ulang per indikator, bukan berlanjut antar tabel. -->
                                        <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                            {{ urutan + 1 }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="font-medium">{{ file.nama }}</div>
                                            <div class="text-muted-foreground text-xs">
                                                {{ file.nama_asli }} · {{ file.ukuran }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="whitespace-nowrap">{{ file.namaBulan }}</TableCell>
                                        <TableCell class="text-muted-foreground max-w-xs truncate">
                                            {{ file.keterangan || '—' }}
                                        </TableCell>
                                        <TableCell class="text-muted-foreground text-sm">
                                            <div>{{ file.pengunggah }}</div>
                                            <div class="text-xs">{{ file.diunggah }}</div>
                                        </TableCell>
                                        <TableCell class="pr-4">
                                            <div class="flex justify-end gap-1">
                                                <!--
                                                    Unduhan adalah respons berkas, bukan kunjungan
                                                    Inertia, jadi di sini <a> yang benar — bukan <Link>.
                                                -->
                                                <Button as-child variant="ghost" size="icon" title="Unduh">
                                                    <a :href="`/kinerja/file/${file.id}/unduh`">
                                                        <Download class="size-4" />
                                                    </a>
                                                </Button>
                                                <Button
                                                    v-if="bisaKelola"
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Edit"
                                                    @click="bukaEdit(file)"
                                                >
                                                    <Pencil class="size-4" />
                                                </Button>
                                                <Button
                                                    v-if="bisaKelola"
                                                    variant="ghost"
                                                    size="icon"
                                                    class="text-destructive hover:text-destructive"
                                                    title="Hapus"
                                                    @click="konfirmasiHapus(file)"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="kelompok.length === 0">
                <CardContent class="py-16">
                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                        <FileText class="size-8 opacity-40" />
                        <p class="text-sm">
                            {{ adaSaringan
                                ? 'Tidak ada file capaian yang cocok dengan saringan ini.'
                                : `Belum ada file capaian untuk tahun ${filter.tahun}.` }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Dialog unggah / ubah -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ fileDiedit ? 'Edit File Capaian' : 'Upload File Capaian' }}</DialogTitle>
                    <DialogDescription>
                        Pilih kategori, lalu indikatornya. Ukuran file maksimal 2 MB
                        (PDF, Word, Excel, PowerPoint, JPG, atau PNG).
                    </DialogDescription>
                </DialogHeader>

                <form class="min-w-0 space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label>Kategori Capaian</Label>
                        <Select v-model="form.kinerja_kategori_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Pilih kategori" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="k in kategoris" :key="k.id" :value="String(k.id)">
                                    {{ k.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.kinerja_kategori_id" class="text-destructive text-sm">
                            {{ form.errors.kinerja_kategori_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>Indikator</Label>
                        <Select v-model="form.kinerja_indikator_id" :disabled="! form.kinerja_kategori_id">
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="form.kinerja_kategori_id ? 'Pilih indikator' : 'Pilih kategori dulu'"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="i in indikatorForm" :key="i.id" :value="String(i.id)">
                                    {{ i.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.kinerja_kategori_id && indikatorForm.length === 0"
                            class="text-muted-foreground text-sm"
                        >
                            Kategori ini belum memiliki indikator aktif.
                        </p>
                        <p v-if="form.errors.kinerja_indikator_id" class="text-destructive text-sm">
                            {{ form.errors.kinerja_indikator_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama">Nama File</Label>
                        <Input id="nama" v-model="form.nama" placeholder="mis. Laporan Capaian APC Agustus" />
                        <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="berkas">File {{ fileDiedit ? '(kosongkan bila tidak diganti)' : '' }}</Label>
                        <Input
                            id="berkas"
                            ref="inputFile"
                            type="file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                            @change="pilihBerkas"
                        />
                        <p v-if="fileDiedit" class="text-muted-foreground text-xs">
                            File saat ini: {{ fileDiedit.nama_asli }} ({{ fileDiedit.ukuran }})
                        </p>
                        <p v-if="form.errors.file" class="text-destructive text-sm">{{ form.errors.file }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Bulan</Label>
                            <Select v-model="form.bulan">
                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih bulan" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="b in daftarBulan" :key="b.nomor" :value="String(b.nomor)">
                                        {{ b.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.bulan" class="text-destructive text-sm">{{ form.errors.bulan }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Tahun</Label>
                            <Select v-model="form.tahun">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in daftarTahun" :key="t" :value="String(t)">
                                        {{ t }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.tahun" class="text-destructive text-sm">{{ form.errors.tahun }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="3" placeholder="Opsional" />
                        <p v-if="form.errors.keterangan" class="text-destructive text-sm">
                            {{ form.errors.keterangan }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogTerbuka = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Mengunggah...' : 'Simpan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Konfirmasi hapus -->
        <AlertDialog v-model:open="dialogHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus file capaian ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        File <strong>{{ fileDihapus?.nama }}</strong> beserta berkas fisiknya akan
                        dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="hapus"
                    >
                        Ya, Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
