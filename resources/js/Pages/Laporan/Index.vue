<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Paginasi from '@/components/Paginasi.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FileDown, FileSearch, FileText, RotateCcw } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    realisasis: { type: Object, required: true },
    wigs: { type: Array, required: true },
    lags: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    filter: { type: Object, required: true },
    jumlahMinggu: { type: Number, default: 5 },
});

const SEMUA = 'semua';

const nilaiAwal = (kunci) => (props.filter[kunci] ? String(props.filter[kunci]) : SEMUA);

const tahun = ref(props.filter.tahun);
const wigId = ref(nilaiAwal('wig_id'));
const lagId = ref(nilaiAwal('lag_id'));
const cabangId = ref(nilaiAwal('cabang_id'));
const bulan = ref(nilaiAwal('bulan'));
const minggu = ref(nilaiAwal('minggu'));

const kueri = computed(() => ({
    tahun: tahun.value,
    wig_id: wigId.value === SEMUA ? undefined : wigId.value,
    lag_id: lagId.value === SEMUA ? undefined : lagId.value,
    cabang_id: cabangId.value === SEMUA ? undefined : cabangId.value,
    bulan: bulan.value === SEMUA ? undefined : bulan.value,
    minggu: minggu.value === SEMUA ? undefined : minggu.value,
}));

watch([tahun, wigId, lagId, cabangId, bulan, minggu], () => {
    router.get('/laporan', kueri.value, { preserveState: true, preserveScroll: true, replace: true });
});

/* Mengganti WIG membuat pilihan Lag sebelumnya tidak relevan lagi. */
watch(wigId, () => (lagId.value = SEMUA));

const aturUlang = () => {
    tahun.value = new Date().getFullYear();
    wigId.value = SEMUA;
    lagId.value = SEMUA;
    cabangId.value = SEMUA;
    bulan.value = SEMUA;
    minggu.value = SEMUA;
};

const adaFilter = computed(() =>
    [wigId, lagId, cabangId, bulan, minggu].some((f) => f.value !== SEMUA)
);

const tautanEkspor = (jenis) => {
    const params = new URLSearchParams(
        Object.entries(kueri.value).filter(([, v]) => v !== undefined)
    );

    return `/laporan/${jenis}?${params}`;
};

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

const warnaPersen = (nilai) =>
    nilai >= 100 ? 'text-success' : nilai >= 70 ? 'text-warning-foreground' : 'text-destructive';
</script>

<template>
    <Head title="Laporan" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Laporan Realisasi</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Realisasi mingguan Lead Measure. Ekspor mengikuti filter yang sedang aktif.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <a :href="tautanEkspor('excel')">
                            <FileDown class="mr-1.5 size-4" />
                            Excel
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <a :href="tautanEkspor('pdf')">
                            <FileText class="mr-1.5 size-4" />
                            PDF
                        </a>
                    </Button>
                </div>
            </div>
        </template>

        <!-- Filter -->
        <Card class="mb-4">
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="tahun">Tahun</Label>
                        <Input id="tahun" v-model.number="tahun" type="number" />
                    </div>

                    <div class="space-y-2">
                        <Label>WIG</Label>
                        <Select v-model="wigId">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Semua WIG" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua WIG</SelectItem>
                                <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                    {{ w.kode_wig }} — {{ w.nama_wig }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Lag Measure</Label>
                        <Select v-model="lagId">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Semua Lag" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua Lag</SelectItem>
                                <SelectItem v-for="l in lags" :key="l.id" :value="String(l.id)">
                                    {{ l.kode_lag }} — {{ l.nama_lag }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Cabang</Label>
                        <Select v-model="cabangId">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Semua cabang" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua cabang</SelectItem>
                                <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                    {{ c.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Bulan</Label>
                        <Select v-model="bulan">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Semua bulan" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua bulan</SelectItem>
                                <SelectItem v-for="(b, i) in namaBulan" :key="b" :value="String(i + 1)">
                                    {{ b }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Minggu</Label>
                        <Select v-model="minggu">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Semua minggu" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SEMUA">Semua minggu</SelectItem>
                                <SelectItem v-for="m in jumlahMinggu" :key="m" :value="String(m)">Minggu {{ m }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div v-if="adaFilter" class="flex justify-end">
                    <Button variant="ghost" size="sm" @click="aturUlang">
                        <RotateCcw class="mr-1.5 size-4" />
                        Atur ulang filter
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Tabel -->
        <Card class="overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Hasil</h2>
                <span class="text-muted-foreground ml-auto text-sm">{{ realisasis.total }} baris</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="w-36">Cabang</TableHead>
                                <TableHead class="w-24">WIG</TableHead>
                                <TableHead class="min-w-[18rem]">Lead Measure</TableHead>
                                <TableHead class="w-28">Periode</TableHead>
                                <TableHead class="w-32 text-right">Target</TableHead>
                                <TableHead class="w-32 text-right">Realisasi</TableHead>
                                <TableHead class="w-28 text-center">% Capaian</TableHead>
                                <TableHead class="min-w-[12rem] pr-4">Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(baris, i) in realisasis.data" :key="baris.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ realisasis.from + i }}
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ baris.cabang ?? '—' }}</TableCell>
                                <TableCell>
                                    <Badge v-if="baris.wig" variant="secondary" class="font-mono text-xs">
                                        {{ baris.wig }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="align-top text-sm whitespace-normal">
                                    {{ baris.nama_lead ?? '—' }}
                                    <span class="text-muted-foreground block text-xs">{{ baris.kode_lead }}</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">
                                    {{ namaBulan[baris.bulan - 1] }}
                                    <span class="block text-xs">Minggu {{ baris.minggu_ke }}</span>
                                </TableCell>
                                <TableCell class="text-right text-sm tabular-nums">{{ angka(baris.target) }}</TableCell>
                                <TableCell class="text-right text-sm tabular-nums">
                                    {{ angka(baris.realisasi) }}
                                </TableCell>
                                <TableCell
                                    :class="cn('text-center text-sm font-medium tabular-nums', warnaPersen(baris.persentase))"
                                >
                                    {{ baris.persentase }}%
                                </TableCell>
                                <TableCell class="text-muted-foreground pr-4 text-sm whitespace-pre-wrap">
                                    {{ baris.catatan || '—' }}
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="realisasis.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="9" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <FileSearch class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada data realisasi untuk filter ini.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <Paginasi :data="realisasis" />
        </Card>
    </AppLayout>
</template>
