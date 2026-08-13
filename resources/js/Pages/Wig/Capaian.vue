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
import { Target, Save } from '@lucide/vue';
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

/** Jumlah nilai bulan 1..n; dipakai untuk kolom mode "s.d. Bulan". */
const akumulasi = (bulan, sampai, kunci) =>
    bulan.slice(0, sampai).reduce((jml, m) => jml + Number(m[kunci] || 0), 0);

const nilaiSel = (baris, indeks, kunci) =>
    mode.value === 'bulan'
        ? Number(baris.bulan[indeks][kunci] || 0)
        : akumulasi(baris.bulan, indeks + 1, kunci);

const persen = (pembilang, penyebut) =>
    Number(penyebut) > 0 ? Math.round((pembilang / penyebut) * 10000) / 100 : 0;

/* Kedua persentase mengacu pada bulan yang dipilih di filter. */
const indeksAcuan = computed(() => Number(props.filter.bulan) - 1);

const realisasiSdAcuan = (baris) => akumulasi(baris.bulan, indeksAcuan.value + 1, 'realisasi');
const targetSdAcuan = (baris) => akumulasi(baris.bulan, indeksAcuan.value + 1, 'target');

/** Realisasi s.d. bulan acuan dibanding target tahunan. */
const persenTahunan = (baris) => persen(realisasiSdAcuan(baris), baris.nilai_target);

/** Realisasi bulan acuan dibanding target bulan acuan. */
const persenBulanan = (baris) =>
    persen(
        Number(baris.bulan[indeksAcuan.value].realisasi || 0),
        Number(baris.bulan[indeksAcuan.value].target || 0)
    );

const warnaPersen = (nilai) =>
    nilai >= 100 ? 'text-success' : nilai >= 90 ? 'text-warning-foreground' : 'text-destructive';

/** Bulan yang sudah lewat diberi latar berbeda agar mudah dibedakan dari rencana ke depan. */
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
                    Kolom target ditetapkan oleh Kedeputian Wilayah, jadi hanya realisasi yang dapat kamu isi.
                </p>

                <p v-if="mode === 'sd'" class="text-muted-foreground text-sm">
                    Sedang menampilkan angka akumulasi. Untuk mengisi data, ganti ke <strong>Per Bulan</strong>.
                </p>
            </CardContent>
        </Card>

        <!-- Tabel lebar: kolom unit kerja dibekukan agar tetap terlihat saat digulir -->
        <Card v-if="wig" class="overflow-hidden py-0">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-muted/60">
                                <th
                                    rowspan="2"
                                    class="bg-muted/60 sticky left-0 z-20 min-w-[12rem] border-b border-r px-3 py-2 text-left font-medium"
                                >
                                    Unit Kerja
                                </th>
                                <th rowspan="2" class="min-w-[9rem] border-b border-r px-3 py-2 text-right font-medium">
                                    Target {{ filter.tahun }}
                                </th>
                                <th
                                    v-for="(b, i) in namaBulan"
                                    :key="b"
                                    colspan="2"
                                    :class="cn('border-b border-r px-3 py-1.5 text-center font-medium', sudahLewat(i) && 'bg-accent/40')"
                                >
                                    {{ b }}
                                </th>
                                <th rowspan="2" class="min-w-[8rem] border-b border-r px-3 py-2 text-center font-medium">
                                    % thd Target {{ filter.tahun }}
                                </th>
                                <th rowspan="2" class="min-w-[8rem] border-b border-r px-3 py-2 text-center font-medium">
                                    % Bulan Berjalan
                                </th>
                                <th rowspan="2" class="min-w-[7rem] border-b border-r px-3 py-2 text-left font-medium">
                                    Satuan
                                </th>
                                <th rowspan="2" class="min-w-[9rem] border-b px-3 py-2 text-left font-medium">
                                    Tanggal Target
                                </th>
                            </tr>
                            <tr class="bg-muted/60">
                                <template v-for="(b, i) in namaBulan" :key="`sub-${b}`">
                                    <th
                                        :class="cn('text-muted-foreground min-w-[7rem] border-b px-3 py-1.5 text-right text-xs font-normal', sudahLewat(i) && 'bg-accent/40')"
                                    >
                                        Target
                                    </th>
                                    <th
                                        :class="cn('text-muted-foreground min-w-[7rem] border-b border-r px-3 py-1.5 text-right text-xs font-normal', sudahLewat(i) && 'bg-accent/40')"
                                    >
                                        Realisasi
                                    </th>
                                </template>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="baris in form.baris" :key="baris.cabang_id" class="hover:bg-muted/30">
                                <td class="bg-background sticky left-0 z-10 border-b border-r px-3 py-2 font-medium">
                                    {{ baris.cabang_nama }}
                                </td>

                                <td class="border-b border-r px-2 py-1.5">
                                    <Input
                                        v-model.number="baris.nilai_target"
                                        type="number"
                                        step="any"
                                        class="h-8 text-right"
                                        :disabled="!bisaUbahTarget"
                                    />
                                </td>

                                <template v-for="(m, i) in baris.bulan" :key="m.bulan">
                                    <td :class="cn('border-b px-2 py-1.5', sudahLewat(i) && 'bg-accent/20')">
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
                                            {{ angka(nilaiSel(baris, i, 'target')) }}
                                        </span>
                                    </td>
                                    <td :class="cn('border-b border-r px-2 py-1.5', sudahLewat(i) && 'bg-accent/20')">
                                        <Input
                                            v-if="mode === 'bulan'"
                                            v-model.number="m.realisasi"
                                            type="number"
                                            step="any"
                                            min="0"
                                            class="h-8 text-right"
                                        />
                                        <span v-else class="block text-right tabular-nums">
                                            {{ angka(nilaiSel(baris, i, 'realisasi')) }}
                                        </span>
                                    </td>
                                </template>

                                <td :class="cn('border-b border-r px-3 py-2 text-center font-medium tabular-nums', warnaPersen(persenTahunan(baris)))">
                                    {{ persenTahunan(baris) }}%
                                </td>
                                <td :class="cn('border-b border-r px-3 py-2 text-center font-medium tabular-nums', warnaPersen(persenBulanan(baris)))">
                                    {{ persenBulanan(baris) }}%
                                </td>

                                <td class="border-b border-r px-2 py-1.5">
                                    <Select v-model="baris.satuan" :disabled="!bisaUbahTarget">
                                        <SelectTrigger class="h-8 w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="s in daftarSatuan" :key="s" :value="s">{{ s }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="border-b px-2 py-1.5">
                                    <Input
                                        v-model="baris.tanggal_target"
                                        type="date"
                                        class="h-8"
                                        :disabled="!bisaUbahTarget"
                                    />
                                </td>
                            </tr>

                            <tr v-if="form.baris.length === 0">
                                <td colspan="30" class="py-12">
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
                    Persentase mengacu pada bulan {{ namaBulan[filter.bulan - 1] }}.
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
