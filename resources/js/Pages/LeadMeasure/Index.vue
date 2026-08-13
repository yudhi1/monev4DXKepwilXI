<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
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
import { Pencil, Plus, Power, TrendingUp, Trash2 } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';
import { kelasAktif } from '@/lib/status';

const props = defineProps({
    leads: { type: Array, required: true },
    lags: { type: Array, required: true },
    wigs: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    filter: { type: Object, required: true },
    terkunciCabang: { type: Boolean, default: false },
});

/* --- Penyaringan; seluruh Lead tampil sejak halaman dibuka --- */
const SEMUA = 'semua';

const wigId = ref(props.filter.wig_id ? String(props.filter.wig_id) : SEMUA);
const lagId = ref(props.filter.lag_id ? String(props.filter.lag_id) : SEMUA);
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : SEMUA);
const tahun = ref(props.filter.tahun);

const muatUlang = () =>
    router.get(
        '/lead-measures',
        {
            wig_id: wigId.value === SEMUA ? undefined : wigId.value,
            lag_id: lagId.value === SEMUA ? undefined : lagId.value,
            cabang_id: cabangId.value === SEMUA ? undefined : cabangId.value,
            tahun: tahun.value,
        },
        { preserveState: true, replace: true }
    );

watch([wigId, lagId, cabangId, tahun], muatUlang);

/*
 | Lag melekat pada kombinasi WIG + cabang, jadi pilihannya baru terbuka
 | setelah keduanya ditentukan, dan direset bila salah satunya berubah.
 */
const lagSiap = computed(() => wigId.value !== SEMUA && cabangId.value !== SEMUA);

watch([wigId, cabangId], () => (lagId.value = SEMUA));

const wigTerpilih = computed(() => props.wigs.find((w) => String(w.id) === wigId.value));
const lagTerpilih = computed(() => props.lags.find((l) => String(l.id) === lagId.value));

/* --- Form --- */
const dialogTerbuka = ref(false);
const leadDiedit = ref(null);

const form = useForm({
    wig_id: '',
    cabang_id: '',
    lag_measure_id: '',
    kode_lead: '',
    nama_lead: '',
    satuan: '',
    tahun: props.filter.tahun,
});

/*
 | Daftar Lag di dalam dialog berdiri sendiri dari daftar halaman, supaya
 | WIG dan cabang bisa dipilih langsung di form tanpa harus memuat ulang
 | daftar di belakangnya.
 */
const lagPilihan = ref([]);
const memuatKonteks = ref(false);

