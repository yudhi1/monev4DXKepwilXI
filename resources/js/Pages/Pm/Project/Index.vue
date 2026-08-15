<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import Lencana from '@/components/pm/Lencana.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CalendarClock, Plus, Search, Users } from '@lucide/vue';

const props = defineProps({
    projects: { type: Object, required: true },
    filter: { type: Object, required: true },
    opsi: { type: Object, required: true },
    bisaBuat: { type: Boolean, default: false },
});

const HEALTH = {
    on_track: { label: 'On Track', kelas: 'text-emerald-600' },
    at_risk: { label: 'At Risk', kelas: 'text-amber-600' },
    critical: { label: 'Critical', kelas: 'text-rose-600' },
};

/* --- Filter (debounce untuk pencarian, langsung untuk dropdown) --- */
const cari = ref(props.filter.cari);
const status = ref(props.filter.status || 'semua');
const prioritas = ref(props.filter.prioritas || 'semua');

const terapkan = () =>
    router.get(
        '/pm/projects',
        {
            cari: cari.value || undefined,
            status: status.value === 'semua' ? undefined : status.value,
            prioritas: prioritas.value === 'semua' ? undefined : prioritas.value,
        },
        { preserveState: true, replace: true }
    );

let timer = null;
watch(cari, () => {
    clearTimeout(timer);
    timer = setTimeout(terapkan, 350);
});
watch([status, prioritas], terapkan);

/* --- Form project baru --- */
const dialogTerbuka = ref(false);

const form = useForm({
    kode: '',
    nama: '',
    deskripsi: '',
    status: 'perencanaan',
    prioritas: 'sedang',
    tanggal_mulai: '',
    tanggal_selesai: '',
});

const bukaTambah = () => {
    form.reset();
    form.clearErrors();
    dialogTerbuka.value = true;
};

const simpan = () =>
    form.post('/pm/projects', {
        preserveScroll: true,
        onSuccess: () => {
            dialogTerbuka.value = false;
            form.reset();
        },
    });

const tanggal = (nilai) =>
    nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Projects" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Project yang Anda ikuti beserta progres dan kesehatannya.
                    </p>
                </div>
                <Button v-if="bisaBuat" @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Project Baru
                </Button>
            </div>
        </template>

        <!-- Filter -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative w-full max-w-xs">
                <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input v-model="cari" placeholder="Cari kode atau nama project..." class="pl-9" />
            </div>

            <Select v-model="status">
                <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua status</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.statusProject" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="prioritas">
                <SelectTrigger class="w-40"><SelectValue placeholder="Prioritas" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua prioritas</SelectItem>
                    <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                        {{ meta.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <span class="text-muted-foreground ml-auto text-sm">{{ projects.total }} project</span>
        </div>

        <!-- Daftar -->
        <Card v-if="projects.data.length === 0">
            <CardContent class="text-muted-foreground p-12 text-center text-sm">
                Tidak ada project yang cocok dengan filter ini.
            </CardContent>
        </Card>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link v-for="p in projects.data" :key="p.id" :href="`/pm/projects/${p.id}`" class="block">
                <Card class="h-full transition-shadow hover:shadow-md">
                    <CardContent class="flex h-full flex-col p-4">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-muted-foreground font-mono text-xs">{{ p.kode }}</span>
                            <span :class="['text-xs font-medium whitespace-nowrap', HEALTH[p.health]?.kelas]">
                                {{ HEALTH[p.health]?.label }}
                            </span>
                        </div>

                        <p class="mt-1 font-medium">{{ p.nama }}</p>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <Lencana :nilai="p.status" :peta="opsi.statusProject" />
                            <Lencana :nilai="p.prioritas" :peta="opsi.prioritas" />
                            <Lencana v-if="p.peranSaya" :nilai="p.peranSaya" :peta="opsi.peran" />
                        </div>

                        <BilahProgress :nilai="p.progress" class="mt-4" />

                        <div class="text-muted-foreground mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                            <span>{{ p.jumlahSelesai }} / {{ p.jumlahTask }} task</span>
                            <span class="flex items-center gap-1">
                                <Users class="size-3" />
                                {{ p.jumlahAnggota }}
                            </span>
                            <span class="flex items-center gap-1">
                                <CalendarClock class="size-3" />
                                {{ tanggal(p.tanggal_selesai) }}
                            </span>
                            <span v-if="p.jumlahTerlambat > 0" class="font-medium text-rose-600">
                                {{ p.jumlahTerlambat }} overdue
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <Paginasi v-if="projects.data.length > 0" :data="projects" class="mt-4" />

        <!-- Dialog project baru -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Project Baru</DialogTitle>
                    <DialogDescription>
                        Anda otomatis menjadi Project Manager pada project ini.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label for="kode">Kode</Label>
                            <Input id="kode" v-model="form.kode" placeholder="PRJ-001" />
                            <p v-if="form.errors.kode" class="text-destructive text-sm">{{ form.errors.kode }}</p>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="nama">Nama Project</Label>
                            <Input id="nama" v-model="form.nama" />
                            <p v-if="form.errors.nama" class="text-destructive text-sm">{{ form.errors.nama }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="deskripsi">Deskripsi</Label>
                        <Textarea id="deskripsi" v-model="form.deskripsi" rows="3" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label>Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(meta, kunci) in opsi.statusProject" :key="kunci" :value="kunci">
                                        {{ meta.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Prioritas</Label>
                            <Select v-model="form.prioritas">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(meta, kunci) in opsi.prioritas" :key="kunci" :value="kunci">
                                        {{ meta.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="mulai">Tanggal Mulai</Label>
                            <Input id="mulai" v-model="form.tanggal_mulai" type="date" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="selesai">Tanggal Selesai</Label>
                            <Input id="selesai" v-model="form.tanggal_selesai" type="date" />
                            <p v-if="form.errors.tanggal_selesai" class="text-destructive text-sm">
                                {{ form.errors.tanggal_selesai }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogTerbuka = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
