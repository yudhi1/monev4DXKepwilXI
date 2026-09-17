<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TabelDetailLead from '@/components/TabelDetailLead.vue';
import TabelPeringkat from '@/components/TabelPeringkat.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Building2, ChevronDown, CircleCheckBig, Gauge, Target } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    cabangs: { type: Array, required: true },
    wigs: { type: Array, required: true },
    lags: { type: Array, required: true },
    peringkat: { type: Array, required: true },
    peringkatBanding: { type: [Array, null], default: null },
    ringkasan: { type: Object, required: true },
    detailLead: { type: Array, required: true },
    detailLeadBanding: { type: [Array, null], default: null },
    sasaran: { type: [Object, null], default: null },
    filter: { type: Object, required: true },
    jumlahMinggu: { type: Number, default: 5 },
});

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
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : SEMUA);

/* ---------------- Kartu sasaran ---------------- */
const KUNCI_SASARAN = 'kepwil.sasaran-terbuka';

// Pilihan buka/tutup diingat per browser; localStorage bisa gagal di mode privat.
const bacaPreferensiSasaran = () => {
    try {
        return localStorage.getItem(KUNCI_SASARAN) !== '0';
    } catch {
        return true;
    }
};

const sasaranTerbuka = ref(bacaPreferensiSasaran());

watch(sasaranTerbuka, (terbuka) => {
    try {
        localStorage.setItem(KUNCI_SASARAN, terbuka ? '1' : '0');
    } catch {
        // Preferensi tampilan saja — abaikan bila penyimpanan diblokir.
    }
});

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
            cabang_id: cabangId.value === SEMUA ? undefined : cabangId.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );

watch([tahun, bulan, minggu, mingguBanding, wigId, lagId, cabangId], muatUlang);

/* Mengganti WIG membuat pilihan LAG sebelumnya tidak relevan lagi. */
watch(wigId, () => (lagId.value = SEMUA));

const namaCabang = computed(
    () => props.cabangs.find((c) => c.id === props.filter.cabang_id)?.nama ?? 'Semua Unit Kerja'
);

/* Kolom unit kerja hanya berguna saat rinciannya mencakup lebih dari satu. */
const semuaUnitKerja = computed(() => props.filter.cabang_id === null);


/* ---------------- Kartu ringkasan ---------------- */
/*
 | Rata-rata wilayah diwarnai mengikuti ambang status yang sama dengan
 | tabel peringkat, sehingga warnanya membawa arti — bukan sekadar hiasan.
 */
const nadaRata = computed(() => {
    const nilai = props.ringkasan.rata_wilayah;

    return nilai >= 100 ? 'success' : nilai >= 90 ? 'warning' : 'destructive';
});

const NADA = {
    primary: {
        kartu: 'border-l-primary',
        angka: 'text-primary',
        ikon: 'bg-primary/10 text-primary',
    },
    ungu: {
        kartu: 'border-l-bidang-sdmuk',
        angka: 'text-bidang-sdmuk',
        ikon: 'bg-bidang-sdmuk/10 text-bidang-sdmuk',
    },
    success: {
        kartu: 'border-l-success',
        angka: 'text-success',
        ikon: 'bg-success/10 text-success',
    },
    warning: {
        kartu: 'border-l-warning',
        angka: 'text-warning-foreground',
        ikon: 'bg-warning/15 text-warning-foreground',
    },
    destructive: {
        kartu: 'border-l-destructive',
        angka: 'text-destructive',
        ikon: 'bg-destructive/10 text-destructive',
    },
};

const kartuRingkasan = computed(() => {
    const susun = (nada, label, nilai, ikon, satuan = null) => ({
        label,
        nilai,
        satuan,
        ikon,
        kelasKartu: NADA[nada].kartu,
        kelasAngka: NADA[nada].angka,
        kelasIkon: NADA[nada].ikon,
    });

    return [
        susun('primary', 'Total Unit Kerja', props.ringkasan.total_cabang, Building2),
        susun('ungu', 'Total WIG', props.ringkasan.total_wig, Target),
        susun(nadaRata.value, 'Rata-rata Wilayah', `${props.ringkasan.rata_wilayah}%`, Gauge),
        susun(
            'success',
            'Unit Kerja On Track',
            props.ringkasan.cabang_on_track,
            CircleCheckBig,
            `/${props.ringkasan.total_cabang}`
        ),
    ];
});

