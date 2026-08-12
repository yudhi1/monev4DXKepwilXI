<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
import { Download, FileSpreadsheet, Trash2, Upload } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    indikator: { type: String, required: true },
    label: { type: String, required: true },
    daftarIndikator: { type: Array, required: true },
    terbaru: { type: [Object, null], default: null },
    riwayat: { type: Array, required: true },
    bisaKelola: { type: Boolean, default: false },
    tahunBawaan: { type: Number, required: true },
});

/* ---------------- Upload ---------------- */
const berkas = ref(null);

const form = useForm({
    tahun: props.tahunBawaan,
    file: null,
});

const pilihBerkas = (event) => {
    form.file = event.target.files[0] ?? null;
};

const unggah = () => {
    form.post(`/monitoring-kinerja/${props.indikator}/upload`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('file');

            if (berkas.value) {
                berkas.value.value = '';
            }
        },
    });
};

/* ---------------- Hapus ---------------- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const unggahanDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    unggahanDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = unggahanDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/monitoring-kinerja/${props.indikator}/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (unggahanDihapus.value = null),
    });
};

/* Baris pertama sheet dipakai sebagai judul kolom. */
const judulKolom = computed(() => props.terbaru?.baris?.[0] ?? []);
const isiBaris = computed(() => props.terbaru?.baris?.slice(1) ?? []);
</script>

<template>
    <Head :title="`Monitoring Kinerja — ${label}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Monitoring Kinerja</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Capaian indikator APC. Data diisi melalui unggahan berkas Excel.
                </p>
            </div>
        </template>

        <!-- Navigasi indikator -->
        <div class="mb-4 flex flex-wrap gap-2">
            <Link
                v-for="item in daftarIndikator"
                :key="item.slug"
                :href="`/monitoring-kinerja/${item.slug}`"
                :class="
                    cn(
                        'rounded-full border px-4 py-1.5 text-sm font-medium transition-colors',
                        item.slug === indikator
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                    )
                "
            >
                {{ item.nama }}
            </Link>
        </div>

        <!-- Form unggah -->
        <Card v-if="bisaKelola" class="mb-4">
            <CardContent>
                <form class="flex flex-wrap items-end gap-4" @submit.prevent="unggah">
                    <div class="w-32 space-y-2">
                        <Label for="tahun">Tahun</Label>
                        <Input id="tahun" v-model.number="form.tahun" type="number" min="2000" max="2100" />
                        <p v-if="form.errors.tahun" class="text-destructive text-sm">{{ form.errors.tahun }}</p>
                    </div>

                    <div class="min-w-72 flex-1 space-y-2">
                        <Label for="file">Berkas Excel (.xlsx, .xls, .csv) — maks 10 MB</Label>
                        <Input
                            id="file"
                            ref="berkas"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="cursor-pointer"
                            @change="pilihBerkas"
                        />
                        <p v-if="form.errors.file" class="text-destructive text-sm">{{ form.errors.file }}</p>
                    </div>

                    <Button type="submit" :disabled="form.processing || !form.file">
                        <Upload class="mr-1.5 size-4" />
                        {{ form.processing ? `Mengunggah ${form.progress?.percentage ?? 0}%` : 'Upload' }}
                    </Button>
                </form>

                <div v-if="form.progress" class="bg-muted mt-3 h-1.5 overflow-hidden rounded-full">
                    <div
                        class="bg-primary h-full rounded-full transition-[width]"
                        :style="{ width: `${form.progress.percentage}%` }"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Data terbaru -->
        <Card class="mb-4 overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Data Terbaru — {{ label }}</h2>
                <template v-if="terbaru">
                    <Badge variant="outline">{{ terbaru.tahun }}</Badge>
                    <span class="text-muted-foreground text-sm">
                        {{ terbaru.original_name }} · {{ terbaru.rows_count }} baris · {{ terbaru.diunggah }}
                    </span>
                    <Button variant="outline" size="sm" class="ml-auto" as-child>
                        <a :href="`/monitoring-kinerja/${indikator}/${terbaru.id}/unduh`">
                            <Download class="mr-1.5 size-4" />
                            Unduh
                        </a>
                    </Button>
                </template>
            </div>

            <CardContent class="p-0">
                <div v-if="terbaru && isiBaris.length" class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead v-for="(judul, i) in judulKolom" :key="i" class="whitespace-nowrap">
                                    {{ judul || `Kolom ${i + 1}` }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(baris, i) in isiBaris" :key="i">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ i + 1 }}</TableCell>
                                <TableCell v-for="(sel, j) in baris" :key="j" class="text-sm whitespace-nowrap">
                                    {{ sel }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-else class="py-16">
                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                        <FileSpreadsheet class="size-8 opacity-40" />
                        <p class="text-sm">
                            {{ bisaKelola
                                ? 'Belum ada data. Unggah berkas Excel di atas untuk menampilkannya.'
                                : 'Belum ada data untuk indikator ini.' }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Riwayat -->
        <Card class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Riwayat Upload</h2>
                <span class="text-muted-foreground ml-auto text-sm">15 unggahan terakhir</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="min-w-[16rem]">Nama Berkas</TableHead>
                                <TableHead class="w-24 text-center">Tahun</TableHead>
                                <TableHead class="w-24 text-center">Baris</TableHead>
                                <TableHead class="w-40">Diunggah Oleh</TableHead>
                                <TableHead class="w-40">Waktu</TableHead>
                                <TableHead class="w-28 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, i) in riwayat" :key="item.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ i + 1 }}</TableCell>
                                <TableCell class="text-sm font-medium">{{ item.original_name }}</TableCell>
                                <TableCell class="text-center text-sm tabular-nums">{{ item.tahun }}</TableCell>
                                <TableCell class="text-center text-sm tabular-nums">{{ item.rows_count }}</TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ item.uploader ?? '—' }}</TableCell>
                                <TableCell class="text-muted-foreground text-sm tabular-nums">
                                    {{ item.diunggah }}
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Unduh" as-child>
                                            <a :href="`/monitoring-kinerja/${indikator}/${item.id}/unduh`">
                                                <Download class="size-4" />
                                            </a>
                                        </Button>
                                        <Button
                                            v-if="bisaKelola"
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(item)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="riwayat.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <FileSpreadsheet class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada riwayat upload.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Konfirmasi hapus -->
        <AlertDialog v-model:open="dialogHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus data upload ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Berkas <strong>{{ unggahanDihapus?.original_name }}</strong> dan datanya akan dihapus permanen.
                        Bila ini unggahan terbaru, tabel akan menampilkan unggahan sebelumnya.
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
