<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
import { Pencil, Plus, Search, Target, Trash2 } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    wigs: { type: Object, required: true },
    wilayahs: { type: Array, required: true },
    daftarBidang: { type: Array, required: true },
    daftarSifat: { type: Object, required: true },
    daftarArah: { type: Object, required: true },
    bawaanSifat: { type: Object, required: true },
    daftarTahun: { type: Array, required: true },
    filter: { type: Object, required: true },
    wilayahBawaan: { type: [Number, null], default: null },
});

const tahunIni = new Date().getFullYear();

/* --- Filter --- */
const SEMUA = 'semua';

const cari = ref(props.filter.cari);
const tahunFilter = ref(props.filter.tahun ? String(props.filter.tahun) : SEMUA);
const bidangFilter = ref(props.filter.bidang ?? SEMUA);
let timer = null;

const muatUlang = () =>
    router.get(
        '/wigs',
        {
            cari: cari.value || undefined,
            tahun: tahunFilter.value === SEMUA ? undefined : tahunFilter.value,
            bidang: bidangFilter.value === SEMUA ? undefined : bidangFilter.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});

watch([tahunFilter, bidangFilter], muatUlang);

/* --- Form --- */
const dialogTerbuka = ref(false);
const wigDiedit = ref(null);

const form = useForm({
    kode_wig: '',
    nama_wig: '',
    indikator_output: '',
    sifat_capaian: props.bawaanSifat.sifat,
    arah: props.bawaanSifat.arah,
    bidang: '',
    tahun: tahunIni,
    wilayah_id: props.wilayahBawaan ? String(props.wilayahBawaan) : null,
});

const bukaTambah = () => {
    wigDiedit.value = null;
    form.reset();
    form.clearErrors();
    form.tahun = tahunIni;
    form.wilayah_id = props.wilayahBawaan ? String(props.wilayahBawaan) : null;
    dialogTerbuka.value = true;
};

const bukaEdit = (wig) => {
    wigDiedit.value = wig;
    form.clearErrors();
    form.kode_wig = wig.kode_wig;
    form.nama_wig = wig.nama_wig;
    form.indikator_output = wig.indikator_output ?? '';
    form.sifat_capaian = wig.sifat_capaian ?? props.bawaanSifat.sifat;
    form.arah = wig.arah ?? props.bawaanSifat.arah;
    form.bidang = wig.bidang ?? '';
    form.tahun = wig.tahun;
    form.wilayah_id = wig.wilayah_id ? String(wig.wilayah_id) : null;
    dialogTerbuka.value = true;
};

const simpan = () => {
    const opsi = {
        preserveScroll: true,
        onSuccess: () => {
            dialogTerbuka.value = false;
            form.reset();
        },
    };

    if (wigDiedit.value) {
        form.put(`/wigs/${wigDiedit.value.id}`, opsi);
    } else {
        form.post('/wigs', opsi);
    }
};

