<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Download, Pencil, Plus, Search, Trash2, Upload, Users } from '@lucide/vue';
import { kelasAktif } from '@/lib/status';

const props = defineProps({
    pegawais: { type: Object, required: true },
    unitKerjas: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    roleAkun: { type: Object, required: true },
    filter: { type: Object, required: true },
});

const page = usePage();
const idSaya = computed(() => page.props.auth.user?.id);

const TINGKAT = [
    { nilai: 'wilayah', label: 'Kedeputian Wilayah' },
    { nilai: 'cabang', label: 'Kantor Cabang' },
];

/* --- Filter --- */
const cari = ref(props.filter.cari);
const tingkatFilter = ref(props.filter.tingkat ?? 'semua');
const unitFilter = ref(props.filter.unit_kerja ? String(props.filter.unit_kerja) : 'semua');
const roleFilter = ref(props.filter.pm_role ?? 'semua');
let timer = null;

const muatUlang = () =>
    router.get(
        '/pegawai',
        {
            cari: cari.value || undefined,
            tingkat: tingkatFilter.value === 'semua' ? undefined : tingkatFilter.value,
            unit_kerja: unitFilter.value === 'semua' ? undefined : unitFilter.value,
            pm_role: roleFilter.value === 'semua' ? undefined : roleFilter.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});

watch([tingkatFilter, unitFilter, roleFilter], muatUlang);

/* Dropdown filter unit dikelompokkan per kantor agar 70 unit tetap terbaca. */
const unitBerkelompok = computed(() => {
    const kelompok = new Map();

    for (const u of props.unitKerjas) {
        if (! kelompok.has(u.induk)) {
            kelompok.set(u.induk, []);
        }
        kelompok.get(u.induk).push(u);
    }

    return [...kelompok.entries()].map(([induk, units]) => ({ induk, units }));
});

/* --- Form --- */
const dialogTerbuka = ref(false);
const pegawaiDiedit = ref(null);

const form = useForm({
    name: '',
    password: '',
    tingkat: 'wilayah',
    cabang_id: null,
    unit_kerja_id: null,
    jabatan: '',
    pm_role: 'member',
    is_active: true,
});

/*
 | Bidang dipilih bertingkat: tentukan dulu Kedeputian Wilayah atau Kantor
 | Cabang, baru bidangnya menyusul — persis alur yang dipakai di lapangan,
 | dan menghindari daftar 70 unit dalam satu dropdown datar.
 */
const bidangTersedia = computed(() => {
    if (form.tingkat === 'wilayah') {
        return props.unitKerjas.filter((u) => u.tingkat === 'wilayah');
    }

    if (! form.cabang_id) {
        return [];
    }

    return props.unitKerjas.filter(
        (u) => u.tingkat === 'cabang' && String(u.cabang_id) === String(form.cabang_id)
    );
});

/* Bidang yang tidak lagi cocok setelah tingkat/cabang berubah harus dilepas. */
watch([() => form.tingkat, () => form.cabang_id], () => {
    const masihCocok = bidangTersedia.value.some((u) => String(u.id) === String(form.unit_kerja_id));

    if (! masihCocok) {
        form.unit_kerja_id = null;
    }

    if (form.tingkat === 'wilayah') {
        form.cabang_id = null;
    }
});

