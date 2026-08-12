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
import { Pencil, Plus, Search, Trash2, TrendingDown } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    lags: { type: Object, required: true },
    wigs: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    daftarBidang: { type: Array, required: true },
    filter: { type: Object, required: true },
});

const tahunIni = new Date().getFullYear();

/* --- Filter --- */
const SEMUA = 'semua';

const cari = ref(props.filter.cari);
const wigFilter = ref(props.filter.wig_id ? String(props.filter.wig_id) : SEMUA);
const bidangFilter = ref(props.filter.bidang ?? SEMUA);
const cabangFilter = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : SEMUA);
let timer = null;

const muatUlang = () =>
    router.get(
        '/lag-measures',
        {
            cari: cari.value || undefined,
            wig_id: wigFilter.value === SEMUA ? undefined : wigFilter.value,
            bidang: bidangFilter.value === SEMUA ? undefined : bidangFilter.value,
            cabang_id: cabangFilter.value === SEMUA ? undefined : cabangFilter.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});

watch([wigFilter, bidangFilter, cabangFilter], muatUlang);

/* --- Form --- */
const dialogTerbuka = ref(false);
const lagDiedit = ref(null);

const form = useForm({
    wig_id: '',
    cabang_id: '',
    kode_lag: '',
    nama_lag: '',
    tanggal_target: '',
    tahun: tahunIni,
});

/*
 | Kode lag diusulkan server dari kode cabang + tahun. Hanya diminta saat
 | membuat baru — kode yang sudah dipakai tidak boleh berubah saat diedit.
 */
const ambilSaranKode = async () => {
    if (lagDiedit.value || ! form.cabang_id) {
        return;
    }

    const params = new URLSearchParams({ cabang_id: form.cabang_id, tahun: form.tahun });
    const respons = await fetch(`/lag-measures/kode?${params}`, {
        headers: { Accept: 'application/json' },
    });

    if (respons.ok) {
        form.kode_lag = (await respons.json()).kode;
    }
};

watch(() => [form.cabang_id, form.tahun], ambilSaranKode);

const bukaTambah = () => {
    lagDiedit.value = null;
    form.reset();
    form.clearErrors();
    form.tahun = tahunIni;
    dialogTerbuka.value = true;
};

const bukaEdit = (lag) => {
    lagDiedit.value = lag;
    form.clearErrors();
    form.wig_id = String(lag.wig_id);
    form.cabang_id = String(lag.cabang_id);
    form.kode_lag = lag.kode_lag;
    form.nama_lag = lag.nama_lag;
    form.tanggal_target = lag.tanggal_target ? lag.tanggal_target.substring(0, 10) : '';
    form.tahun = lag.tahun;
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

    if (lagDiedit.value) {
        form.put(`/lag-measures/${lagDiedit.value.id}`, opsi);
    } else {
        form.post('/lag-measures', opsi);
    }
};

/* --- Hapus --- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const lagDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    lagDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = lagDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/lag-measures/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (lagDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Lag Measure" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Lag Measure</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Ukuran hasil per WIG dan cabang. Kode dibuat otomatis dari kode cabang dan tahun.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Lag
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari kode atau nama lag..." class="pl-9" />
                </div>

                <Select v-model="bidangFilter">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="Semua bidang" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="SEMUA">Semua bidang</SelectItem>
                        <SelectItem v-for="b in daftarBidang" :key="b" :value="b">{{ b }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="wigFilter">
                    <SelectTrigger class="w-72">
                        <SelectValue placeholder="Semua WIG" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="SEMUA">Semua WIG</SelectItem>
                        <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                            {{ w.kode_wig }} — {{ w.nama_wig }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="cabangFilter">
                    <SelectTrigger class="w-56">
                        <SelectValue placeholder="Semua cabang" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="SEMUA">Semua cabang</SelectItem>
                        <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                            {{ c.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ lags.total }} lag</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-44">Kode</TableHead>
                                <TableHead class="w-[34rem] min-w-[20rem]">Nama Lag</TableHead>
                                <TableHead class="w-28">Bidang</TableHead>
                                <TableHead>Cabang</TableHead>
                                <TableHead class="w-28 text-center">Target</TableHead>
                                <TableHead class="w-20 text-center">Lead</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(lag, index) in lags.data" :key="lag.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ lags.from + index }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono text-xs">{{ lag.kode_lag }}</Badge>
                                </TableCell>
                                <TableCell class="align-top text-sm">
                                    <div class="max-w-[34rem] whitespace-pre-wrap">{{ lag.nama_lag }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="lag.wig?.bidang" variant="outline" :class="kelasBidang(lag.wig.bidang)">{{ lag.wig.bidang }}</Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ lag.cabang?.nama ?? '—' }}</TableCell>
                                <TableCell class="text-muted-foreground text-center text-sm tabular-nums">
                                    {{ lag.tanggal_target ? lag.tanggal_target.substring(0, 10) : '—' }}
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ lag.lead_measures_count }}</TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(lag)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <!--
                                          Lag yang masih punya Lead memang ditolak server. Tombolnya
                                          dimatikan sejak awal supaya alasannya terbaca sebelum diklik.
                                        -->
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="lag.lead_measures_count > 0"
                                            :title="
                                                lag.lead_measures_count > 0
                                                    ? `Tidak dapat dihapus — masih punya ${lag.lead_measures_count} Lead Measure`
                                                    : 'Hapus'
                                            "
                                            @click="konfirmasiHapus(lag)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="lags.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="8" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <TrendingDown class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada Lag Measure yang cocok.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="lags" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ lagDiedit ? 'Edit Lag Measure' : 'Tambah Lag Measure' }}</DialogTitle>
                    <DialogDescription>
                        Pilih WIG dan cabang terlebih dahulu — kode lag akan terisi otomatis.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label>WIG</Label>
                        <Select v-model="form.wig_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih WIG" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                    {{ w.kode_wig }} — {{ w.nama_wig }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.wig_id" class="text-destructive text-sm">{{ form.errors.wig_id }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2 sm:col-span-2">
                            <Label>Cabang</Label>
                            <Select v-model="form.cabang_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih cabang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                        {{ c.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.cabang_id" class="text-destructive text-sm">
                                {{ form.errors.cabang_id }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="tahun">Tahun</Label>
                            <Input id="tahun" v-model.number="form.tahun" type="number" />
                            <p v-if="form.errors.tahun" class="text-destructive text-sm">{{ form.errors.tahun }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="kode_lag">Kode Lag</Label>
                            <Input id="kode_lag" v-model="form.kode_lag" class="font-mono" />
                            <p v-if="form.errors.kode_lag" class="text-destructive text-sm">
                                {{ form.errors.kode_lag }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="tanggal_target">Tanggal Target</Label>
                            <Input id="tanggal_target" v-model="form.tanggal_target" type="date" />
                            <p v-if="form.errors.tanggal_target" class="text-destructive text-sm">
                                {{ form.errors.tanggal_target }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_lag">Nama Lag</Label>
                        <Textarea id="nama_lag" v-model="form.nama_lag" rows="3" />
                        <p v-if="form.errors.nama_lag" class="text-destructive text-sm">{{ form.errors.nama_lag }}</p>
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
                    <AlertDialogTitle>Hapus Lag Measure ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong>{{ lagDihapus?.kode_lag }}</strong> akan dihapus permanen.
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
