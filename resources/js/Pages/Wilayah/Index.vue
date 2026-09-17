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
import { MapPin, Pencil, Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    wilayahs: { type: Object, required: true },
    filter: { type: Object, required: true },
});

/* --- Pencarian (debounce, tidak ada roundtrip per ketikan) --- */
const cari = ref(props.filter.cari);
let timer = null;

watch(cari, (nilai) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/wilayahs', { cari: nilai || undefined }, { preserveState: true, replace: true });
    }, 350);
});

/* --- Form tambah/edit --- */
const dialogTerbuka = ref(false);
const wilayahDiedit = ref(null);

const form = useForm({
    kode: '',
    nama: '',
    deskripsi: '',
});

const bukaTambah = () => {
    wilayahDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (wilayah) => {
    wilayahDiedit.value = wilayah;
    form.clearErrors();
    form.kode = wilayah.kode;
    form.nama = wilayah.nama;
    form.deskripsi = wilayah.deskripsi ?? '';
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

    if (wilayahDiedit.value) {
        form.put(`/wilayahs/${wilayahDiedit.value.id}`, opsi);
    } else {
        form.post('/wilayahs', opsi);
    }
};

/* --- Hapus --- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const wilayahDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    wilayahDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = wilayahDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/wilayahs/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (wilayahDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Kelola Wilayah" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Kelola Wilayah</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Data master wilayah beserta jumlah kantor cabang di bawahnya.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Wilayah
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari kode atau nama..." class="pl-9" />
                </div>
                <span class="text-muted-foreground ml-auto text-sm">
                    {{ wilayahs.total }} wilayah
                </span>
            </div>

            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-14 pl-4">#</TableHead>
                            <TableHead class="w-32">Kode</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Deskripsi</TableHead>
                            <TableHead class="w-28 text-center">Cabang</TableHead>
                            <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(wilayah, index) in wilayahs.data" :key="wilayah.id">
                            <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                {{ wilayahs.from + index }}
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary" class="font-mono">{{ wilayah.kode }}</Badge>
                            </TableCell>
                            <TableCell class="font-medium">{{ wilayah.nama }}</TableCell>
                            <TableCell class="text-muted-foreground max-w-md truncate">
                                {{ wilayah.deskripsi || '—' }}
                            </TableCell>
                            <TableCell class="text-center tabular-nums">{{ wilayah.cabangs_count }}</TableCell>
                            <TableCell class="pr-4">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(wilayah)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Hapus"
                                        class="text-destructive hover:text-destructive"
                                        @click="konfirmasiHapus(wilayah)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="wilayahs.data.length === 0" class="hover:bg-transparent">
                            <TableCell colspan="6" class="py-12">
                                <div class="text-muted-foreground flex flex-col items-center gap-2">
                                    <MapPin class="size-8 opacity-40" />
                                    <p class="text-sm">
                                        {{ filter.cari ? 'Tidak ada wilayah yang cocok.' : 'Belum ada data wilayah.' }}
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>

            <Paginasi :data="wilayahs" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ wilayahDiedit ? 'Edit Wilayah' : 'Tambah Wilayah' }}</DialogTitle>
                    <DialogDescription>
                        Kode wilayah harus unik dan maksimal 20 karakter.
                    </DialogDescription>
                </DialogHeader>

                <form class="min-w-0 space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label for="kode">Kode</Label>
                        <Input id="kode" v-model="form.kode" autofocus />
                        <p v-if="form.errors.kode" class="text-destructive text-sm">{{ form.errors.kode }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama">Nama</Label>
                        <Input id="nama" v-model="form.nama" />
                        <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="deskripsi">Deskripsi</Label>
                        <Input id="deskripsi" v-model="form.deskripsi" />
                        <p v-if="form.errors.deskripsi" class="text-destructive text-sm">
                            {{ form.errors.deskripsi }}
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
                    <AlertDialogTitle>Hapus wilayah ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Wilayah <strong>{{ wilayahDihapus?.nama }}</strong> akan dihapus permanen.
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
