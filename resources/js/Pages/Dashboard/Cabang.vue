<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Grafik from '@/components/grafik/Grafik.vue';
import { opsiDasar, warnaToken } from '@/components/grafik/pakaiTemaGrafik';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronDown, Minus, Target, TrendingDown, TrendingUp } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    cabangs: { type: Array, required: true },
    filter: { type: Object, required: true },
    terkunciCabang: { type: Boolean, default: false },
    ringkasan: { type: Object, required: true },
    bulanData: { type: Array, required: true },
    rankingCabang: { type: Array, required: true },
    wigProgress: { type: Array, required: true },
    pohonWig: { type: Array, required: true },
});

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* ---------------- Filter periode ---------------- */
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : '');
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));
const minggu = ref(String(props.filter.minggu));

const muatUlang = () =>
    router.get(
        '/dashboard-cabang',
        {
            cabang_id: cabangId.value || undefined,
            tahun: tahun.value,
            bulan: bulan.value,
            minggu: minggu.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );

watch([cabangId, tahun, bulan, minggu], muatUlang);

const namaCabang = computed(
    () => props.cabangs.find((c) => String(c.id) === cabangId.value)?.nama ?? '—'
);

const persenOnTrack = computed(() =>
    props.ringkasan.total_lead ? Math.round((props.ringkasan.on_track / props.ringkasan.total_lead) * 100) : 0
);

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

/** Format nilai mengikuti App\Support\Format::nilai di sisi server. */
const nilai = (v, satuan) => {
    const s = (satuan ?? '').trim();

    if (s.includes('%')) {
        return `${angka(v)}%`;
    }

    if (s === '' || s.toLowerCase().startsWith('rp')) {
        return `Rp ${Number(v ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
    }

    return `${Number(v ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })} ${s}`;
};

/* ---------------- Grafik tren bulanan ---------------- */
const dataTren = computed(() => ({
    labels: BULAN,
    datasets: [
        {
            label: '% Capaian',
            data: props.bulanData,
            backgroundColor: warnaToken('chart-1'),
            borderRadius: 4,
        },
    ],
}));

const opsiTren = computed(() => opsiDasar({ maxY: 150, legend: false }));

/* ---------------- Detail WIG ---------------- */
const wigDipilih = ref(0);
const mode = ref('bulanan');

const wp = computed(() => props.wigProgress[wigDipilih.value] ?? null);

watch(
    () => props.wigProgress,
    () => {
        if (wigDipilih.value > props.wigProgress.length - 1) {
            wigDipilih.value = 0;
        }
    }
);

const dataDetail = computed(() => {
    if (! wp.value) {
        return { labels: [], datasets: [] };
    }

    if (mode.value === 'bulanan') {
        return {
            labels: BULAN,
            datasets: [
                {
                    label: '% Capaian WIG (kumulatif)',
                    data: wp.value.wig_pct_bulan,
                    borderColor: warnaToken('chart-1'),
                    backgroundColor: warnaToken('chart-1', 0.15),
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
                {
                    label: '% Capaian Aktivitas (Lead bulanan)',
                    data: wp.value.lead_pct_bulan,
                    borderColor: warnaToken('chart-2'),
                    backgroundColor: warnaToken('chart-2', 0.15),
                    borderWidth: 3,
                    borderDash: [6, 4],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 3,
                },
            ],
        };
    }

    return {
        labels: wp.value.lead_label_mingguan,
        datasets: [
            {
                label: '% Aktivitas mingguan',
                data: wp.value.lead_pct_mingguan,
                backgroundColor: wp.value.lead_pct_mingguan.map((v) =>
                    v >= 100
                        ? warnaToken('success')
                        : v >= 70
                          ? warnaToken('warning')
                          : v > 0
                            ? warnaToken('destructive')
                            : warnaToken('muted')
                ),
                borderRadius: 2,
            },
        ],
    };
});

const opsiDetail = computed(() => opsiDasar({ legend: mode.value === 'bulanan' }));

/* Insight tekstual — teksnya dipertahankan dari versi Blade yang diarsipkan. */
const insight = computed(() => {
    if (! wp.value) {
        return null;
    }

    const k = wp.value.korelasi ?? {};
    const wigNow = wp.value.wig_pct_kini ?? 0;
    const leadNow = wp.value.lead_pct_kini ?? 0;
    const gap = (leadNow - wigNow).toFixed(1);

    const isi = {
        kuat: {
            nada: 'success',
            judul: 'Aktivitas (Lead) terbukti mendorong WIG.',
            pesan: `Korelasi r=${k.r}. Pertahankan ritme aktivitas saat ini — setiap kenaikan aktivitas berdampak ke hasil WIG.`,
        },
        sedang: {
            nada: 'warning',
            judul: 'Aktivitas cukup berpengaruh.',
            pesan: `Korelasi r=${k.r}. Ada hubungan tapi belum kuat — evaluasi apakah ada Lead Measure tambahan yang perlu diaktifkan, atau Lead yang ada perlu ditingkatkan kualitasnya.`,
        },
        lemah: {
            nada: 'destructive',
            judul: 'Aktivitas tidak nyambung dengan hasil WIG.',
            pesan: `Korelasi r=${k.r}. Kemungkinan: (1) Lead Measure yang dipilih bukan pendorong utama, (2) hasil WIG dipengaruhi faktor eksternal, atau (3) ada jeda waktu yang lebih panjang. Pertimbangkan ganti atau tambah Lead Measure.`,
        },
        kurang_data: {
            nada: 'muted',
            judul: 'Data belum cukup',
            pesan: 'Minimal butuh 3 bulan realisasi WIG dan Aktivitas untuk menyimpulkan hubungan.',
        },
    }[k.level ?? 'kurang_data'];

    return {
        ...isi,
        gap:
            Math.abs(gap) > 5 && k.level !== 'kurang_data'
                ? `Bulan ini: % Aktivitas ${leadNow}% vs % WIG ${wigNow}% (gap ${gap >= 0 ? '+' : ''}${gap}%).`
                : null,
    };
});

const KELAS_NADA = {
    success: 'border-success/30 bg-success/10 text-success',
    warning: 'border-warning/40 bg-warning/10 text-warning-foreground',
    destructive: 'border-destructive/30 bg-destructive/10 text-destructive',
    muted: 'border-border bg-muted text-muted-foreground',
};

const KELAS_PACE = {
    on: 'bg-success',
    warn: 'bg-warning',
    behind: 'bg-destructive',
    unknown: 'bg-muted-foreground',
};

const LABEL_PACE = {
    on: 'On Pace',
    warn: 'Hati-hati',
    behind: 'Belum Tercapai',
    unknown: '—',
};

const LABEL_STATUS = { on: 'On Track', warn: 'Hati-hati', behind: 'Belum Tercapai' };

/* ---------------- Pohon WIG ---------------- */
const wigTerbuka = ref({});
const toggleWig = (id) => (wigTerbuka.value[id] = ! wigTerbuka.value[id]);
</script>

<template>
    <Head title="Dashboard Cabang" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Dashboard 4DX</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ namaCabang }} · {{ BULAN[filter.bulan - 1] }} {{ filter.tahun }} — Minggu {{ filter.minggu }}
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div v-if="!terkunciCabang" class="space-y-1">
                        <Label class="text-xs">Cabang</Label>
                        <Select v-model="cabangId">
                            <SelectTrigger class="w-52"><SelectValue placeholder="Pilih cabang" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in cabangs" :key="c.id" :value="String(c.id)">
                                    {{ c.nama }}
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
                    <p class="text-muted-foreground text-sm">Total WIG</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.total_wig }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Total Lead Measure</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.total_lead }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Lead On Track</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ ringkasan.on_track }}<span class="text-muted-foreground text-lg">/{{ ringkasan.total_lead }}</span>
                    </p>
                    <div class="bg-muted mt-2 h-1.5 overflow-hidden rounded-full">
                        <div class="bg-success h-full rounded-full" :style="{ width: `${persenOnTrack}%` }" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent>
                    <p class="text-muted-foreground text-sm">Rata-rata Capaian</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ ringkasan.avg_pct }}%</p>
                </CardContent>
            </Card>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <!-- Tren bulanan -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base">Tren Capaian Aktivitas {{ filter.tahun }}</CardTitle>
                    <CardDescription>Rata-rata persentase realisasi Lead Measure per bulan.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Grafik tipe="bar" :data="dataTren" :opsi="opsiTren" tinggi="h-64" />
                </CardContent>
            </Card>

            <!-- Ranking -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Ranking Cabang</CardTitle>
                    <CardDescription>Rata-rata capaian sepanjang {{ filter.tahun }}.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="(baris, i) in rankingCabang" :key="baris.cabang_id" class="flex items-center gap-3">
                        <span
                            :class="
                                cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                    i < 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'
                                )
                            "
                        >
                            {{ i + 1 }}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm">{{ baris.nama }}</span>
                        <span class="text-sm font-medium tabular-nums">{{ baris.pct }}%</span>
                    </div>
                    <p v-if="rankingCabang.length === 0" class="text-muted-foreground py-8 text-center text-sm">
                        Belum ada data realisasi.
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Progres WIG + grafik detail -->
        <Card v-if="wigProgress.length" class="mb-6">
            <CardHeader>
                <CardTitle class="text-base">Progres WIG — {{ namaCabang }}</CardTitle>
                <CardDescription>Klik baris WIG untuk melihat detail grafiknya di bawah.</CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="min-w-[16rem]">WIG</TableHead>
                                <TableHead class="text-right">Sekarang / Target</TableHead>
                                <TableHead class="w-48 text-center">Progres Tahun</TableHead>
                                <TableHead class="text-center">% WIG</TableHead>
                                <TableHead class="text-center">% Aktivitas</TableHead>
                                <TableHead class="text-center">Gap</TableHead>
                                <TableHead class="text-center">Korelasi</TableHead>
                                <TableHead class="text-center">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(item, i) in wigProgress"
                                :key="item.wig_id"
                                :class="cn('cursor-pointer', wigDipilih === i && 'bg-secondary')"
                                @click="wigDipilih = i"
                            >
                                <TableCell>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <Badge variant="secondary" class="font-mono text-xs">{{ item.kode_wig }}</Badge>
                                        <Badge v-if="item.bidang" variant="outline" :class="kelasBidang(item.bidang)">{{ item.bidang }}</Badge>
                                    </div>
                                    <p class="mt-1 max-w-[28rem] text-sm font-medium whitespace-normal">
                                        {{ item.nama_wig }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-right text-sm">
                                    <span class="font-medium">{{ nilai(item.nilai_sekarang, item.satuan) }}</span>
                                    <span class="text-muted-foreground block">
                                        / {{ nilai(item.nilai_target, item.satuan) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="bg-muted h-4 overflow-hidden rounded-full">
                                        <div
                                            :class="cn('flex h-full items-center justify-end rounded-full pr-1.5 text-[10px] font-medium text-white', KELAS_PACE[item.pace])"
                                            :style="{ width: `${item.pct}%` }"
                                        >
                                            {{ item.pct_raw }}%
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center text-sm tabular-nums">
                                    {{ Math.round(item.wig_pct_kini * 10) / 10 }}%
                                </TableCell>
                                <TableCell class="text-center text-sm tabular-nums">
                                    {{ Math.round(item.lead_pct_kini * 10) / 10 }}%
                                </TableCell>
                                <TableCell class="text-center text-sm tabular-nums">
                                    {{ (item.lead_pct_kini - item.wig_pct_kini).toFixed(1) }}%
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge
                                        variant="outline"
                                        :class="KELAS_NADA[{ kuat: 'success', sedang: 'warning', lemah: 'destructive', kurang_data: 'muted' }[item.korelasi.level]]"
                                        :title="item.korelasi.label"
                                    >
                                        {{ item.korelasi.r ?? '—' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline">{{ LABEL_PACE[item.pace] }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Detail WIG terpilih -->
                <div v-if="wp" class="rounded-lg border p-4">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">[{{ wp.kode_wig }}] {{ wp.nama_wig }}</p>
                            <p class="text-muted-foreground mt-0.5 text-sm">
                                Awal: {{ nilai(wp.nilai_awal, wp.satuan) }} · Sekarang:
                                {{ nilai(wp.nilai_sekarang, wp.satuan) }} · Target:
                                {{ nilai(wp.nilai_target, wp.satuan) }}
                            </p>
                        </div>

                        <div class="bg-muted flex gap-1 rounded-lg p-1">
                            <Button
                                :variant="mode === 'bulanan' ? 'default' : 'ghost'"
                                size="sm"
                                @click="mode = 'bulanan'"
                            >
                                Bulanan
                            </Button>
                            <Button
                                :variant="mode === 'mingguan' ? 'default' : 'ghost'"
                                size="sm"
                                @click="mode = 'mingguan'"
                            >
                                Mingguan
                            </Button>
                        </div>
                    </div>

                    <Grafik :tipe="mode === 'bulanan' ? 'line' : 'bar'" :data="dataDetail" :opsi="opsiDetail" />

                    <div v-if="insight" :class="cn('mt-4 rounded-lg border p-3 text-sm', KELAS_NADA[insight.nada])">
                        <p class="font-medium">{{ insight.judul }}</p>
                        <p class="mt-1 opacity-90">{{ insight.pesan }}</p>
                        <p v-if="insight.gap" class="mt-1 font-medium">{{ insight.gap }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Scoreboard mingguan -->
        <Card class="py-0">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Scoreboard WIG Mingguan</h2>
                <p class="text-muted-foreground text-sm">
                    {{ namaCabang }} · {{ BULAN[filter.bulan - 1] }} {{ filter.tahun }} — Minggu {{ filter.minggu }}
                </p>
            </div>

            <CardContent class="p-0">
                <div v-for="wig in pohonWig" :key="wig.id" class="border-b last:border-b-0">
                    <button
                        class="hover:bg-secondary/50 flex w-full items-center gap-3 px-4 py-3 text-left transition-colors"
                        @click="toggleWig(wig.id)"
                    >
                        <Badge variant="secondary" class="font-mono text-xs">{{ wig.kode_wig }}</Badge>
                        <span class="min-w-0 flex-1 text-sm font-medium">{{ wig.nama_wig }}</span>
                        <Badge v-if="wig.bidang" variant="outline" :class="kelasBidang(wig.bidang)">{{ wig.bidang }}</Badge>
                        <ChevronDown :class="cn('size-4 shrink-0 opacity-60 transition-transform', wigTerbuka[wig.id] && 'rotate-180')" />
                    </button>

                    <div v-if="wigTerbuka[wig.id]" class="space-y-4 px-4 pb-4">
                        <div v-for="lag in wig.lags" :key="lag.id">
                            <p class="text-muted-foreground mb-2 text-sm">
                                <Badge variant="outline" class="mr-1.5 font-mono text-xs">{{ lag.kode_lag }}</Badge>
                                {{ lag.nama_lag }}
                                <span class="ml-1">
                                    (Target Tahunan: {{ angka(lag.target_tahunan) }} {{ lag.satuan }})
                                </span>
                            </p>

                            <div class="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="hover:bg-transparent">
                                            <TableHead class="w-12 pl-3 text-center">No</TableHead>
                                            <TableHead class="min-w-[16rem]">Lead Measure</TableHead>
                                            <TableHead class="text-right">Target</TableHead>
                                            <TableHead class="text-right">Realisasi</TableHead>
                                            <TableHead class="text-center">% Capaian</TableHead>
                                            <TableHead class="text-center">Status</TableHead>
                                            <TableHead class="text-center">Tren</TableHead>
                                            <TableHead class="min-w-[12rem]">Keterangan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="(lead, i) in lag.leads" :key="lead.id">
                                            <TableCell class="text-muted-foreground pl-3 text-center tabular-nums">
                                                {{ i + 1 }}
                                            </TableCell>
                                            <TableCell class="align-top text-sm whitespace-normal">
                                                {{ lead.nama_lead }}
                                                <span class="text-muted-foreground">({{ lead.kode_lead }})</span>
                                            </TableCell>
                                            <TableCell class="text-right text-sm tabular-nums">
                                                {{ lead.target !== null ? angka(lead.target) : '—' }}
                                            </TableCell>
                                            <TableCell class="text-right text-sm tabular-nums">
                                                {{ lead.realisasi !== null ? angka(lead.realisasi) : '—' }}
                                            </TableCell>
                                            <TableCell class="text-center text-sm font-medium tabular-nums">
                                                {{ lead.persentase }}%
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <Badge
                                                    variant="outline"
                                                    :class="KELAS_NADA[{ on: 'success', warn: 'warning', behind: 'destructive' }[lead.status]]"
                                                >
                                                    {{ LABEL_STATUS[lead.status] }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <span
                                                    class="inline-flex items-center gap-1 text-sm"
                                                    :class="{
                                                        'text-success': lead.tren === 'naik',
                                                        'text-destructive': lead.tren === 'turun',
                                                        'text-muted-foreground': lead.tren === 'stabil',
                                                    }"
                                                    :title="`Sebelumnya ${lead.persentase_sebelumnya}%`"
                                                >
                                                    <component
                                                        :is="{ naik: TrendingUp, turun: TrendingDown, stabil: Minus }[lead.tren]"
                                                        class="size-4"
                                                    />
                                                    {{ { naik: 'Naik', turun: 'Turun', stabil: 'Stabil' }[lead.tren] }}
                                                </span>
                                            </TableCell>
                                            <TableCell class="text-muted-foreground text-sm whitespace-pre-wrap">
                                                {{ lead.catatan || '—' }}
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="lag.leads.length === 0" class="hover:bg-transparent">
                                            <TableCell colspan="8" class="text-muted-foreground py-6 text-center text-sm">
                                                Belum ada Lead Measure.
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>

                        <p v-if="wig.lags.length === 0" class="text-muted-foreground text-sm">
                            Belum ada Lag Measure pada WIG ini.
                        </p>
                    </div>
                </div>

                <div v-if="pohonWig.length === 0" class="py-12">
                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                        <Target class="size-8 opacity-40" />
                        <p class="text-sm">Belum ada WIG untuk tahun {{ filter.tahun }}.</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
