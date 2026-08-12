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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil, Plus, Search, Trash2, Users } from '@lucide/vue';
import { kelasAktif } from '@/lib/status';

const props = defineProps({
    users: { type: Object, required: true },
    wilayahs: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    filter: { type: Object, required: true },
});

const page = usePage();
const idSaya = computed(() => page.props.auth.user?.id);

const ROLE = [
    { nilai: 'admin', label: 'Admin' },
    { nilai: 'kedeputian_wilayah', label: 'Kedeputian Wilayah' },
    { nilai: 'kantor_cabang', label: 'Kantor Cabang' },
];

const labelRole = (nilai) => ROLE.find((r) => r.nilai === nilai)?.label ?? '—';

/* --- Filter --- */
const cari = ref(props.filter.cari);
const roleFilter = ref(props.filter.role ?? 'semua');
let timer = null;

const muatUlang = () =>
    router.get(
        '/users',
        {
            cari: cari.value || undefined,
            role: roleFilter.value === 'semua' ? undefined : roleFilter.value,
        },
        { preserveState: true, replace: true }
    );

watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(muatUlang, 350);
});

watch(roleFilter, muatUlang);

/* --- Form --- */
const dialogTerbuka = ref(false);
const userDiedit = ref(null);

const form = useForm({
    name: '',
    password: '',
    role: 'kantor_cabang',
    wilayah_id: null,
    cabang_id: null,
    is_active: true,
    alamat: '',
});

/* Cabang dipersempit mengikuti wilayah yang dipilih, supaya tidak salah pasang. */
const cabangTersedia = computed(() =>
    form.wilayah_id ? props.cabangs.filter((c) => String(c.wilayah_id) === String(form.wilayah_id)) : props.cabangs
);

watch(
    () => form.wilayah_id,
    () => {
        const masihCocok = cabangTersedia.value.some((c) => String(c.id) === String(form.cabang_id));

        if (! masihCocok) {
            form.cabang_id = null;
        }
    }
);

const bukaTambah = () => {
    userDiedit.value = null;
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const bukaEdit = (user) => {
    userDiedit.value = user;
    form.clearErrors();
    form.name = user.name;
    form.password = '';
    form.role = user.role ?? 'kantor_cabang';
    form.wilayah_id = user.wilayah_id ? String(user.wilayah_id) : null;
    form.cabang_id = user.cabang_id ? String(user.cabang_id) : null;
    form.is_active = user.is_active;
    form.alamat = user.alamat ?? '';
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

    if (userDiedit.value) {
        form.put(`/users/${userDiedit.value.id}`, opsi);
    } else {
        form.post('/users', opsi);
    }
};

/* --- Hapus --- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const userDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    userDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = userDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/users/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (userDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Kelola User" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Kelola User</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Akun pengguna beserta role dan penempatannya. Email dibuat otomatis dari nama.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah User
                </Button>
            </div>
        </template>

        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari nama user..." class="pl-9" />
                </div>

                <Select v-model="roleFilter">
                    <SelectTrigger class="w-56">
                        <SelectValue placeholder="Semua role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua role</SelectItem>
                        <SelectItem v-for="r in ROLE" :key="r.nilai" :value="r.nilai">{{ r.label }}</SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ users.total }} user</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Penempatan</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-24 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(user, index) in users.data" :key="user.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ users.from + index }}
                                </TableCell>
                                <TableCell class="font-medium">
                                    {{ user.name }}
                                    <Badge v-if="user.id === idSaya" variant="outline" class="ml-1.5">Anda</Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{ labelRole(user.role) }}</Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ user.cabang ?? user.wilayah ?? '—' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="kelasAktif(user.is_active)">
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(user)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="user.id === idSaya"
                                            @click="konfirmasiHapus(user)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="users.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Users class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada user yang cocok.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="users" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{{ userDiedit ? 'Edit User' : 'Tambah User' }}</DialogTitle>
                    <DialogDescription>
                        {{
                            userDiedit
                                ? 'Kosongkan password bila tidak ingin mengubahnya.'
                                : 'Email akan dibuat otomatis dari nama user.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Nama</Label>
                            <Input id="name" v-model="form.name" />
                            <p v-if="form.errors.name" class="text-destructive text-sm">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">
                                Password
                                <span v-if="userDiedit" class="text-muted-foreground font-normal">(opsional)</span>
                            </Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                            <p v-if="form.errors.password" class="text-destructive text-sm">
                                {{ form.errors.password }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="r in ROLE" :key="r.nilai" :value="r.nilai">{{ r.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.role" class="text-destructive text-sm">{{ form.errors.role }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Wilayah</Label>
                            <Select v-model="form.wilayah_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Tidak ditentukan" />
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

                        <div class="space-y-2">
                            <Label>Cabang</Label>
                            <Select v-model="form.cabang_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Tidak ditentukan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in cabangTersedia" :key="c.id" :value="String(c.id)">
                                        {{ c.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.cabang_id" class="text-destructive text-sm">
                                {{ form.errors.cabang_id }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="alamat">Alamat</Label>
                        <Input id="alamat" v-model="form.alamat" />
                        <p v-if="form.errors.alamat" class="text-destructive text-sm">{{ form.errors.alamat }}</p>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label for="is_active">Status aktif</Label>
                            <p class="text-muted-foreground text-sm">User nonaktif tidak dapat masuk ke aplikasi.</p>
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

        <!-- Konfirmasi hapus -->
        <AlertDialog v-model:open="dialogHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus user ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        User <strong>{{ userDihapus?.name }}</strong> akan dihapus permanen.
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
