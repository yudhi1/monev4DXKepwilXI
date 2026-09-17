<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
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
import { Layers, Pencil, Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    kategoris: { type: Object, required: true },
    filter: { type: Object, required: true },
});

/* --- Pencarian (debounce, tidak ada roundtrip per ketikan) --- */
const cari = ref(props.filter.cari);
let timer = null;

watch(cari, (nilai) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/kinerja/kategori', { cari: nilai || undefined }, { preserveState: true, replace: true });
    }, 350);
});

/* --- Form tambah/edit --- */
const dialogTerbuka = ref(false);
const kategoriDiedit = ref(null);

const form = useForm({
    nama: '',
    keterangan: '',
    urutan: 0,
    is_active: true,
});

const bukaTambah = () => {
    kategoriDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (kategori) => {
    kategoriDiedit.value = kategori;
    form.clearErrors();
    form.nama = kategori.nama;
    form.keterangan = kategori.keterangan ?? '';
    form.urutan = kategori.urutan ?? 0;
    form.is_active = kategori.is_active;
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

    if (kategoriDiedit.value) {
        form.put(`/kinerja/kategori/${kategoriDiedit.value.id}`, opsi);
    } else {
        form.post('/kinerja/kategori', opsi);
    }
};

/* --- Hapus --- */
/*
 | Sasaran hapus dipisahkan dari keadaan buka/tutup dialog: AlertDialogAction
 | menutup dialog lebih dulu daripada @click kita, jadi mengosongkan sasaran
 | saat menutup akan membuat hapus() kehilangan acuannya.
 */
const kategoriDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    kategoriDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = kategoriDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/kinerja/kategori/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (kategoriDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Kategori Capaian Kinerja" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Kategori Capaian</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Lapis pertama Monitoring Kinerja. Indikator disusun di bawah kategori ini.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Kategori
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari nama kategori..." class="pl-9" />
                </div>
                <span class="text-muted-foreground ml-auto text-sm">{{ kategoris.total }} kategori</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead>Nama Kategori</TableHead>
                                <TableHead>Keterangan</TableHead>
                                <TableHead class="w-28 text-center">Indikator</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(kategori, index) in kategoris.data" :key="kategori.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ kategoris.from + index }}
                                </TableCell>
                                <TableCell class="font-medium">{{ kategori.nama }}</TableCell>
                                <TableCell class="text-muted-foreground max-w-md truncate">
                                    {{ kategori.keterangan || '—' }}
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ kategori.jumlahIndikator }}</TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="kategori.is_active ? 'secondary' : 'outline'">
                                        {{ kategori.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(kategori)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(kategori)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="kategoris.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="6" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Layers class="size-8 opacity-40" />
                                        <p class="text-sm">
                                            {{ filter.cari ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori capaian.' }}
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="kategoris" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ kategoriDiedit ? 'Edit Kategori' : 'Tambah Kategori' }}</DialogTitle>
                    <DialogDescription>
                        Nama kategori harus unik. Kategori nonaktif tidak muncul saat memilih indikator.
                    </DialogDescription>
                </DialogHeader>

                <form class="min-w-0 space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label for="nama">Nama Kategori</Label>
                        <Input id="nama" v-model="form.nama" autofocus />
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
                    <AlertDialogTitle>Hapus kategori ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Kategori <strong>{{ kategoriDihapus?.nama }}</strong> akan dihapus permanen.
                        Kategori yang masih memiliki indikator tidak dapat dihapus.
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
