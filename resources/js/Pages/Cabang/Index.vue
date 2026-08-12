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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Pencil, Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    cabangs: { type: Object, required: true },
    wilayahs: { type: Array, required: true },
    filter: { type: Object, required: true },
});

/* --- Filter --- */
const cari = ref(props.filter.cari);
const wilayahFilter = ref(props.filter.wilayah_id ? String(props.filter.wilayah_id) : 'semua');
let timer = null;

const muatUlang = () =>
    router.get(
        '/cabangs',
        {
            cari: cari.value || undefined,
            wilayah_id: wilayahFilter.value === 'semua' ? undefined : wilayahFilter.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});

watch(wilayahFilter, muatUlang);

/* --- Form --- */
const dialogTerbuka = ref(false);
const cabangDiedit = ref(null);

const form = useForm({
    wilayah_id: '',
    kode: '',
    nama: '',
    alamat: '',
});

const bukaTambah = () => {
    cabangDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (cabang) => {
    cabangDiedit.value = cabang;
    form.clearErrors();
    form.wilayah_id = String(cabang.wilayah_id);
    form.kode = cabang.kode;
    form.nama = cabang.nama;
    form.alamat = cabang.alamat ?? '';
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

    if (cabangDiedit.value) {
        form.put(`/cabangs/${cabangDiedit.value.id}`, opsi);
    } else {
        form.post('/cabangs', opsi);
    }
};

/* --- Hapus --- */
const cabangDihapus = ref(null);

const hapus = () => {
    router.delete(`/cabangs/${cabangDihapus.value.id}`, {
        preserveScroll: true,
        onFinish: () => (cabangDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Kelola Cabang" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Kelola Cabang</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Data master kantor cabang beserta wilayah induknya.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Cabang
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari kode atau nama..." class="pl-9" />
                </div>

                <Select v-model="wilayahFilter">
                    <SelectTrigger class="w-56">
                        <SelectValue placeholder="Semua wilayah" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua wilayah</SelectItem>
                        <SelectItem v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                            {{ w.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ cabangs.total }} cabang</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-32">Kode</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Wilayah</TableHead>
                                <TableHead>Alamat</TableHead>
                                <TableHead class="w-20 text-center">User</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(cabang, index) in cabangs.data" :key="cabang.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ cabangs.from + index }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono">{{ cabang.kode }}</Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ cabang.nama }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ cabang.wilayah?.nama ?? '—' }}</TableCell>
                                <TableCell class="text-muted-foreground max-w-xs truncate">
                                    {{ cabang.alamat || '—' }}
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ cabang.users_count }}</TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(cabang)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="cabangDihapus = cabang"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="cabangs.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Building2 class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada cabang yang cocok.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="cabangs" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ cabangDiedit ? 'Edit Cabang' : 'Tambah Cabang' }}</DialogTitle>
                    <DialogDescription>Setiap cabang harus berada di bawah satu wilayah.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label>Wilayah</Label>
                        <Select v-model="form.wilayah_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih wilayah" />
                            </SelectTrigger>
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="kode">Kode</Label>
                            <Input id="kode" v-model="form.kode" />
                            <p v-if="form.errors.kode" class="text-destructive text-sm">{{ form.errors.kode }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="nama">Nama</Label>
                            <Input id="nama" v-model="form.nama" />
                            <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="alamat">Alamat</Label>
                        <Input id="alamat" v-model="form.alamat" />
                        <p v-if="form.errors.alamat" class="text-destructive text-sm">{{ form.errors.alamat }}</p>
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
        <AlertDialog :open="!!cabangDihapus" @update:open="(v) => !v && (cabangDihapus = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus cabang ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Cabang <strong>{{ cabangDihapus?.nama }}</strong> akan dihapus permanen.
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
