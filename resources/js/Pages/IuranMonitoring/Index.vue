<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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
import { Coins, FileDown, FileText, Pencil, Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
    cabangs: { type: Array, required: true },
    items: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    maksPemdaPerMinggu: { type: Number, required: true },
    filter: { type: Object, required: true },
    terkunciCabang: { type: Boolean, default: false },
});

const STATUS = [
    { nilai: 'sudah', label: 'Sudah Bayar' },
    { nilai: 'sebagian', label: 'Bayar Sebagian' },
    { nilai: 'belum', label: 'Belum Bayar' },
];

const KELAS_STATUS = {
    sudah: 'border-success/30 bg-success/10 text-success',
    sebagian: 'border-warning/40 bg-warning/10 text-warning-foreground',
    belum: 'border-destructive/30 bg-destructive/10 text-destructive',
};

const labelStatus = (nilai) => STATUS.find((s) => s.nilai === nilai)?.label ?? '—';

/* ---------------- Filter ---------------- */
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));
const cabangFilter = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : 'semua');
const mingguFilter = ref(props.filter.minggu ? String(props.filter.minggu) : 'semua');
const statusFilter = ref(props.filter.status ?? 'semua');

const kueri = computed(() => ({
    tahun: tahun.value,
    bulan: bulan.value,
    cabang_id: cabangFilter.value === 'semua' ? undefined : cabangFilter.value,
    minggu: mingguFilter.value === 'semua' ? undefined : mingguFilter.value,
    status: statusFilter.value === 'semua' ? undefined : statusFilter.value,
}));