const bukaTambah = () => {
    pegawaiDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (pegawai) => {
    pegawaiDiedit.value = pegawai;
    form.clearErrors();
    form.name = pegawai.name;
    form.password = '';
    form.tingkat = pegawai.tingkat ?? 'wilayah';

    const unit = props.unitKerjas.find((u) => u.id === pegawai.unit_kerja_id);
    form.cabang_id = unit?.cabang_id ? String(unit.cabang_id) : null;
    form.unit_kerja_id = pegawai.unit_kerja_id ? String(pegawai.unit_kerja_id) : null;

    form.jabatan = pegawai.jabatan ?? '';
    form.pm_role = pegawai.pm_role ?? 'member';
    form.is_active = pegawai.is_active;
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

    if (pegawaiDiedit.value) {
        form.put(`/pegawai/${pegawaiDiedit.value.id}`, opsi);
    } else {
        form.post('/pegawai', opsi);
    }
};

/* --- Impor Excel --- */
const dialogImpor = ref(false);
const formImpor = useForm({ file: null });

const pilihBerkas = (e) => (formImpor.file = e.target.files?.[0] ?? null);

const kirimImpor = () =>
    formImpor.post('/pegawai/impor', {
        preserveScroll: true,
        onSuccess: () => {
            dialogImpor.value = false;
            formImpor.reset();
        },
    });

/* --- Hapus --- */
const pegawaiDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    pegawaiDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = pegawaiDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/pegawai/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (pegawaiDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Akun Project Management" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Akun Project Management</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Akun perorangan pegawai, terikat satu bidang. Akun unit kerja untuk
                        Monev 4DX diurus di halaman Akun Monev 4DX.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="dialogImpor = true">
                        <Upload class="mr-1.5 size-4" />
                        Impor Excel
                    </Button>
                    <Button @click="bukaTambah">
                        <Plus class="mr-1.5 size-4" />
                        Tambah Pegawai
                    </Button>
                </div>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari nama atau jabatan..." class="pl-9" />
                </div>

                <Select v-model="tingkatFilter">
                    <SelectTrigger class="w-48"><SelectValue placeholder="Semua tingkat" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua tingkat</SelectItem>
                        <SelectItem v-for="t in TINGKAT" :key="t.nilai" :value="t.nilai">{{ t.label }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="unitFilter">
                    <SelectTrigger class="w-60"><SelectValue placeholder="Semua bidang" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua bidang</SelectItem>
                        <SelectGroup v-for="k in unitBerkelompok" :key="k.induk">
                            <SelectLabel>{{ k.induk }}</SelectLabel>
                            <SelectItem v-for="u in k.units" :key="u.id" :value="String(u.id)">
                                {{ u.nama }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <Select v-model="roleFilter">
                    <SelectTrigger class="w-44"><SelectValue placeholder="Semua role" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua role</SelectItem>
                        <SelectItem v-for="(meta, kunci) in roleAkun" :key="kunci" :value="kunci">
                            {{ meta.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ pegawais.total }} pegawai</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead>Nama Pegawai</TableHead>
                                <TableHead>Unit Kerja</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(pegawai, index) in pegawais.data" :key="pegawai.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ pegawais.from + index }}
                                </TableCell>
                                <TableCell>
                                    <p class="font-medium">
                                        {{ pegawai.name }}
                                        <Badge v-if="pegawai.id === idSaya" variant="outline" class="ml-1.5">
                                            Anda
                                        </Badge>
                                    </p>
                                    <p v-if="pegawai.jabatan" class="text-muted-foreground text-xs">
                                        {{ pegawai.jabatan }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm">{{ pegawai.unitKerja }}</p>
                                    <p class="text-muted-foreground text-xs">{{ pegawai.induk }}</p>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {{ roleAkun[pegawai.pm_role]?.label ?? '—' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="kelasAktif(pegawai.is_active)">
                                        {{ pegawai.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(pegawai)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="pegawai.id === idSaya"
                                            @click="konfirmasiHapus(pegawai)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="pegawais.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="6" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Users class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada pegawai yang cocok dengan filter ini.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <Paginasi :data="pegawais" />
            </CardContent>
        </Card>

        <!-- ===== Form pegawai ===== -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ pegawaiDiedit ? 'Ubah Pegawai' : 'Tambah Pegawai' }}</DialogTitle>
                    <DialogDescription>
                        Email dibuat otomatis dari nama. Wilayah dan cabang mengikuti bidang yang dipilih.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label for="nama-pegawai">Nama Pegawai</Label>
                        <Input id="nama-pegawai" v-model="form.name" />
                        <p v-if="form.errors.name" class="text-destructive text-sm">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password-pegawai">Password</Label>
                        <Input
                            id="password-pegawai"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="pegawaiDiedit ? 'Kosongkan bila tidak diubah' : ''"
                        />
                        <p v-if="form.errors.password" class="text-destructive text-sm">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Unit kerja: tingkat dulu, lalu bidang -->
                    <div class="space-y-2">
                        <Label>Unit Kerja</Label>
                        <Select v-model="form.tingkat">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in TINGKAT" :key="t.nilai" :value="t.nilai">
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="form.tingkat === 'cabang'" class="space-y-2">
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
                    </div>

                    <div class="space-y-2">
                        <Label>Bidang</Label>
                        <Select v-model="form.unit_kerja_id" :disabled="bidangTersedia.length === 0">
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="
                                        form.tingkat === 'cabang' && ! form.cabang_id
                                            ? 'Pilih kantor cabang dulu'
                                            : 'Pilih bidang...'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="u in bidangTersedia" :key="u.id" :value="String(u.id)">
                                    {{ u.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.unit_kerja_id" class="text-destructive text-sm">
                            {{ form.errors.unit_kerja_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="jabatan-pegawai">Jabatan</Label>
                        <Input id="jabatan-pegawai" v-model="form.jabatan" placeholder="Staf / Kepala Bidang" />
                    </div>

                    <div class="space-y-2">
                        <Label>Role</Label>
                        <Select v-model="form.pm_role">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(meta, kunci) in roleAkun" :key="kunci" :value="kunci">
                                    {{ meta.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-muted-foreground text-xs">
                            {{ roleAkun[form.pm_role]?.keterangan }}
                        </p>
                        <p v-if="form.errors.pm_role" class="text-destructive text-sm">{{ form.errors.pm_role }}</p>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label for="pegawai-aktif">Status aktif</Label>
                            <p class="text-muted-foreground text-sm">
                                Pegawai nonaktif tidak dapat masuk ke aplikasi.
                            </p>
                        </div>
                        <Switch id="pegawai-aktif" v-model="form.is_active" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogTerbuka = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ===== Impor Excel ===== -->
        <Dialog v-model:open="dialogImpor">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Impor Pegawai</DialogTitle>
                    <DialogDescription>
                        Nama yang sudah ada akan diperbarui, bukan diduplikasi.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="bg-muted/50 space-y-2 rounded-lg border p-3 text-sm">
                        <p class="font-medium">Kolom yang dibaca</p>
                        <p class="text-muted-foreground">
                            <code>nama</code> · <code>jabatan</code> · <code>bidang</code> ·
                            <code>cabang</code> · <code>role</code>
                        </p>
                        <p class="text-muted-foreground text-xs">
                            Kolom <code>cabang</code> dikosongkan untuk pegawai di Kedeputian Wilayah.
                            Kolom <code>role</code> boleh kosong — bawaannya Member. Password awal
                            akun baru: <code>monev2026</code>.
                        </p>
                        <a
                            href="/pegawai/impor/template"
                            class="text-primary inline-flex items-center gap-1 text-sm font-medium"
                        >
                            <Download class="size-4" />
                            Unduh template Excel
                        </a>
                    </div>

                    <div class="space-y-2">
                        <Label for="berkas-pegawai">Berkas Excel</Label>
                        <Input id="berkas-pegawai" type="file" accept=".xlsx,.xls,.csv" @change="pilihBerkas" />
                        <p v-if="formImpor.errors.file" class="text-destructive text-sm">
                            {{ formImpor.errors.file }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="dialogImpor = false">Batal</Button>
                    <Button :disabled="! formImpor.file || formImpor.processing" @click="kirimImpor">
                        <Upload class="mr-1.5 size-4" />
                        Unggah
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ===== Konfirmasi hapus ===== -->
        <AlertDialog v-model:open="dialogHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus pegawai ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Akun <span class="font-medium">{{ pegawaiDihapus?.name }}</span> akan dihapus.
                        Task yang pernah ditugaskan kepadanya akan kehilangan PIC.
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
