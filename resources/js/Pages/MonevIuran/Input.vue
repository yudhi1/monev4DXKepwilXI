<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
import { Coins, Lock, LockOpen, Pencil, Save, X } from '@lucide/vue';

const props = defineProps({
    cabangs: { type: Array, required: true },
    baris: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    bulanLalu: { type: String, required: true },
    filter: { type: Object, required: true },
    konsolidasi: { type: Boolean, default: false },
    periodeTerkunci: { type: Boolean, default: false },
    terkunciCabang: { type: Boolean, default: false },
    bisaBukaKunci: { type: Boolean, default: false },
});

/* ---------------- Konteks ---------------- */
const pilihan = ref(props.filter.pilihan || 'kosong');
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));

watch([pilihan, tahun, bulan], () => {
    router.get(
        '/monev-iuran/input',
        {
            pilihan: pilihan.value === 'kosong' ? undefined : pilihan.value,
            tahun: tahun.value,
            bulan: bulan.value,
        },
        { preserveState: false, replace: true }
    );
});

const adaKonteks = computed(() => props.konsolidasi || !! props.filter.cabang_id);

const namaCabang = computed(
    () => props.cabangs.find((c) => c.id === props.filter.cabang_id)?.nama ?? '—'
);

/* ---------------- Edit per baris ---------------- */
const draf = reactive({});
const sedangDiedit = ref(null);

const form = useForm({
    cabang_id: null,
    segmen_id: null,
    tahun: props.filter.tahun,
    bulan: props.filter.bulan,
    realisasi_sd_bulan_lalu: 0,
    mg1: 0,
    mg2: 0,
    mg3: 0,
    mg4: 0,
    keterangan: '',
});

const mulaiEdit = (b) => {
    sedangDiedit.value = b.segmen_id;
    draf[b.segmen_id] = { ...b };
};

const batalEdit = () => {
    delete draf[sedangDiedit.value];
    sedangDiedit.value = null;
    form.clearErrors();
};

const simpan = (b) => {
    const d = draf[b.segmen_id];

    form.cabang_id = props.filter.cabang_id;
    form.segmen_id = b.segmen_id;
    form.tahun = props.filter.tahun;
    form.bulan = props.filter.bulan;
    form.realisasi_sd_bulan_lalu = d.realisasi_sd_bulan_lalu;
    form.mg1 = d.mg1;
    form.mg2 = d.mg2;
    form.mg3 = d.mg3;
    form.mg4 = d.mg4;
    form.keterangan = d.keterangan;

    form.post('/monev-iuran/input', {
        preserveScroll: true,
        onSuccess: () => {
            delete draf[b.segmen_id];
            sedangDiedit.value = null;
        },
    });
};

const nilaiBaris = (b) => draf[b.segmen_id] ?? b;

const totalBulan = (b) => {
    const d = nilaiBaris(b);

    return Number(d.mg1 || 0) + Number(d.mg2 || 0) + Number(d.mg3 || 0) + Number(d.mg4 || 0);
};

const totalKumulatif = (b) => Number(nilaiBaris(b).realisasi_sd_bulan_lalu || 0) + totalBulan(b);

const totalSemua = computed(() => ({
    bulan: props.baris.reduce((jml, b) => jml + totalBulan(b), 0),
    kumulatif: props.baris.reduce((jml, b) => jml + totalKumulatif(b), 0),
}));

const rupiah = (n) => 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

/* ---------------- Kunci periode ---------------- */
/*
 | Keadaan buka/tutup dialog dipisahkan dari jenis tindakannya. AlertDialogAction
 | punya penangan klik bawaan yang menutup dialog, dan Vue menjalankannya lebih
 | dulu daripada @click kita — kalau `konfirmasi` ikut dikosongkan saat menutup,
 | tombol "Ya, Kunci" justru mengirim permintaan buka kunci.
 */
const konfirmasi = ref(null);
const dialogKunciTerbuka = ref(false);

const mintaKonfirmasi = (jenis) => {
    konfirmasi.value = jenis;
    dialogKunciTerbuka.value = true;
};

const kirimKunci = () => {
    const jenis = konfirmasi.value;

    if (! jenis) {
        return;
    }

    router.post(
        jenis === 'kunci' ? '/monev-iuran/kunci' : '/monev-iuran/buka-kunci',
        { cabang_id: props.filter.cabang_id, tahun: props.filter.tahun, bulan: props.filter.bulan },
        { preserveScroll: true, onFinish: () => (konfirmasi.value = null) }
    );
};
</script>

