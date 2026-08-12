<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Grafik from '@/components/grafik/Grafik.vue';
import { opsiDasar, warnaToken } from '@/components/grafik/pakaiTemaGrafik';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Minus, TrendingDown, TrendingUp } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    cabangs: { type: Array, required: true },
    wigs: { type: Array, required: true },
    peringkat: { type: Array, required: true },
    ringkasan: { type: Object, required: true },
    detailLead: { type: Array, required: true },
    filter: { type: Object, required: true },
});

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* ---------------- Filter ---------------- */
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));
const minggu = ref(String(props.filter.minggu));
const wigId = ref(props.filter.wig_id ? String(props.filter.wig_id) : 'semua');
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : '');

const muatUlang = () =>
    router.get(
        '/dashboard-kepwil',
        {
            tahun: tahun.value,
            bulan: bulan.value,
            minggu: minggu.value,
            wig_id: wigId.value === 'semua' ? undefined : wigId.value,
            cabang_id: cabangId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );

watch([tahun, bulan, minggu, wigId, cabangId], muatUlang);

const namaCabang = computed(
    () => props.cabangs.find((c) => c.id === props.filter.cabang_id)?.nama ?? '—'
);

/* ---------------- Grafik peringkat ---------------- */
const dataPeringkat = computed(() => ({
    labels: props.peringkat.map((p) => p.nama),
    datasets: [
        {
            label: '% Capaian',
            data: props.peringkat.map((p) => p.pct),
            backgroundColor: props.peringkat.map((p) =>
                p.status === 'on'
                    ? warnaToken('success')
                    : p.status === 'waspada'
                      ? warnaToken('warning')
                      : warnaToken('destructive')
            ),
            borderRadius: 4,
        },
    ],
}));

const opsiPeringkat = computed(() => ({
    ...opsiDasar({ maxY: 120, legend: false }),
    indexAxis: 'y',
    scales: {
        x: {
            beginAtZero: true,
            suggestedMax: 120,
            ticks: { color: warnaToken('muted-foreground'), callback: (v) => `${v}%` },
            grid: { color: warnaToken('border') },
            border: { display: false },
        },
        y: {
            ticks: { color: warnaToken('muted-foreground') },
            grid: { display: false },
            border: { color: warnaToken('border') },
        },
    },
}));

const tinggiGrafik = computed(() => Math.max(props.peringkat.length * 36 + 40, 200));

/* ---------------- Gaya status ---------------- */
const KELAS_STATUS = {
    on: 'border-success/30 bg-success/10 text-success',
    waspada: 'border-warning/40 bg-warning/10 text-warning-foreground',
    awas: 'border-destructive/30 bg-destructive/10 text-destructive',
};

const LABEL_STATUS = { on: 'On Track', waspada: 'Waspada', awas: 'Awas' };

const IKON_TREN = { naik: TrendingUp, turun: TrendingDown, stabil: Minus };
const LABEL_TREN = { naik: 'Naik', turun: 'Turun', stabil: 'Stabil' };
const KELAS_TREN = {
    naik: 'text-success',
    turun: 'text-destructive',
    stabil: 'text-muted-foreground',
};

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Dashboard Wilayah" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Dashboard Wilayah</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Peringkat kantor cabang pada {{ BULAN[filter.bulan - 1] }} {{ filter.tahun }} — Minggu
                        {{ filter.minggu }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="space-y-1">
                        <Label class="text-xs">WIG</Label>
                        <Select v-model="wigId">
                            <SelectTrigger class="w-56"><SelectValue placeholder="Semua WIG" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua WIG</SelectItem>
                                <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                    {{ w.kode_wig }} — {{ w.nama_wig }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label class="text-xs">Tahun</Label>
                        <Input v-model.number="tahun" type="number" class="w-24" />
                    </div>

                    <div class="space-y-1">
                        <Label class="text-xs">Bulan</Label>
                        <Select v-model="bulan">
                            <SelectTrigger class="w-28"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(b, i) in BULAN" :key="b" :value="String(i + 1)">{{ b }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label class="text-xs">Minggu</Label>
                        <Select v-model="minggu">
                            <SelectTrigger class="w-32"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="m in 4" :key="m" :value="String(m)">Minggu {{ m }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>
        </template>

        <!-- Ringkasan -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total Cabang</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.total_cabang }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total WIG</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.total_wig }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Rata-rata Wilayah</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.rata_wilayah }}%</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Cabang On Track</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ ringkasan.cabang_on_track
                        }}<span class="text-muted-foreground text-lg">/{{ ringkasan.total_cabang }}</span>
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Grafik peringkat -->
        <Card v-if="peringkat.length" class="mb-6">
            <CardHeader>
                <CardTitle class="text-base">Peringkat Capaian Cabang</CardTitle>
                <CardDescription>
                    Rata-rata persentase Lead Measure pada periode terpilih. Hijau ≥100%, kuning ≥90%, merah di bawahnya.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Grafik tipe="bar" :data="dataPeringkat" :opsi="opsiPeringkat" :tinggi-px="tinggiGrafik" />
            </CardContent>
        </Card>

        <!-- Tabel peringkat -->
        <Card class="mb-6 overflow-hidden py-0">
            <div class="flex items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Rincian Peringkat</h2>
                <span class="text-muted-foreground ml-auto text-sm">Klik baris untuk melihat Lead Measure-nya</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-16 pl-4 text-center">#</TableHead>
                                <TableHead>Cabang</TableHead>
                                <TableHead class="w-32 text-center">Jumlah Lead</TableHead>
                                <TableHead class="w-48">Capaian</TableHead>
                                <TableHead class="w-32 text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(baris, i) in peringkat"
                                :key="baris.cabang_id"
                                :class="cn('cursor-pointer', baris.cabang_id === filter.cabang_id && 'bg-secondary')"
                                @click="cabangId = String(baris.cabang_id)"
                            >
                                <TableCell class="pl-4 text-center">
                                    <span
                                        :class="
                                            cn(
                                                'inline-flex size-6 items-center justify-center rounded-full text-xs font-semibold',
                                                i < 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'
                                            )
                                        "
                                    >
                                        {{ i + 1 }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-sm font-medium">{{ baris.nama }}</TableCell>
                                <TableCell class="text-center text-sm tabular-nums">{{ baris.jumlah_lead }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="bg-muted h-2 flex-1 overflow-hidden rounded-full">
                                            <div
                                                class="h-full rounded-full"
                                                :class="{
                                                    'bg-success': baris.status === 'on',
                                                    'bg-warning': baris.status === 'waspada',
                                                    'bg-destructive': baris.status === 'awas',
                                                }"
                                                :style="{ width: `${Math.min(baris.pct, 100)}%` }"
                                            />
                                        </div>
                                        <span class="w-16 text-right text-sm font-medium tabular-nums">
                                            {{ baris.pct }}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="KELAS_STATUS[baris.status]">
                                        {{ LABEL_STATUS[baris.status] }}
                                    </Badge>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="peringkat.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="5" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Building2 class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada kantor cabang pada wilayah ini.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Detail Lead cabang terpilih -->
        <Card class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Lead Measure — {{ namaCabang }}</h2>
                <span class="text-muted-foreground ml-auto text-sm">Diurutkan dari capaian terendah</span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="min-w-[18rem]">Lead Measure</TableHead>
                                <TableHead class="w-24">WIG</TableHead>
                                <TableHead class="w-32 text-right">Target</TableHead>
                                <TableHead class="w-32 text-right">Realisasi</TableHead>
                                <TableHead class="w-28 text-center">% Capaian</TableHead>
                                <TableHead class="w-32 text-center">Status</TableHead>
                                <TableHead class="w-28 text-center">Tren</TableHead>
                                <TableHead class="min-w-[12rem] pr-4">Keterangan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(lead, i) in detailLead" :key="lead.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ i + 1 }}</TableCell>
                                <TableCell class="text-sm">
                                    {{ lead.nama_lead }}
                                    <span class="text-muted-foreground block text-xs">{{ lead.kode_lead }}</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ lead.wig ?? '—' }}</TableCell>
                                <TableCell class="text-right text-sm tabular-nums">{{ angka(lead.target) }}</TableCell>
                                <TableCell class="text-right text-sm tabular-nums">
                                    {{ angka(lead.realisasi) }}
                                </TableCell>
                                <TableCell class="text-center text-sm font-medium tabular-nums">
                                    {{ lead.pct }}%
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="KELAS_STATUS[lead.status]">
                                        {{ LABEL_STATUS[lead.status] }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span
                                        :class="cn('inline-flex items-center gap-1 text-sm', KELAS_TREN[lead.tren])"
                                        :title="`Sebelumnya ${lead.pct_sebelumnya}%`"
                                    >
                                        <component :is="IKON_TREN[lead.tren]" class="size-4" />
                                        {{ LABEL_TREN[lead.tren] }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-muted-foreground pr-4 text-sm whitespace-pre-wrap">
                                    {{ lead.keterangan || '—' }}
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="detailLead.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="9" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Building2 class="size-8 opacity-40" />
                                        <p class="text-sm">Tidak ada Lead Measure aktif untuk cabang ini.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