watch([tahun, bulan, cabangFilter, mingguFilter, statusFilter], () => {
    router.get('/monitoring-prioritas/iuran', kueri.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const tautanEkspor = (jenis) => {
    const params = new URLSearchParams(
        Object.entries(kueri.value).filter(([, v]) => v !== undefined)
    );

    return `/monitoring-prioritas/iuran/${jenis}?${params}`;
};

/* ---------------- Form ---------------- */
const dialogTerbuka = ref(false);
const itemDiedit = ref(null);

const form = useForm({
    cabang_id: props.terkunciCabang && props.filter.cabang_id ? String(props.filter.cabang_id) : '',
    tahun: props.filter.tahun,
    bulan: props.filter.bulan,
    minggu: 1,
    nama_pemda: '',
    tagihan: 0,
    status_bayar: 'belum',
    outstanding: 0,
    pic: '',
    kendala: '',
    keterangan: '',
    target_penyelesaian: '',
});

/* Outstanding mengikuti status bayar, sama seperti aturan di server. */
watch([() => form.status_bayar, () => form.tagihan], () => {
    if (form.status_bayar === 'sudah') {
        form.outstanding = 0;
    } else if (form.status_bayar === 'belum') {
        form.outstanding = form.tagihan;
    }
});

/** Berapa Pemda yang sudah terisi di minggu & cabang yang sedang dipilih di form. */
const terisiMingguIni = computed(() => {
    if (! form.cabang_id) {
        return 0;
    }

    return props.items.filter(
        (i) =>
            String(i.cabang_id) === String(form.cabang_id) &&
            i.minggu === Number(form.minggu) &&
            i.id !== itemDiedit.value?.id
    ).length;
});

const bukaTambah = () => {
    itemDiedit.value = null;
    form.reset();
    form.clearErrors();
    form.tahun = props.filter.tahun;
    form.bulan = props.filter.bulan;

    if (props.terkunciCabang && props.filter.cabang_id) {
        form.cabang_id = String(props.filter.cabang_id);
    }

    dialogTerbuka.value = true;
};

const bukaEdit = (item) => {
    itemDiedit.value = item;
    form.clearErrors();
    form.cabang_id = String(item.cabang_id);
    form.tahun = props.filter.tahun;
    form.bulan = props.filter.bulan;
    form.minggu = item.minggu;
    form.nama_pemda = item.nama_pemda;
    form.tagihan = item.tagihan;
    form.status_bayar = item.status_bayar;
    form.outstanding = item.outstanding;
    form.pic = item.pic;
    form.kendala = item.kendala;
    form.keterangan = item.keterangan;
    form.target_penyelesaian = item.target_penyelesaian ?? '';
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

    if (itemDiedit.value) {
        form.put(`/monitoring-prioritas/iuran/${itemDiedit.value.id}`, opsi);
    } else {
        form.post('/monitoring-prioritas/iuran', opsi);
    }
};

/* ---------------- Hapus ---------------- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const itemDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    itemDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = itemDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/monitoring-prioritas/iuran/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (itemDihapus.value = null),
    });
};

/* ---------------- Ringkasan ---------------- */
const total = computed(() => ({
    tagihan: props.items.reduce((j, i) => j + Number(i.tagihan || 0), 0),
    outstanding: props.items.reduce((j, i) => j + Number(i.outstanding || 0), 0),
}));

const rupiah = (n) => 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
</script>

<template>
    <Head title="Monitoring Iuran" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Monitoring Iuran Pemda</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Maksimal {{ maksPemdaPerMinggu }} Pemda per minggu untuk tiap kantor cabang.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <a :href="tautanEkspor('excel')">
                            <FileDown class="mr-1.5 size-4" />
                            Excel
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <a :href="tautanEkspor('pdf')">
                            <FileText class="mr-1.5 size-4" />
                            PDF
                        </a>
                    </Button>
                    <Button @click="bukaTambah">
                        <Plus class="mr-1.5 size-4" />
                        Tambah Data
                    </Button>
                </div>
            </div>
        </template>

        <!-- Filter -->
        <Card class="mb-4">
            <CardContent class="flex flex-wrap items-end gap-4">
                <div class="w-28 space-y-2">
                    <Label for="tahun">Tahun</Label>
                    <Input id="tahun" v-model.number="tahun" type="number" />
                </div>

                <div class="w-36 space-y-2">
                    <Label>Bulan</Label>
                    <Select v-model="bulan">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="(b, i) in namaBulan" :key="b" :value="String(i + 1)">{{ b }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="min-w-52 flex-1 space-y-2">
                    <Label>Cabang</Label>
                    <Select v-model="cabangFilter" :disabled="terkunciCabang">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua cabang" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua">Semua cabang</SelectItem>
                            <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">{{ c.nama }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-36 space-y-2">
                    <Label>Minggu</Label>
                    <Select v-model="mingguFilter">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua">Semua</SelectItem>
                            <SelectItem v-for="m in 5" :key="m" :value="String(m)">Minggu {{ m }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-44 space-y-2">
                    <Label>Status</Label>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua">Semua status</SelectItem>
                            <SelectItem v-for="s in STATUS" :key="s.nilai" :value="s.nilai">{{ s.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Ringkasan -->
        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Jumlah Data</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ items.length }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total Tagihan</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ rupiah(total.tagihan) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total Outstanding</p>
                    <p class="text-destructive mt-1 text-2xl font-semibold tabular-nums">
                        {{ rupiah(total.outstanding) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Tabel -->
        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-24 pl-4">Minggu</TableHead>
                                <TableHead class="w-40">Cabang</TableHead>
                                <TableHead class="min-w-[14rem]">Nama Pemda</TableHead>
                                <TableHead class="w-40 text-right">Tagihan</TableHead>
                                <TableHead class="w-36 text-center">Status</TableHead>
                                <TableHead class="w-40 text-right">Outstanding</TableHead>
                                <TableHead class="w-32">PIC</TableHead>
                                <TableHead class="w-32">Target</TableHead>
                                <TableHead class="min-w-[12rem]">Kendala</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in items" :key="item.id">
                                <TableCell class="pl-4 text-sm">Minggu {{ item.minggu }}</TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ item.cabang ?? '—' }}</TableCell>
                                <TableCell class="text-sm font-medium">{{ item.nama_pemda }}</TableCell>
                                <TableCell class="text-right text-sm tabular-nums">{{ rupiah(item.tagihan) }}</TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="KELAS_STATUS[item.status_bayar]">
                                        {{ labelStatus(item.status_bayar) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right text-sm tabular-nums">
                                    {{ rupiah(item.outstanding) }}
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ item.pic || '—' }}</TableCell>
                                <TableCell class="text-muted-foreground text-sm tabular-nums">
                                    {{ item.target_penyelesaian || '—' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm whitespace-pre-wrap">
                                    {{ item.kendala || '—' }}
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(item)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
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

                            <TableRow v-if="items.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="10" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Coins class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada data iuran untuk filter ini.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ itemDiedit ? 'Edit Data Iuran' : 'Tambah Data Iuran' }}</DialogTitle>
                    <DialogDescription>
                        Outstanding terisi otomatis: nol bila sudah bayar, sebesar tagihan bila belum bayar.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2 sm:col-span-2">
                            <Label>Cabang</Label>
                            <Select v-model="form.cabang_id" :disabled="terkunciCabang">
                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih cabang" /></SelectTrigger>
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
                            <Label>Minggu</Label>
                            <Select v-model.number="form.minggu">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="m in 5" :key="m" :value="m">Minggu {{ m }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-muted-foreground text-xs">
                                Terisi {{ terisiMingguIni }} dari {{ maksPemdaPerMinggu }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_pemda">Nama Pemda</Label>
                        <Input id="nama_pemda" v-model="form.nama_pemda" />
                        <p v-if="form.errors.nama_pemda" class="text-destructive text-sm">
                            {{ form.errors.nama_pemda }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="tagihan">Tagihan</Label>
                            <Input id="tagihan" v-model.number="form.tagihan" type="number" step="any" min="0" />
                            <p v-if="form.errors.tagihan" class="text-destructive text-sm">{{ form.errors.tagihan }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Status Bayar</Label>
                            <Select v-model="form.status_bayar">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in STATUS" :key="s.nilai" :value="s.nilai">
                                        {{ s.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="outstanding">Outstanding</Label>
                            <Input
                                id="outstanding"
                                v-model.number="form.outstanding"
                                type="number"
                                step="any"
                                min="0"
                                :disabled="form.status_bayar !== 'sebagian'"
                            />
                            <p v-if="form.errors.outstanding" class="text-destructive text-sm">
                                {{ form.errors.outstanding }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="pic">PIC</Label>
                            <Input id="pic" v-model="form.pic" />
                        </div>

                        <div class="space-y-2">
                            <Label for="target_penyelesaian">Target Penyelesaian</Label>
                            <Input id="target_penyelesaian" v-model="form.target_penyelesaian" type="date" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="kendala">Kendala</Label>
                        <Textarea id="kendala" v-model="form.kendala" rows="2" />
                    </div>

                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
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
                    <AlertDialogTitle>Hapus data iuran ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Data <strong>{{ itemDihapus?.nama_pemda }}</strong> pada Minggu
                        {{ itemDihapus?.minggu }} akan dihapus permanen.
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
