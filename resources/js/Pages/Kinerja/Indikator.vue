<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
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
import { ListChecks, Pencil, Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    indikators: { type: Object, required: true },
    kategoris: { type: Array, required: true },
    filter: { type: Object, required: true },
});

/* --- Filter & pencarian --- */
/*
 | "semua" dipakai sebagai nilai penanda karena SelectItem reka-ui menolak
 | value string kosong — nilai kosong dianggap "tak ada pilihan" olehnya.
 */
const cari = ref(props.filter.cari);
const kategori = ref(props.filter.kategori ? String(props.filter.kategori) : 'semua');
let timer = null;

const muat = () => {
    router.get(
        '/kinerja/indikator',
        {
            cari: cari.value || undefined,
            kategori: kategori.value === 'semua' ? undefined : kategori.value,
        },
        { preserveState: true, replace: true }
    );
};

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muat, 350);
});

watch(kategori, muat);

/* --- Form tambah/edit --- */
const dialogTerbuka = ref(false);
const indikatorDiedit = ref(null);

const form = useForm({
    kinerja_kategori_id: '',
    nama: '',
    keterangan: '',
    urutan: 0,
    is_active: true,
});

const bukaTambah = () => {
    indikatorDiedit.value = null;
    form.reset();
    form.clearErrors();

    // Kategori yang sedang difilter dipakai sebagai nilai awal.
    if (kategori.value !== 'semua') {
        form.kinerja_kategori_id = kategori.value;
    }

    dialogTerbuka.value = true;
};

const bukaEdit = (indikator) => {
    indikatorDiedit.value = indikator;
    form.clearErrors();
    form.kinerja_kategori_id = String(indikator.kinerja_kategori_id);
    form.nama = indikator.nama;
    form.keterangan = indikator.keterangan ?? '';
    form.urutan = indikator.urutan ?? 0;
    form.is_active = indikator.is_active;
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

    if (indikatorDiedit.value) {
        form.put(`/kinerja/indikator/${indikatorDiedit.value.id}`, opsi);
    } else {
        form.post('/kinerja/indikator', opsi);
    }
};

/* --- Hapus --- */
const indikatorDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    indikatorDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = indikatorDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/kinerja/indikator/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (indikatorDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Indikator Capaian Kinerja" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Indikator Capaian</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Setiap indikator berada di bawah satu kategori. Pilih kategorinya lebih dulu.
                    </p>
                </div>
                <Button :disabled="kategoris.length === 0" @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Indikator
                </Button>
            </div>
        </template>

        <!-- Tanpa kategori, indikator tidak punya tempat bernaung. -->
        <Card v-if="kategoris.length === 0" class="mb-4">
            <CardContent class="text-muted-foreground flex flex-wrap items-center gap-3 text-sm">
                <span>Belum ada kategori aktif. Buat kategori capaian lebih dulu.</span>
                <Button as-child size="sm" variant="outline">
                    <Link href="/kinerja/kategori">Ke Kategori Capaian</Link>
                </Button>
            </CardContent>
        </Card>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari nama indikator..." class="pl-9" />
                </div>

                <Select v-model="kategori">
                    <SelectTrigger class="w-64"><SelectValue placeholder="Semua kategori" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua kategori</SelectItem>
                        <SelectItem v-for="k in kategoris" :key="k.id" :value="String(k.id)">
                            {{ k.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ indikators.total }} indikator</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead>Nama Indikator</TableHead>
                                <TableHead>Keterangan</TableHead>
                                <TableHead class="w-20 text-center">File</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(indikator, index) in indikators.data" :key="indikator.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ indikators.from + index }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{ indikator.kategori }}</Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ indikator.nama }}</TableCell>
                                <TableCell class="text-muted-foreground max-w-md truncate">
                                    {{ indikator.keterangan || '—' }}
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ indikator.jumlahFile }}</TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="indikator.is_active ? 'secondary' : 'outline'">
                                        {{ indikator.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(indikator)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(indikator)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="indikators.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <ListChecks class="size-8 opacity-40" />
                                        <p class="text-sm">
                                            {{ filter.cari || filter.kategori
                                                ? 'Tidak ada indikator yang cocok.'
                                                : 'Belum ada indikator capaian.' }}
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="indikators" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ indikatorDiedit ? 'Edit Indikator' : 'Tambah Indikator' }}</DialogTitle>
                    <DialogDescription>
                        Pilih kategori terlebih dahulu, lalu isi nama indikatornya.
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
                        <Label for="nama">Nama Indikator</Label>
                        <Input id="nama" v-model="form.nama" />
                        <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan</Label>
                        <Input id="keterangan" v-model="form.keterangan" placeholder="Opsional" />
                        <p v-if="form.errors.keterangan" class="text-destructive text-sm">
                            {{ form.errors.keterangan }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="urutan">Urutan Tampil</Label>
                        <Input id="urutan" v-model="form.urutan" type="number" min="0" class="w-32" />
                        <p v-if="form.errors.urutan" class="text-destructive text-sm">{{ form.errors.urutan }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch id="is_active" v-model="form.is_active" />
                        <Label for="is_active" class="font-normal">Aktif</Label>
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
                    <AlertDialogTitle>Hapus indikator ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Indikator <strong>{{ indikatorDihapus?.nama }}</strong> akan dihapus permanen.
                        Indikator yang masih memiliki file capaian tidak dapat dihapus.
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
