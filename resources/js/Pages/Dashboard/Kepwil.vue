<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Grafik from '@/components/grafik/Grafik.vue';
import TabelDetailLead from '@/components/TabelDetailLead.vue';
import { opsiDasar, warnaToken } from '@/components/grafik/pakaiTemaGrafik';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2 } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    cabangs: { type: Array, required: true },
    wigs: { type: Array, required: true },
    lags: { type: Array, required: true },
    peringkat: { type: Array, required: true },
    ringkasan: { type: Object, required: true },
    detailLead: { type: Array, required: true },
    detailLeadBanding: { type: [Array, null], default: null },
    sasaran: { type: [Object, null], default: null },
    filter: { type: Object, required: true },
    jumlahMinggu: { type: Number, default: 5 },
});

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const BULAN_PANJANG = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const SEMUA = 'semua';
const TANPA = 'tanpa';

/* ---------------- Filter ---------------- */
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));
const minggu = ref(String(props.filter.minggu));
const mingguBanding = ref(props.filter.minggu_banding ? String(props.filter.minggu_banding) : TANPA);
const wigId = ref(props.filter.wig_id ? String(props.filter.wig_id) : SEMUA);
const lagId = ref(props.filter.lag_id ? String(props.filter.lag_id) : SEMUA);
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : '');

const muatUlang = () =>
    router.get(
        '/dashboard-kepwil',
        {
            tahun: tahun.value,
            bulan: bulan.value,
            minggu: minggu.value,
            minggu_banding: mingguBanding.value === TANPA ? undefined : mingguBanding.value,
            wig_id: wigId.value === SEMUA ? undefined : wigId.value,
            lag_id: lagId.value === SEMUA ? undefined : lagId.value,
            cabang_id: cabangId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );

watch([tahun, bulan, minggu, mingguBanding, wigId, lagId, cabangId], muatUlang);

/* Mengganti WIG membuat pilihan LAG sebelumnya tidak relevan lagi. */
watch(wigId, () => (lagId.value = SEMUA));

const namaCabang = computed(
    () => props.cabangs.find((c) => c.id === props.filter.cabang_id)?.nama ?? '—'
);

const periode = (m) => `Minggu ${m} / ${BULAN_PANJANG[props.filter.bulan - 1]} ${props.filter.tahun}`;

const membandingkan = computed(() => props.detailLeadBanding !== null);

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
</script>

<template>
    <Head title="Dashboard Wilayah" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Dashboard Wilayah</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Peringkat kantor cabang pada {{ BULAN_PANJANG[filter.bulan - 1] }} {{ filter.tahun }} — Minggu
                        {{ filter.minggu }}<template v-if="membandingkan">, dibandingkan dengan Minggu
                        {{ filter.minggu_banding }}</template>.
                    </p>
                </div>
            </div>
        </template>

        <!-- Filter -->
        <Card class="mb-6">
            <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="space-y-2 xl:col-span-2">
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

                <div class="space-y-2 xl:col-span-2">
                    <Label>LAG</Label>
                    <Select v-model="lagId" :disabled="lags.length === 0">
                        <SelectTrigger class="w-full">
                            <SelectValue :placeholder="lags.length ? 'Semua LAG' : 'Pilih WIG dulu'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="SEMUA">Semua LAG</SelectItem>
                            <SelectItem v-for="l in lags" :key="l.id" :value="String(l.id)">
                                {{ l.kode_lag }} — {{ l.nama_lag }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label for="tahun">Tahun</Label>
                    <Input id="tahun" v-model.number="tahun" type="number" />
                </div>

                <div class="space-y-2">
                    <Label>Bulan</Label>
                    <Select v-model="bulan">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="(b, i) in BULAN_PANJANG" :key="b" :value="String(i + 1)">
                                {{ b }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Minggu</Label>
                    <Select v-model="minggu">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="m in jumlahMinggu" :key="m" :value="String(m)">Minggu {{ m }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Bandingkan dengan</Label>
                    <Select v-model="mingguBanding">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="TANPA">Tanpa pembanding</SelectItem>
                            <SelectItem
                                v-for="m in jumlahMinggu"
                                :key="m"
                                :value="String(m)"
                                :disabled="String(m) === minggu"
                            >
                                Minggu {{ m }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2 sm:col-span-2 xl:col-span-2">
                    <Label>Kantor Cabang</Label>
                    <Select v-model="cabangId">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih cabang" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                {{ c.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Sasaran yang sedang ditinjau -->
        <Card v-if="sasaran" class="mb-6 overflow-hidden py-0">
            <div class="divide-y">
                <div class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
                    <span class="w-32 shrink-0 text-sm font-semibold">WIG</span>
                    <p class="text-sm">
                        <Badge variant="secondary" class="mr-2 font-mono text-xs">{{ sasaran.wig.kode }}</Badge>
                        {{ sasaran.wig.nama }}
                    </p>
                </div>

                <div v-if="sasaran.lag" class="bg-muted/40 flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
                    <span class="w-32 shrink-0 text-sm font-semibold">LAG</span>
                    <p class="text-sm">
                        <Badge variant="outline" class="mr-2 font-mono text-xs">{{ sasaran.lag.kode }}</Badge>
                        {{ sasaran.lag.nama }}
                    </p>
                </div>

                <div class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
                    <span class="w-32 shrink-0 text-sm font-semibold">Lead Measure</span>
                    <ol v-if="sasaran.leads.length" class="list-decimal space-y-1 pl-4 text-sm">
                        <li v-for="lead in sasaran.leads" :key="lead.kode">
                            {{ lead.nama }}
                            <span class="text-muted-foreground text-xs">({{ lead.kode }})</span>
                        </li>
                    </ol>
                    <p v-else class="text-muted-foreground text-sm">
                        Belum ada Lead Measure aktif untuk kombinasi ini.
                    </p>
                </div>
            </div>
        </Card>

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

        <!-- Detail Lead: satu atau dua minggu -->
        <div :class="cn('mb-6 grid gap-4', membandingkan && '2xl:grid-cols-2')">
            <TabelDetailLead
                :judul="`Detail Lead Measure — ${namaCabang}`"
                :periode="periode(filter.minggu)"
                :baris="detailLead"
            />

            <TabelDetailLead
                v-if="membandingkan"
                :judul="`Detail Lead Measure — ${namaCabang}`"
                :periode="periode(filter.minggu_banding)"
                :baris="detailLeadBanding"
            />
        </div>

        <!-- Grafik peringkat -->
        <Card v-if="peringkat.length" class="mb-6">
            <CardHeader>
                <CardTitle class="text-base">Peringkat Capaian Cabang</CardTitle>
                <CardDescription>
                    Rata-rata persentase Lead Measure pada Minggu {{ filter.minggu }}. Hijau ≥100%, kuning ≥90%,
                    merah di bawahnya.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Grafik tipe="bar" :data="dataPeringkat" :opsi="opsiPeringkat" :tinggi-px="tinggiGrafik" />
            </CardContent>
        </Card>

        <!-- Tabel peringkat -->
        <Card class="overflow-hidden py-0">
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
    </AppLayout>
</template>
