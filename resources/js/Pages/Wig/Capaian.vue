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
import { Save, Target } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';
import { cn } from '@/lib/utils';

const props = defineProps({
    wigs: { type: Array, required: true },
    wig: { type: [Object, null], default: null },
    baris: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    daftarSatuan: { type: Array, required: true },
    filter: { type: Object, required: true },
    bisaUbahTarget: { type: Boolean, default: false },
});

/* ---------------- Konteks ---------------- */
const wigId = ref(props.wig ? String(props.wig.id) : '');
const tahun = ref(props.filter.tahun);
const bulanAcuan = ref(String(props.filter.bulan));

/* Per Bulan menampilkan angka bulan itu; s.d. Bulan menampilkan akumulasinya. */
const mode = ref('bulan');

const muatUlang = () =>
    router.get(
        '/wig-capaian',
        { wig_id: wigId.value || undefined, tahun: tahun.value, bulan: bulanAcuan.value },
        { preserveState: false, replace: true }
    );

watch([wigId, tahun, bulanAcuan], muatUlang);

/* ---------------- Form ---------------- */
const form = useForm({
    wig_id: props.wig?.id,
    tahun: props.filter.tahun,
    baris: props.baris.map((b) => ({ ...b, bulan: b.bulan.map((m) => ({ ...m })) })),
});

watch(
    () => props.baris,
    (baru) => {
        form.wig_id = props.wig?.id;
        form.tahun = props.filter.tahun;
        form.baris = baru.map((b) => ({ ...b, bulan: b.bulan.map((m) => ({ ...m })) }));
    }
);

const simpan = () => form.post('/wig-capaian', { preserveScroll: true });

/* ---------------- Perhitungan ---------------- */
const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

/** Jumlah nilai bulan 1..n; dipakai untuk mode "s.d. Bulan". */
const akumulasi = (bulan, sampai, kunci) =>
    bulan.slice(0, sampai).reduce((jml, m) => jml + Number(m[kunci] || 0), 0);

const nilaiSel = (baris, indeks, kunci) =>
    mode.value === 'bulan'
        ? Number(baris.bulan[indeks][kunci] || 0)
        : akumulasi(baris.bulan, indeks + 1, kunci);

const persen = (pembilang, penyebut) =>
    Number(penyebut) > 0 ? Math.round((pembilang / penyebut) * 10000) / 100 : 0;

/** Capaian tiap kolom bulan, mengikuti mode tampilan yang sedang aktif. */
const persenSel = (baris, indeks) =>
    persen(nilaiSel(baris, indeks, 'realisasi'), nilaiSel(baris, indeks, 'target'));

/* Kedua kolom ringkasan mengacu pada bulan yang dipilih di filter. */
const indeksAcuan = computed(() => Number(props.filter.bulan) - 1);

/** Realisasi s.d. bulan acuan dibanding target tahunan. */
const persenTahunan = (baris) =>
    persen(akumulasi(baris.bulan, indeksAcuan.value + 1, 'realisasi'), baris.nilai_target);

/** Realisasi bulan acuan dibanding target bulan acuan. */
const persenBulanan = (baris) =>
    persen(
        Number(baris.bulan[indeksAcuan.value].realisasi || 0),
        Number(baris.bulan[indeksAcuan.value].target || 0)
    );

const warnaPersen = (nilai) =>
    nilai >= 100 ? 'text-success' : nilai >= 90 ? 'text-warning-foreground' : 'text-destructive';

/** Bulan yang sudah berjalan diberi latar berbeda agar terpisah dari rencana ke depan. */
const sudahLewat = (indeks) => indeks <= indeksAcuan.value;
</script>