/* --- Hapus --- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const wigDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    wigDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = wigDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/wigs/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (wigDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Input Data WIG" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Input Data WIG</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Wildly Important Goal per wilayah dan tahun.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah WIG
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari kode atau nama WIG..." class="pl-9" />
                </div>

                <Select v-model="bidangFilter">
                    <SelectTrigger class="w-44"><SelectValue placeholder="Semua bidang" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="SEMUA">Semua bidang</SelectItem>
                        <SelectItem v-for="b in daftarBidang" :key="b" :value="b">{{ b }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="tahunFilter">
                    <SelectTrigger class="w-40"><SelectValue placeholder="Semua tahun" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="SEMUA">Semua tahun</SelectItem>
                        <SelectItem v-for="t in daftarTahun" :key="t" :value="String(t)">{{ t }}</SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ wigs.total }} WIG</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-36">Kode</TableHead>
                                <TableHead class="w-[34rem] min-w-[20rem]">Nama WIG</TableHead>
                                <TableHead class="w-28">Bidang</TableHead>
                                <TableHead>Wilayah</TableHead>
                                <TableHead class="w-20 text-center">Tahun</TableHead>
                                <TableHead class="w-16 text-center">Lag</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(wig, index) in wigs.data" :key="wig.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ wigs.from + index }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono text-xs">{{ wig.kode_wig }}</Badge>
                                </TableCell>
                                <TableCell class="align-top text-sm whitespace-normal">
                                    <!--
                                      TableCell bawaan shadcn memakai whitespace-nowrap; tanpa whitespace-normal
                                      teks panjang tidak membungkus dan meluber menembus kolom sebelahnya.
                                    -->
                                    <div class="max-w-[34rem]">
                                        <p class="font-medium">{{ wig.nama_wig }}</p>
                                        <!--
                                          Badge dipakai untuk labelnya saja; badge bawaan memakai
                                          whitespace-nowrap sehingga teks panjang akan terpotong bila
                                          dimasukkan ke dalamnya.
                                        -->
                                        <div
                                            v-if="wig.indikator_output"
                                            class="mt-1.5 flex flex-wrap items-start gap-1.5"
                                        >
                                            <Badge
                                                variant="outline"
                                                class="border-destructive/30 bg-destructive/10 text-destructive"
                                            >
                                                Indikator
                                            </Badge>
                                            <span
                                                class="text-destructive line-clamp-2 whitespace-pre-wrap"
                                                :title="wig.indikator_output"
                                            >
                                                {{ wig.indikator_output }}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="wig.bidang" variant="outline" :class="kelasBidang(wig.bidang)">{{ wig.bidang }}</Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ wig.wilayah?.nama ?? '—' }}</TableCell>
                                <TableCell class="text-center tabular-nums">{{ wig.tahun }}</TableCell>
                                <TableCell class="text-center tabular-nums">{{ wig.lag_measures_count }}</TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(wig)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(wig)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="wigs.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="8" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Target class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada WIG yang cocok.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="wigs" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ wigDiedit ? 'Edit WIG' : 'Tambah WIG' }}</DialogTitle>
                    <DialogDescription>Kode WIG harus unik di seluruh sistem.</DialogDescription>
                </DialogHeader>

                <form class="min-w-0 space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="kode_wig">Kode WIG</Label>
                            <Input id="kode_wig" v-model="form.kode_wig" class="font-mono" />
                            <p v-if="form.errors.kode_wig" class="text-destructive text-sm">
                                {{ form.errors.kode_wig }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Bidang</Label>
                            <Select v-model="form.bidang">
                                <SelectTrigger class="w-full"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="b in daftarBidang" :key="b" :value="b">{{ b }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.bidang" class="text-destructive text-sm">{{ form.errors.bidang }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="tahun">Tahun</Label>
                            <Input id="tahun" v-model.number="form.tahun" type="number" />
                            <p v-if="form.errors.tahun" class="text-destructive text-sm">{{ form.errors.tahun }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_wig">Nama WIG</Label>
                        <Input id="nama_wig" v-model="form.nama_wig" />
                        <p v-if="form.errors.nama_wig" class="text-destructive text-sm">{{ form.errors.nama_wig }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="indikator_output">Indikator Output</Label>
                        <Textarea id="indikator_output" v-model="form.indikator_output" rows="3" />
                        <p v-if="form.errors.indikator_output" class="text-destructive text-sm">
                            {{ form.errors.indikator_output }}
                        </p>
                    </div>

                    <!--
                      | Sifat menentukan cara capaian "s.d. bulan" dihitung; arah
                      | menentukan apakah melampaui target itu baik atau buruk.
                    -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Sifat Capaian</Label>
                            <Select v-model="form.sifat_capaian">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(s, kunci) in daftarSifat" :key="kunci" :value="kunci">
                                        {{ s.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-muted-foreground text-xs">
                                {{ daftarSifat[form.sifat_capaian]?.keterangan }}
                            </p>
                            <p v-if="form.errors.sifat_capaian" class="text-destructive text-sm">
                                {{ form.errors.sifat_capaian }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Arah Keberhasilan</Label>
                            <Select v-model="form.arah">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(a, kunci) in daftarArah" :key="kunci" :value="kunci">
                                        {{ a.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-muted-foreground text-xs">{{ daftarArah[form.arah]?.keterangan }}</p>
                            <p v-if="form.errors.arah" class="text-destructive text-sm">{{ form.errors.arah }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Wilayah</Label>
                        <Select v-model="form.wilayah_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Tidak ditentukan" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                                    {{ w.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.wilayah_id" class="text-destructive text-sm">
                            {{ form.errors.wilayah_id }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogTerbuka = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Konfirmasi hapus -->
        <AlertDialog v-model:open="dialogHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus WIG ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong>{{ wigDihapus?.kode_wig }}</strong> akan dihapus permanen.
                        Tindakan ini tidak dapat dibatalkan.
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