<template>
    <Head title="Input Realisasi Iuran" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Input Realisasi Iuran</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Realisasi per segmen kepesertaan, dirinci per minggu dalam satu bulan.
                    </p>
                </div>

                <div v-if="filter.cabang_id && !konsolidasi" class="flex gap-2">
                    <Button
                        v-if="!periodeTerkunci"
                        variant="outline"
                        @click="mintaKonfirmasi('kunci')"
                    >
                        <Lock class="mr-1.5 size-4" />
                        Kunci Periode
                    </Button>
                    <Button
                        v-else-if="bisaBukaKunci"
                        variant="outline"
                        @click="mintaKonfirmasi('buka')"
                    >
                        <LockOpen class="mr-1.5 size-4" />
                        Buka Kunci
                    </Button>
                </div>
            </div>
        </template>

        <!-- Konteks -->
        <Card class="mb-4">
            <CardContent class="flex flex-wrap items-end gap-4">
                <div class="min-w-64 flex-1 space-y-2">
                    <Label>Kantor Cabang</Label>
                    <Select v-model="pilihan" :disabled="terkunciCabang">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih cabang" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="kosong">— Pilih cabang —</SelectItem>
                            <SelectItem value="konsolidasi">Konsolidasi (semua cabang)</SelectItem>
                            <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                {{ c.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-28 space-y-2">
                    <Label for="tahun">Tahun</Label>
                    <Input id="tahun" v-model.number="tahun" type="number" />
                </div>

                <div class="w-36 space-y-2">
                    <Label>Bulan</Label>
                    <Select v-model="bulan">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="(b, i) in namaBulan" :key="b" :value="String(i + 1)">{{ b }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Status periode -->
        <div v-if="konsolidasi" class="bg-accent/40 mb-4 rounded-lg border p-3 text-sm">
            <strong>Mode Konsolidasi.</strong>
            Menampilkan total seluruh cabang yang dapat kamu akses. Data hanya bisa dilihat, tidak bisa diedit.
        </div>

        <div
            v-else-if="periodeTerkunci"
            class="border-warning/40 bg-warning/10 mb-4 flex items-center gap-2 rounded-lg border p-3 text-sm"
        >
            <Lock class="size-4 shrink-0" />
            <span>
                Periode <strong>{{ namaBulan[filter.bulan - 1] }} {{ filter.tahun }}</strong> untuk
                <strong>{{ namaCabang }}</strong> sudah <strong>Final</strong> dan tidak dapat diubah.
            </span>
        </div>

        <!-- Tabel -->
        <Card v-if="adaKonteks" class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">
                    {{ konsolidasi ? 'Konsolidasi Semua Cabang' : namaCabang }}
                </h2>
                <Badge variant="outline">{{ namaBulan[filter.bulan - 1] }} {{ filter.tahun }}</Badge>
                <span class="text-muted-foreground ml-auto text-sm">
                    Total bulan ini: <span class="text-foreground font-medium">{{ rupiah(totalSemua.bulan) }}</span>
                </span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="min-w-[12rem] pl-4">Segmen</TableHead>
                                <TableHead class="w-44 text-right">s.d. {{ bulanLalu }}</TableHead>
                                <TableHead class="w-36 text-right">Minggu 1</TableHead>
                                <TableHead class="w-36 text-right">Minggu 2</TableHead>
                                <TableHead class="w-36 text-right">Minggu 3</TableHead>
                                <TableHead class="w-36 text-right">Minggu 4</TableHead>
                                <TableHead class="w-40 text-right">Total Bulan Ini</TableHead>
                                <TableHead class="w-44 text-right">Kumulatif</TableHead>
                                <TableHead class="min-w-[12rem]">Keterangan</TableHead>
                                <TableHead v-if="!konsolidasi" class="w-32 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="b in baris" :key="b.segmen_id">
                                <TableCell class="pl-4 text-sm font-medium">{{ b.nama }}</TableCell>

                                <TableCell>
                                    <Input
                                        v-if="sedangDiedit === b.segmen_id"
                                        v-model.number="draf[b.segmen_id].realisasi_sd_bulan_lalu"
                                        type="number"
                                        step="any"
                                        min="0"
                                        class="text-right"
                                    />
                                    <p v-else class="text-right text-sm tabular-nums">
                                        {{ rupiah(b.realisasi_sd_bulan_lalu) }}
                                    </p>
                                </TableCell>

                                <TableCell v-for="m in ['mg1', 'mg2', 'mg3', 'mg4']" :key="m">
                                    <Input
                                        v-if="sedangDiedit === b.segmen_id"
                                        v-model.number="draf[b.segmen_id][m]"
                                        type="number"
                                        step="any"
                                        min="0"
                                        class="text-right"
                                    />
                                    <p v-else class="text-right text-sm tabular-nums">{{ rupiah(b[m]) }}</p>
                                </TableCell>

                                <TableCell class="text-right text-sm font-medium tabular-nums">
                                    {{ rupiah(totalBulan(b)) }}
                                </TableCell>

                                <TableCell class="text-right text-sm font-medium tabular-nums">
                                    {{ rupiah(totalKumulatif(b)) }}
                                </TableCell>

                                <TableCell>
                                    <Input
                                        v-if="sedangDiedit === b.segmen_id"
                                        v-model="draf[b.segmen_id].keterangan"
                                        placeholder="Opsional"
                                    />
                                    <p v-else class="text-muted-foreground text-sm whitespace-pre-wrap">
                                        {{ b.keterangan || '—' }}
                                    </p>
                                </TableCell>

                                <TableCell v-if="!konsolidasi" class="pr-4">
                                    <div class="flex justify-end gap-1">
                                        <template v-if="sedangDiedit === b.segmen_id">
                                            <Button variant="ghost" size="icon" title="Batal" @click="batalEdit">
                                                <X class="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Simpan"
                                                :disabled="form.processing"
                                                @click="simpan(b)"
                                            >
                                                <Save class="size-4" />
                                            </Button>
                                        </template>
                                        <Button
                                            v-else
                                            variant="ghost"
                                            size="icon"
                                            title="Edit"
                                            :disabled="b.terkunci || sedangDiedit !== null"
                                            @click="mulaiEdit(b)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="baris.length" class="bg-muted/50 hover:bg-muted/50 font-medium">
                                <TableCell class="pl-4 text-sm">Total</TableCell>
                                <TableCell colspan="5" />
                                <TableCell class="text-right text-sm tabular-nums">
                                    {{ rupiah(totalSemua.bulan) }}
                                </TableCell>
                                <TableCell class="text-right text-sm tabular-nums">
                                    {{ rupiah(totalSemua.kumulatif) }}
                                </TableCell>
                                <TableCell />
                                <TableCell v-if="!konsolidasi" />
                            </TableRow>

                            <TableRow v-if="baris.length === 0" class="hover:bg-transparent">
                                <TableCell :colspan="konsolidasi ? 9 : 10" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Coins class="size-8 opacity-40" />
                                        <p class="text-sm">
                                            Belum ada segmen aktif. Tambahkan lewat menu Master Segmen.
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <div
                v-if="Object.keys(form.errors).length"
                class="border-destructive/30 bg-destructive/10 text-destructive border-t px-4 py-3 text-sm"
            >
                <p v-for="(pesan, kunci) in form.errors" :key="kunci">{{ pesan }}</p>
            </div>
        </Card>

        <Card v-else>
            <CardContent class="py-16">
                <div class="text-muted-foreground flex flex-col items-center gap-2">
                    <Coins class="size-8 opacity-40" />
                    <p class="text-sm">Pilih Kantor Cabang atau mode Konsolidasi terlebih dahulu.</p>
                </div>
            </CardContent>
        </Card>

        <!-- Konfirmasi kunci / buka kunci -->
        <AlertDialog v-model:open="dialogKunciTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ konfirmasi === 'kunci' ? 'Kunci periode ini?' : 'Buka kunci periode ini?' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="konfirmasi === 'kunci'">
                            Seluruh data <strong>{{ namaBulan[filter.bulan - 1] }} {{ filter.tahun }}</strong> untuk
                            <strong>{{ namaCabang }}</strong> akan ditandai Final dan tidak bisa diubah lagi.
                            Hanya admin yang dapat membukanya kembali.
                        </template>
                        <template v-else>
                            Periode akan kembali berstatus Draft dan datanya bisa diubah lagi.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="kirimKunci">
                        {{ konfirmasi === 'kunci' ? 'Ya, Kunci' : 'Ya, Buka' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
