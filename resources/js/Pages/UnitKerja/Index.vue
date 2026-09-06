<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
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
import { Building2, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { kelasAktif } from '@/lib/status';

const props = defineProps({
    units: { type: Object, required: true },
    cabangs: { type: Array, required: true },
    filter: { type: Object, required: true },
});

const TINGKAT = [
    { nilai: 'wilayah', label: 'Kedeputian Wilayah' },
    { nilai: 'cabang', label: 'Kantor Cabang' },
];

/* --- Saringan --- */
const cari = ref(props.filter.cari);
const tingkat = ref(props.filter.tingkat || 'semua');
const cabang = ref(props.filter.cabang ? String(props.filter.cabang) : 'semua');
let timer = null;

const muatUlang = () =>
    router.get(
        '/unit-kerja',
        {
            cari: cari.value || undefined,
            tingkat: tingkat.value === 'semua' ? undefined : tingkat.value,
            cabang: cabang.value === 'semua' ? undefined : cabang.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});
watch([tingkat, cabang], muatUlang);

/* --- Form --- */
const dialogTerbuka = ref(false);
const unitDiedit = ref(null);

const form = useForm({
    kode: '',
    nama: '',
    tingkat: 'cabang',
    cabang_id: null,
    urutan: 0,
    is_active: true,
});

/* Bidang tingkat wilayah tidak punya kantor cabang. */
watch(
    () => form.tingkat,
    (nilai) => {
        if (nilai === 'wilayah') {
            form.cabang_id = null;
        }
    }
);

const bukaTambah = () => {
    unitDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (u) => {
    unitDiedit.value = u;
    form.clearErrors();
    form.kode = u.kode;
    form.nama = u.nama;
    form.tingkat = u.tingkat;
    form.cabang_id = u.cabang_id ? String(u.cabang_id) : null;
    form.urutan = u.urutan;
    form.is_active = u.is_active;
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

    if (unitDiedit.value) {
        form.put(`/unit-kerja/${unitDiedit.value.id}`, opsi);
    } else {
        form.post('/unit-kerja', opsi);
    }
};

/* --- Hapus --- */
const unitDihapus = ref(null);
const dialogHapus = ref(false);

const konfirmasiHapus = (u) => {
    unitDihapus.value = u;
    dialogHapus.value = true;
};

const hapus = () => {
    const sasaran = unitDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/unit-kerja/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (unitDihapus.value = null),
    });
};

const labelTingkat = (n) => TINGKAT.find((t) => t.nilai === n)?.label ?? n;

const bisaDihapus = computed(() => (unitDihapus.value?.jumlahPegawai ?? 0) === 0);
</script>

<template>
    <Head title="Bidang / Unit Kerja" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Bidang / Unit Kerja</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Bidang di Kedeputian Wilayah dan di tiap kantor cabang. Bidang cabang berdiri
                        sendiri per kantor — PMU KC Denpasar berbeda dari PMU KC Kupang.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Bidang
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari kode atau nama bidang..." class="pl-9" />
                </div>

                <Select v-model="tingkat">
                    <SelectTrigger class="w-52"><SelectValue placeholder="Semua tingkat" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua tingkat</SelectItem>
                        <SelectItem v-for="t in TINGKAT" :key="t.nilai" :value="t.nilai">{{ t.label }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="cabang">
                    <SelectTrigger class="w-56"><SelectValue placeholder="Semua kantor" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua kantor</SelectItem>
                        <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">{{ c.nama }}</SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ units.total }} bidang</span>
            </div>

            <CardContent class="p-0">
                <div class="gulir-terlihat overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-32">Kode</TableHead>
                                <TableHead>Nama Bidang</TableHead>
                                <TableHead class="w-56">Kantor</TableHead>
                                <TableHead class="w-24 text-center">Pegawai</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(u, index) in units.data" :key="u.id">
                                <TableCell class="pl-4">
                                    <span
                                        class="bg-primary/10 text-primary inline-flex size-7 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                                    >
                                        {{ units.from + index }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono">{{ u.kode }}</Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ u.nama }}</TableCell>
                                <TableCell class="text-muted-foreground">
                                    <span class="flex items-center gap-1 text-sm">
                                        <Building2 class="size-3 shrink-0" />
                                        {{ u.induk }}
                                    </span>
                                    <span class="text-xs">{{ labelTingkat(u.tingkat) }}</span>
                                </TableCell>
                                <TableCell class="text-center text-sm tabular-nums">{{ u.jumlahPegawai }}</TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="kelasAktif(u.is_active)">
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Ubah" @click="bukaEdit(u)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(u)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="units.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="text-muted-foreground py-12 text-center text-sm">
                                    Tidak ada bidang yang cocok dengan saringan ini.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <Paginasi :data="units" />
            </CardContent>
        </Card>

        <!-- ===== Form bidang ===== -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ unitDiedit ? 'Ubah Bidang' : 'Tambah Bidang' }}</DialogTitle>
                    <DialogDescription>
                        Kode boleh sama antar kantor — "PMU" ada di setiap cabang.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label for="kode">Kode</Label>
                            <Input id="kode" v-model="form.kode" placeholder="PMU" />
                            <p v-if="form.errors.kode" class="text-destructive text-sm">{{ form.errors.kode }}</p>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="nama">Nama Bidang</Label>
                            <Input id="nama" v-model="form.nama" placeholder="Bidang PMU" />
                            <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Tingkat</Label>
                        <Select v-model="form.tingkat">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in TINGKAT" :key="t.nilai" :value="t.nilai">
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="form.tingkat === 'cabang'" class="space-y-1.5">
                        <Label>Kantor Cabang</Label>
                        <Select v-model="form.cabang_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih kantor cabang..." />
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

                    <div class="space-y-1.5">
                        <Label for="urutan">Urutan tampil</Label>
                        <Input id="urutan" v-model="form.urutan" type="number" min="0" />
                    </div>

                    <div class="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label for="aktif">Status aktif</Label>
                            <p class="text-muted-foreground text-sm">
                                Bidang nonaktif tidak muncul saat memilih unit kerja pegawai.
                            </p>
                        </div>
                        <Switch id="aktif" v-model="form.is_active" />
                    </div>

                    <DialogFooter>
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
                    <AlertDialogTitle>Hapus bidang ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="bisaDihapus">
                            <span class="font-medium">{{ unitDihapus?.nama }}</span> —
                            {{ unitDihapus?.induk }} akan dihapus permanen.
                        </template>
                        <template v-else>
                            Bidang ini masih dipakai
                            <span class="font-medium">{{ unitDihapus?.jumlahPegawai }} pegawai</span>,
                            jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Ubah bila sudah
                            tidak digunakan.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        v-if="bisaDihapus"
                        class="bg-destructive hover:bg-destructive/90"
                        @click="hapus"
                    >
                        Ya, Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