const periode = (m) => `Minggu ${m} / ${BULAN_PANJANG[props.filter.bulan - 1]} ${props.filter.tahun}`;

const membandingkan = computed(() => props.detailLeadBanding !== null);
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
                    <Label>Unit Kerja</Label>
                    <Select v-model="cabangId">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua unit kerja" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="SEMUA">Semua unit kerja</SelectItem>
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
                <!--
                  | Daftar Lead Measure bisa panjang sehingga kartu ini mendorong
                  | tabel peringkat jauh ke bawah. Ringkasannya tetap terlihat di
                  | baris ini saat ditutup.
                -->
                <button
                    type="button"
                    class="hover:bg-muted/40 flex w-full items-center gap-3 px-4 py-3 text-left"
                    :aria-expanded="sasaranTerbuka"
                    @click="sasaranTerbuka = !sasaranTerbuka"
                >
                    <ChevronDown
                        :class="cn('text-muted-foreground size-4 shrink-0 transition-transform', !sasaranTerbuka && '-rotate-90')"
                    />
                    <span class="text-sm font-semibold">Sasaran yang ditinjau</span>
                    <span v-if="!sasaranTerbuka" class="text-muted-foreground min-w-0 truncate text-sm">
                        {{ sasaran.wig.kode }} · {{ sasaran.leads.length }} Lead Measure
                    </span>
                </button>

                <div v-show="sasaranTerbuka" class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
                    <span class="w-32 shrink-0 text-sm font-semibold">WIG</span>
                    <p class="text-sm">
                        <Badge variant="secondary" class="mr-2 font-mono text-xs">{{ sasaran.wig.kode }}</Badge>
                        {{ sasaran.wig.nama }}
                    </p>
                </div>

                <div v-if="sasaran.lag" v-show="sasaranTerbuka" class="bg-muted/40 flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
                    <span class="w-32 shrink-0 text-sm font-semibold">LAG</span>
                    <p class="text-sm">
                        <Badge variant="outline" class="mr-2 font-mono text-xs">{{ sasaran.lag.kode }}</Badge>
                        {{ sasaran.lag.nama }}
                    </p>
                </div>

                <div v-show="sasaranTerbuka" class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:gap-4">
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
            <Card v-for="k in kartuRingkasan" :key="k.label" :class="cn('border-l-4', k.kelasKartu)">
                <CardContent class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-muted-foreground text-sm">{{ k.label }}</p>
                        <p :class="cn('mt-1 text-3xl font-semibold tabular-nums', k.kelasAngka)">
                            {{ k.nilai }}<span v-if="k.satuan" class="text-muted-foreground text-lg">{{ k.satuan }}</span>
                        </p>
                    </div>
                    <span :class="cn('flex size-9 shrink-0 items-center justify-center rounded-lg', k.kelasIkon)">
                        <component :is="k.ikon" class="size-[18px]" />
                    </span>
                </CardContent>
            </Card>
        </div>

        <!-- Peringkat: satu atau dua minggu, berdampingan bila layar memadai -->
        <div :class="cn('mb-6 grid gap-4', membandingkan && 'xl:grid-cols-2')">
            <TabelPeringkat
                :periode="periode(filter.minggu)"
                :baris="peringkat"
                :cabang-dipilih="filter.cabang_id"
                @pilih="(id) => (cabangId = String(id))"
            />

            <TabelPeringkat
                v-if="membandingkan"
                :periode="periode(filter.minggu_banding)"
                :baris="peringkatBanding"
                :cabang-dipilih="filter.cabang_id"
                @pilih="(id) => (cabangId = String(id))"
            />
        </div>

        <!-- Detail Lead: satu atau dua minggu -->
        <div :class="cn('grid gap-4', membandingkan && '2xl:grid-cols-2')">
            <TabelDetailLead
                :judul="`Detail Lead Measure — ${namaCabang}`"
                :periode="periode(filter.minggu)"
                :baris="detailLead"
                :tampilkan-cabang="semuaUnitKerja"
            />

            <TabelDetailLead
                v-if="membandingkan"
                :judul="`Detail Lead Measure — ${namaCabang}`"
                :periode="periode(filter.minggu_banding)"
                :baris="detailLeadBanding"
                :tampilkan-cabang="semuaUnitKerja"
            />
        </div>
    </AppLayout>
</template>
