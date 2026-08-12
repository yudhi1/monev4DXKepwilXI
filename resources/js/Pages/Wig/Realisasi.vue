<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ClipboardList, Save } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    wigs: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    baris: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    target: { type: [Object, null], default: null },
    ringkasan: { type: Object, required: true },
    filter: { type: Object, required: true },
    terkunciCabang: { type: Boolean, default: false },
});

/* --- Konteks --- */
const wigId = ref(props.filter.wig_id ? String(props.filter.wig_id) : '');
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : '');
const tahun = ref(props.filter.tahun);

watch([wigId, cabangId, tahun], () => {
    router.get(
        '/wig-realisasi',
        {
            wig_id: wigId.value || undefined,
            cabang_id: cabangId.value || undefined,
            tahun: tahun.value,
        },
        { preserveState: false, replace: true }
    );
});

const wigTerpilih = computed(() => props.wigs.find((w) => String(w.id) === wigId.value));

/*
 | 12 baris diisi lokal lalu dikirim sekali. Versi Livewire mengirim
 | perubahan tiap kali sel diketik.
 */
const form = useForm({
    wig_id: props.filter.wig_id,
    cabang_id: props.filter.cabang_id,
    tahun: props.filter.tahun,
    baris: props.baris.map((b) => ({ ...b })),
});

watch(
    () => props.baris,
    (baru) => {
        form.wig_id = props.filter.wig_id;
        form.cabang_id = props.filter.cabang_id;
        form.tahun = props.filter.tahun;
        form.baris = baru.map((b) => ({ ...b }));
    }
);

const simpan = () => form.post('/wig-realisasi', { preserveScroll: true });

/* Ringkasan dihitung ulang lokal supaya angkanya bergerak saat mengetik. */
const totalLokal = computed(() => form.baris.reduce((jml, b) => jml + Number(b.nilai || 0), 0));

const progresLokal = computed(() => {
    const awal = Number(props.target?.nilai_awal ?? 0);
    const target = Number(props.target?.nilai_target ?? 0);
    const rentang = target - awal;

    if (rentang > 0) {
        return Math.round((totalLokal.value / rentang) * 10000) / 100;
    }

    if (target > 0) {
        return Math.round((totalLokal.value / target) * 10000) / 100;
    }

    return 0;
});

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

const siap = computed(() => !! props.filter.wig_id && !! props.filter.cabang_id);
</script>

<template>
    <Head title="Input Realisasi WIG Bulanan" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Input Realisasi WIG Bulanan</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Isi capaian tiap bulan untuk satu WIG di satu kantor cabang.
                    </p>
                </div>
                <Button :disabled="!siap || form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Realisasi' }}
                </Button>
            </div>
        </template>

        <!-- Konteks -->
        <Card class="mb-4">
            <CardContent class="flex flex-wrap items-end gap-4">
                <div class="min-w-72 flex-1 space-y-2">
                    <Label>WIG</Label>
                    <Select v-model="wigId">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih WIG" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                [{{ w.tahun }}] {{ w.kode_wig }} — {{ w.nama_wig }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="min-w-56 flex-1 space-y-2">
                    <Label>Cabang</Label>
                    <Select v-model="cabangId" :disabled="terkunciCabang">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih cabang" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">{{ c.nama }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-28 space-y-2">
                    <Label for="tahun">Tahun</Label>
                    <Input id="tahun" v-model.number="tahun" type="number" />
                </div>
            </CardContent>
        </Card>

        <div v-if="wigTerpilih && siap" class="bg-accent/40 mb-4 rounded-lg border p-3 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="secondary" class="font-mono">{{ wigTerpilih.kode_wig }}</Badge>
                <Badge v-if="wigTerpilih.bidang" variant="outline" :class="kelasBidang(wigTerpilih.bidang)">{{ wigTerpilih.bidang }}</Badge>
            </div>
            <p class="text-muted-foreground mt-1.5">{{ wigTerpilih.nama_wig }}</p>
        </div>

        <!-- Ringkasan target -->
        <div v-if="siap" class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Nilai Awal</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ target ? angka(target.nilai_awal) : '—' }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Nilai Target</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ target ? angka(target.nilai_target) : '—' }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total Realisasi {{ filter.tahun }}</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ angka(totalLokal) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Progres</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ progresLokal }}%</p>
                    <div class="bg-muted mt-2 h-1.5 overflow-hidden rounded-full">
                        <div
                            class="bg-success h-full rounded-full"
                            :style="{ width: `${Math.min(progresLokal, 100)}%` }"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <p v-if="siap && !target" class="border-warning/40 bg-warning/10 mb-4 rounded-lg border p-3 text-sm">
            Kombinasi WIG dan cabang ini belum punya target. Realisasi tetap bisa disimpan, tapi progresnya
            belum dapat dihitung — tetapkan dulu di menu Input Target WIG.
        </p>

        <!-- Tabel 12 bulan -->
        <Card v-if="siap" class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Realisasi Bulanan {{ filter.tahun }}</h2>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-40">Bulan</TableHead>
                                <TableHead class="w-52 text-right">Nilai Realisasi</TableHead>
                                <TableHead class="pr-4">Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(b, i) in form.baris" :key="b.bulan">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ i + 1 }}</TableCell>
                                <TableCell class="text-sm font-medium">{{ namaBulan[b.bulan - 1] }}</TableCell>
                                <TableCell>
                                    <Input v-model.number="b.nilai" type="number" step="any" class="text-right" />
                                </TableCell>
                                <TableCell class="pr-4">
                                    <Input v-model="b.catatan" placeholder="Opsional" />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <div class="flex items-center justify-between border-t px-4 py-3">
                <p class="text-muted-foreground text-sm">
                    Total: <span class="text-foreground font-medium tabular-nums">{{ angka(totalLokal) }}</span>
                </p>
                <Button :disabled="form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Realisasi' }}
                </Button>
            </div>
        </Card>

        <Card v-else>
            <CardContent class="py-16">
                <div class="text-muted-foreground flex flex-col items-center gap-2">
                    <ClipboardList class="size-8 opacity-40" />
                    <p class="text-sm">Pilih WIG dan cabang terlebih dahulu.</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