const ambilKonteks = async () => {
    if (! form.wig_id || ! form.cabang_id) {
        lagPilihan.value = [];

        return;
    }

    memuatKonteks.value = true;

    try {
        const params = new URLSearchParams({
            wig_id: form.wig_id,
            cabang_id: form.cabang_id,
            tahun: form.tahun,
        });

        const respons = await fetch(`/lead-measures/konteks?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (! respons.ok) {
            return;
        }

        const data = await respons.json();
        lagPilihan.value = data.lags;

        // Kode hanya diusulkan saat membuat baru; kode yang sudah dipakai
        // tidak boleh berubah sendiri ketika diedit.
        if (! leadDiedit.value) {
            form.kode_lead = data.kode;
        }

        // Lag yang tak lagi tersedia pada kombinasi baru dilepas.
        if (form.lag_measure_id && ! data.lags.some((l) => String(l.id) === String(form.lag_measure_id))) {
            form.lag_measure_id = '';
        }
    } finally {
        memuatKonteks.value = false;
    }
};

watch([() => form.wig_id, () => form.cabang_id, () => form.tahun], ambilKonteks);

const bukaTambah = async () => {
    leadDiedit.value = null;
    form.reset();
    form.clearErrors();
    lagPilihan.value = [];

    // Mengikuti pilihan di halaman bila ada, tapi tidak mewajibkannya.
    form.wig_id = wigId.value === SEMUA ? '' : wigId.value;
    form.cabang_id = props.terkunciCabang && props.filter.cabang_id
        ? String(props.filter.cabang_id)
        : (cabangId.value === SEMUA ? '' : cabangId.value);
    form.tahun = tahun.value;

    dialogTerbuka.value = true;
    await ambilKonteks();
};

const bukaEdit = (lead) => {
    leadDiedit.value = lead;
    form.clearErrors();
    form.wig_id = String(lead.wig_id);
    form.cabang_id = String(lead.cabang_id);
    form.lag_measure_id = String(lead.lag_measure_id);
    form.kode_lead = lead.kode_lead;
    form.nama_lead = lead.nama_lead;
    form.satuan = lead.satuan ?? '';
    form.tahun = lead.tahun;
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

    if (leadDiedit.value) {
        form.put(`/lead-measures/${leadDiedit.value.id}`, opsi);
    } else {
        form.post('/lead-measures', opsi);
    }
};

const toggleAktif = (lead) => router.patch(`/lead-measures/${lead.id}/toggle`, {}, { preserveScroll: true });

/* --- Hapus --- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari data sasarannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau sasarannya ikut dikosongkan saat menutup,
 | fungsi hapus() menerima null dan permintaan tak pernah terkirim.
 */
const leadDihapus = ref(null);
const dialogHapusTerbuka = ref(false);

const konfirmasiHapus = (sasaran) => {
    leadDihapus.value = sasaran;
    dialogHapusTerbuka.value = true;
};

const hapus = () => {
    const sasaran = leadDihapus.value;

    if (! sasaran) {
        return;
    }

    router.delete(`/lead-measures/${sasaran.id}`, {
        preserveScroll: true,
        onFinish: () => (leadDihapus.value = null),
    });
};
</script>

<template>
    <Head title="Lead Measure" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Lead Measure</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Aktivitas pendorong per WIG dan cabang. Kode dibuat otomatis dari bidang WIG dan kode cabang.
                    </p>
                </div>
                <Button @click="bukaTambah">
                    <Plus class="mr-1.5 size-4" />
                    Tambah Lead
                </Button>
            </div>
        </template>

        <!-- Penyaringan -->
        <Card class="mb-4">
            <CardContent class="space-y-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="min-w-72 flex-1 space-y-2">
                        <Label>WIG</Label>
                        <Select v-model="wigId">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Semua WIG" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua WIG</SelectItem>
                                <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                    {{ w.kode_wig }} — {{ w.nama_wig }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="min-w-56 flex-1 space-y-2">
                        <Label>Cabang</Label>
                        <Select v-model="cabangId" :disabled="terkunciCabang">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Semua cabang" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua cabang</SelectItem>
                                <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                    {{ c.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="min-w-72 flex-1 space-y-2">
                        <Label>Lag Measure</Label>
                        <Select v-model="lagId" :disabled="!lagSiap">
                            <SelectTrigger class="w-full">
                                <SelectValue :placeholder="lagSiap ? 'Semua Lag' : 'Pilih WIG dan cabang dulu'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua Lag</SelectItem>
                                <SelectItem v-for="l in lags" :key="l.id" :value="String(l.id)">
                                    {{ l.kode_lag }} — {{ l.nama_lag }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="lagSiap && lags.length === 0" class="text-muted-foreground text-sm">
                            Kombinasi ini belum punya Lag Measure.
                        </p>
                    </div>

                    <div class="w-28 space-y-2">
                        <Label for="tahun">Tahun</Label>
                        <Input id="tahun" v-model.number="tahun" type="number" />
                    </div>
                </div>

                <!-- Keterangan pilihan, menggantikan panel terpisah di tengah halaman -->
                <div class="bg-muted/40 space-y-1.5 rounded-lg border p-3 text-sm">
                    <p class="font-medium">Pilihan Anda</p>

                    <div class="flex flex-wrap items-start gap-2">
                        <span class="text-muted-foreground w-12 shrink-0">Wig :</span>
                        <template v-if="wigTerpilih">
                            <Badge variant="secondary" class="font-mono text-xs">{{ wigTerpilih.kode_wig }}</Badge>
                            <Badge
                                v-if="wigTerpilih.bidang"
                                variant="outline"
                                :class="kelasBidang(wigTerpilih.bidang)"
                            >
                                {{ wigTerpilih.bidang }}
                            </Badge>
                            <span class="text-muted-foreground min-w-0 flex-1 whitespace-pre-wrap">
                                {{ wigTerpilih.nama_wig }}
                            </span>
                        </template>
                        <span v-else class="text-muted-foreground">Semua WIG</span>
                    </div>

                    <div class="flex flex-wrap items-start gap-2">
                        <span class="text-muted-foreground w-12 shrink-0">Lag :</span>
                        <template v-if="lagTerpilih">
                            <Badge variant="outline" class="font-mono text-xs">{{ lagTerpilih.kode_lag }}</Badge>
                            <span class="text-muted-foreground min-w-0 flex-1 whitespace-pre-wrap">
                                {{ lagTerpilih.nama_lag }}
                            </span>
                        </template>
                        <span v-else class="text-muted-foreground">Semua Lag</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Daftar -->
        <Card class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Daftar Lead Measure</h2>
                <span class="text-muted-foreground ml-auto text-sm">{{ leads.total }} lead</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-64">Kode</TableHead>
                                <TableHead class="w-[34rem] min-w-[20rem]">Nama Lead</TableHead>
                                <TableHead class="w-28">Bidang</TableHead>
                                <TableHead class="w-24 text-center">Status</TableHead>
                                <TableHead class="w-32 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(lead, index) in leads.data" :key="lead.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ leads.from + index }}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono text-xs">{{ lead.kode_lead }}</Badge>
                                </TableCell>
                                <TableCell class="align-top text-sm">
                                    <div class="max-w-[34rem] whitespace-pre-wrap">{{ lead.nama_lead }}</div>
                                </TableCell>
                                <TableCell class="align-top">
                                    <Badge
                                        v-if="lead.wig?.bidang"
                                        variant="outline"
                                        :class="kelasBidang(lead.wig.bidang)"
                                    >
                                        {{ lead.wig.bidang }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="kelasAktif(lead.is_active)">
                                        {{ lead.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            :title="lead.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            @click="toggleAktif(lead)"
                                        >
                                            <Power class="size-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" title="Edit" @click="bukaEdit(lead)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Hapus"
                                            class="text-destructive hover:text-destructive"
                                            @click="konfirmasiHapus(lead)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="leads.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="6" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <TrendingUp class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada Lead Measure yang cocok.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="leads" />
        </Card>

        <!-- Dialog tambah / edit -->
        <Dialog v-model:open="dialogTerbuka">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ leadDiedit ? 'Edit Lead Measure' : 'Tambah Lead Measure' }}</DialogTitle>
                    <DialogDescription>
                        Pilih WIG dan cabang di sini — kode Lead dan daftar Lag Measure akan menyesuaikan.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="simpan">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2 sm:col-span-2">
                            <Label>WIG</Label>
                            <Select v-model="form.wig_id">
                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih WIG" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                        {{ w.kode_wig }} — {{ w.nama_wig }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.wig_id" class="text-destructive text-sm">{{ form.errors.wig_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="tahun_form">Tahun</Label>
                            <Input id="tahun_form" v-model.number="form.tahun" type="number" />
                            <p v-if="form.errors.tahun" class="text-destructive text-sm">{{ form.errors.tahun }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
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
                        <Label>Lag Measure</Label>
                        <Select v-model="form.lag_measure_id" :disabled="lagPilihan.length === 0">
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="
                                        memuatKonteks
                                            ? 'Memuat...'
                                            : lagPilihan.length
                                              ? 'Pilih Lag Measure'
                                              : 'Pilih WIG dan cabang dulu'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="lag in lagPilihan" :key="lag.id" :value="String(lag.id)">
                                    {{ lag.kode_lag }} — {{ lag.nama_lag }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.wig_id && form.cabang_id && !memuatKonteks && lagPilihan.length === 0"
                            class="text-muted-foreground text-sm"
                        >
                            Kombinasi ini belum punya Lag Measure. Buat dulu di menu Lag Measure.
                        </p>
                        <p v-if="form.errors.lag_measure_id" class="text-destructive text-sm">
                            {{ form.errors.lag_measure_id }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="kode_lead">Kode Lead</Label>
                            <Input id="kode_lead" v-model="form.kode_lead" class="font-mono" />
                            <p v-if="form.errors.kode_lead" class="text-destructive text-sm">
                                {{ form.errors.kode_lead }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="satuan">Satuan</Label>
                            <Input id="satuan" v-model="form.satuan" placeholder="mis. orang" />
                            <p v-if="form.errors.satuan" class="text-destructive text-sm">{{ form.errors.satuan }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_lead">Nama Lead</Label>
                        <Textarea id="nama_lead" v-model="form.nama_lead" rows="3" />
                        <p v-if="form.errors.nama_lead" class="text-destructive text-sm">{{ form.errors.nama_lead }}</p>
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
                    <AlertDialogTitle>Hapus Lead Measure ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong>{{ leadDihapus?.kode_lead }}</strong> akan dihapus permanen.
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