<template>
    <Head title="Target & Realisasi WIG" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Target &amp; Realisasi WIG</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Target tahunan, target bulanan, dan realisasinya dalam satu tabel.
                    </p>
                </div>
                <Button :disabled="!wig || form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </template>

        <!-- Konteks -->
        <Card class="mb-4">
            <CardContent class="space-y-3">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="min-w-80 flex-1 space-y-2">
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

                    <div class="w-28 space-y-2">
                        <Label for="tahun">Tahun</Label>
                        <Input id="tahun" v-model.number="tahun" type="number" />
                    </div>

                    <div class="w-40 space-y-2">
                        <Label>Bulan berjalan</Label>
                        <Select v-model="bulanAcuan">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(b, i) in namaBulan" :key="b" :value="String(i + 1)">
                                    {{ b }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Tampilan angka bulanan</Label>
                        <div class="bg-muted flex gap-1 rounded-lg p-1">
                            <Button :variant="mode === 'bulan' ? 'default' : 'ghost'" size="sm" @click="mode = 'bulan'">
                                Per Bulan
                            </Button>
                            <Button :variant="mode === 'sd' ? 'default' : 'ghost'" size="sm" @click="mode = 'sd'">
                                s.d. Bulan
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="wig" class="bg-accent/40 flex flex-wrap items-center gap-2 rounded-lg border p-3 text-sm">
                    <Badge variant="secondary" class="font-mono">{{ wig.kode_wig }}</Badge>
                    <Badge v-if="wig.bidang" variant="outline" :class="kelasBidang(wig.bidang)">{{ wig.bidang }}</Badge>
                    <span class="text-muted-foreground">{{ wig.nama_wig }}</span>
                </div>

                <p v-if="!bisaUbahTarget" class="text-muted-foreground text-sm">
                    Baris target ditetapkan oleh Kedeputian Wilayah, jadi hanya realisasi yang dapat kamu isi.
                </p>

                <p v-if="mode === 'sd'" class="text-muted-foreground text-sm">
                    Sedang menampilkan angka akumulasi. Untuk mengisi data, ganti ke <strong>Per Bulan</strong>.
                </p>
            </CardContent>
        </Card>

        <!--
          Target dan realisasi ditumpuk sebagai sub-baris, sehingga tiap bulan
          cukup satu kolom — lebar tabel berkurang sekitar separuh.
        -->
        <Card v-if="wig" class="overflow-hidden py-0">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-muted/60">
                                <th class="bg-muted/60 sticky left-0 z-20 min-w-[11rem] border-b border-r px-3 py-2 text-left font-medium">
                                    Unit Kerja
                                </th>
                                <th class="bg-muted/60 sticky left-[11rem] z-20 min-w-[6.5rem] border-b border-r px-3 py-2 text-left font-medium">
                                    Baris
                                </th>
                                <th
                                    v-for="(b, i) in namaBulan"
                                    :key="b"
                                    :class="cn('min-w-[7rem] border-b border-r px-3 py-2 text-right font-medium', sudahLewat(i) && 'bg-accent/40')"
                                >
                                    {{ b.slice(0, 3) }}
                                </th>
                                <th class="min-w-[9rem] border-b border-r px-3 py-2 text-right font-medium">
                                    Target {{ filter.tahun }}
                                </th>
                                <th class="min-w-[7.5rem] border-b border-r px-3 py-2 text-center font-medium">
                                    % thd Target
                                </th>
                                <th class="min-w-[7.5rem] border-b border-r px-3 py-2 text-center font-medium">
                                    % Bulan Berjalan
                                </th>
                                <th class="min-w-[7rem] border-b border-r px-3 py-2 text-left font-medium">Satuan</th>
                                <th class="min-w-[9rem] border-b px-3 py-2 text-left font-medium">Tanggal Target</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="b in form.baris" :key="b.cabang_id">
                                <!-- Sub-baris 1: target bulanan, sekaligus memuat kolom yang menaungi ketiganya -->
                                <tr class="hover:bg-muted/20">
                                    <td
                                        rowspan="3"
                                        class="bg-background sticky left-0 z-10 border-b-2 border-r px-3 py-2 align-top font-medium"
                                    >
                                        {{ b.cabang_nama }}
                                    </td>
                                    <td class="bg-background text-muted-foreground sticky left-[11rem] z-10 border-b border-r px-3 py-1.5">
                                        Target
                                    </td>

                                    <td
                                        v-for="(m, i) in b.bulan"
                                        :key="`t-${m.bulan}`"
                                        :class="cn('border-b border-r px-2 py-1.5', sudahLewat(i) && 'bg-accent/20')"
                                    >
                                        <Input
                                            v-if="mode === 'bulan'"
                                            v-model.number="m.target"
                                            type="number"
                                            step="any"
                                            min="0"
                                            class="h-8 text-right"
                                            :disabled="!bisaUbahTarget"
                                        />
                                        <span v-else class="block text-right tabular-nums">
                                            {{ angka(nilaiSel(b, i, 'target')) }}
                                        </span>
                                    </td>

                                    <td rowspan="3" class="border-b-2 border-r px-2 py-1.5 align-top">
                                        <Input
                                            v-model.number="b.nilai_target"
                                            type="number"
                                            step="any"
                                            class="h-8 text-right"
                                            :disabled="!bisaUbahTarget"
                                        />
                                    </td>
                                    <td
                                        rowspan="3"
                                        :class="cn('border-b-2 border-r px-3 text-center align-middle text-base font-semibold tabular-nums', warnaPersen(persenTahunan(b)))"
                                    >
                                        {{ persenTahunan(b) }}%
                                    </td>
                                    <td
                                        rowspan="3"
                                        :class="cn('border-b-2 border-r px-3 text-center align-middle text-base font-semibold tabular-nums', warnaPersen(persenBulanan(b)))"
                                    >
                                        {{ persenBulanan(b) }}%
                                    </td>
                                    <td rowspan="3" class="border-b-2 border-r px-2 py-1.5 align-top">
                                        <Select v-model="b.satuan" :disabled="!bisaUbahTarget">
                                            <SelectTrigger class="h-8 w-full"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="s in daftarSatuan" :key="s" :value="s">
                                                    {{ s }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td rowspan="3" class="border-b-2 px-2 py-1.5 align-top">
                                        <Input
                                            v-model="b.tanggal_target"
                                            type="date"
                                            class="h-8"
                                            :disabled="!bisaUbahTarget"
                                        />
                                    </td>
                                </tr>

                                <!-- Sub-baris 2: realisasi bulanan -->
                                <tr class="hover:bg-muted/20">
                                    <td class="bg-background text-muted-foreground sticky left-[11rem] z-10 border-b border-r px-3 py-1.5">
                                        Realisasi
                                    </td>
                                    <td
                                        v-for="(m, i) in b.bulan"
                                        :key="`r-${m.bulan}`"
                                        :class="cn('border-b border-r px-2 py-1.5', sudahLewat(i) && 'bg-accent/20')"
                                    >
                                        <Input
                                            v-if="mode === 'bulan'"
                                            v-model.number="m.realisasi"
                                            type="number"
                                            step="any"
                                            min="0"
                                            class="h-8 text-right"
                                        />
                                        <span v-else class="block text-right tabular-nums">
                                            {{ angka(nilaiSel(b, i, 'realisasi')) }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Sub-baris 3: capaian, dihitung dari dua sub-baris di atasnya -->
                                <tr class="hover:bg-muted/20">
                                    <td class="bg-background text-muted-foreground sticky left-[11rem] z-10 border-b-2 border-r px-3 py-1.5">
                                        % Capaian
                                    </td>
                                    <td
                                        v-for="(m, i) in b.bulan"
                                        :key="`p-${m.bulan}`"
                                        :class="
                                            cn(
                                                'border-b-2 border-r px-3 py-1.5 text-right font-medium tabular-nums',
                                                sudahLewat(i) && 'bg-accent/20',
                                                warnaPersen(persenSel(b, i))
                                            )
                                        "
                                    >
                                        {{ persenSel(b, i) }}%
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="form.baris.length === 0">
                                <td colspan="19" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Target class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada unit kerja.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
                <p class="text-muted-foreground text-sm">
                    Kolom ringkasan mengacu pada bulan {{ namaBulan[filter.bulan - 1] }}.
                    Kolom berlatar terang menandai bulan yang sudah berjalan.
                </p>
                <Button :disabled="form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </Card>

        <Card v-else>
            <CardContent class="py-16">
                <div class="text-muted-foreground flex flex-col items-center gap-2">
                    <Target class="size-8 opacity-40" />
                    <p class="text-sm">Pilih WIG terlebih dahulu.</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
