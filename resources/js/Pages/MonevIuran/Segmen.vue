<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ListOrdered, Pencil, Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
    segmens: { type: Array, required: true },
    urutanBerikutnya: { type: Number, required: true },
});

const dialogTerbuka = ref(false);
const segmenDiedit = ref(null);

const form = useForm({
    nama: '',
    urutan: props.urutanBerikutnya,
    is_active: true,
});

const bukaTambah = () => {
    segmenDiedit.value = null;
    form.reset();
    form.clearErrors();
    form.urutan = props.urutanBerikutnya;
    dialogTerbuka.value = true;
};

const bukaEdit = (segmen) => {
    segmenDiedit.value = segmen;
    form.clearErrors();
    form.nama = segmen.nama;
    form.urutan = segmen.urutan;
    form.is_active = segmen.is_active;
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

    if (segmenDiedit.value) {
        form.put(`/monev-iuran/segmen/${segmenDiedit.value.id}`, opsi);
    } else {
        form.post('/monev-iuran/segmen', opsi);
    }
};

const segmenDihapus = ref(null);

const hapus = () => {
    router.delete(`/monev-iuran/segmen/${segmenDihapus.value.id}`, {
        preserveScroll: true,
        onFinish: () => (segmenDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Master Segmen" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Master Segmen</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Segmen kepesertaan yang dipakai pada input realisasi iuran. Urutan menentukan posisi baris.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Segmen
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-24 pl-4 text-center">Urutan</TableHead>
                            <TableHead>Nama Segmen</TableHead>
                            <TableHead class="w-32 text-center">Realisasi</TableHead>
                            <TableHead class="w-28 text-center">Status</TableHead>
                            <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="segmen in segmens" :key="segmen.id">
                            <TableCell class="text-muted-foreground pl-4 text-center tabular-nums">
                                {{ segmen.urutan }}
                            </TableCell>
                            <TableCell class="text-sm font-medium">{{ segmen.nama }}</TableCell>
                            <TableCell class="text-center text-sm tabular-nums">
                                {{ segmen.realisasis_count }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    variant="outline"
                                    :class="
                                        segmen.is_active
                                            ? 'border-success/30 bg-success/10 text-success'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{ segmen.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="pr-4">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(segmen)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Hapus"
                                        class="text-destructive hover:text-destructive"
                                        @click="segmenDihapus = segmen"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="segmens.length === 0" class="hover:bg-transparent">
                            <TableCell colspan="5" class="py-12">
                                <div class="text-muted-foreground flex flex-col items-center gap-2">
                                    <ListOrdered class="size-8 opacity-40" />
                                    <p class="text-sm">Belum ada segmen.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ segmenDiedit ? 'Edit Segmen' : 'Tambah Segmen' }}</DialogTitle>
                    <DialogDescription>
                        Segmen nonaktif tidak muncul di halaman input realisasi, tapi datanya tetap tersimpan.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="space-y-2">
                        <Label for="nama">Nama Segmen</Label>
                        <Input id="nama" v-model="form.nama" autofocus />
                        <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="urutan">Urutan</Label>
                        <Input id="urutan" v-model.number="form.urutan" type="number" min="0" class="w-32" />
                        <p v-if="form.errors.urutan" class="text-destructive text-sm">{{ form.errors.urutan }}</p>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label for="is_active">Aktif</Label>
                            <p class="text-muted-foreground text-sm">Tampilkan segmen ini di input realisasi.</p>
                        </div>
                        <Switch id="is_active" v-model="form.is_active" />
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

        <AlertDialog :open="!!segmenDihapus" @update:open="(v) => !v && (segmenDihapus = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus segmen ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Segmen <strong>{{ segmenDihapus?.nama }}</strong> akan dihapus permanen.
                        Segmen yang sudah punya data realisasi tidak dapat dihapus.
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
