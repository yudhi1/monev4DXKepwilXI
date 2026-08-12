<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ClipboardCheck, Pencil, Save, X } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    wigs: { type: Array, required: true },
    cabangs: { type: Array, required: true },
    leads: { type: Array, required: true },
    namaBulan: { type: Array, required: true },
    filter: { type: Object, required: true },
    terkunciCabang: { type: Boolean, default: false },
});

/* ---------------- Konteks ---------------- */
const wigId = ref(props.filter.wig_id ? String(props.filter.wig_id) : '');
const cabangId = ref(props.filter.cabang_id ? String(props.filter.cabang_id) : '');
const tahun = ref(props.filter.tahun);
const bulan = ref(String(props.filter.bulan));

watch([wigId, cabangId, tahun, bulan], () => {
    router.get(
        '/realisasi',
        {
            wig_id: wigId.value || undefined,
            cabang_id: cabangId.value || undefined,
            tahun: tahun.value,
            bulan: bulan.value,
        },
        { preserveState: false, replace: true }
    );
});

const siap = computed(() => !! props.filter.wig_id && !! props.filter.cabang_id);
const wigTerpilih = computed(() => props.wigs.find((w) => String(w.id) === wigId.value));

/* ---------------- Edit per Lead ---------------- */
/*
 | Tiap Lead disimpan sendiri (seperti versi Livewire), tapi pengetikan
 | seluruh 4 minggu terjadi lokal dan dikirim dalam satu permintaan.
 */
const draf = reactive({});
const sedangDiedit = ref(null);

const form = useForm({
    lead_measure_id: null,
    cabang_id: props.filter.cabang_id,
    tahun: props.filter.tahun,
    bulan: props.filter.bulan,
    minggu: [],
});

const mulaiEdit = (lead) => {
    sedangDiedit.value = lead.id;
    draf[lead.id] = lead.minggu.map((m) => ({ ...m }));
};

const batalEdit = () => {
    delete draf[sedangDiedit.value];
    sedangDiedit.value = null;
    form.clearErrors();
};

const simpan = (lead) => {
    form.lead_measure_id = lead.id;
    form.cabang_id = props.filter.cabang_id;
    form.tahun = props.filter.tahun;
    form.bulan = props.filter.bulan;
    form.minggu = draf[lead.id];

    form.post('/realisasi', {
        preserveScroll: true,
        onSuccess: () => {
            delete draf[lead.id];
            sedangDiedit.value = null;
        },
    });
};

/* Persentase dihitung lokal supaya angkanya bergerak saat mengetik. */
const persen = (m) => (Number(m.target) > 0 ? Math.round((Number(m.realisasi) / Number(m.target)) * 10000) / 100 : 0);

const warnaPersen = (nilai) =>
    nilai >= 100 ? 'text-success' : nilai >= 70 ? 'text-warning-foreground' : nilai > 0 ? 'text-destructive' : 'text-muted-foreground';

const barisLead = (lead) => draf[lead.id] ?? lead.minggu;

const rataLead = (lead) => {
    const baris = barisLead(lead);
    const terisi = baris.filter((m) => Number(m.target) > 0);

    if (terisi.length === 0) {
        return 0;
    }

    return Math.round((terisi.reduce((jml, m) => jml + persen(m), 0) / terisi.length) * 100) / 100;
};

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Input Realisasi Mingguan" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Input Realisasi Mingguan</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Isi target dan realisasi tiap Lead Measure untuk empat minggu dalam satu bulan.
                </p>
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
                                {{ w.kode_wig }} — {{ w.nama_wig }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="min-w-56 flex-1 space-y-2">
                    <Label>Kantor Cabang</Label>
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

        <div v-if="wigTerpilih && siap" class="bg-accent/40 mb-4 rounded-lg border p-3 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="secondary" class="font-mono">{{ wigTerpilih.kode_wig }}</Badge>
                <Badge v-if="wigTerpilih.bidang" variant="outline" :class="kelasBidang(wigTerpilih.bidang)">{{ wigTerpilih.bidang }}</Badge>
            </div>
            <p class="text-muted-foreground mt-1.5 whitespace-pre-wrap">{{ wigTerpilih.nama_wig }}</p>
        </div>

        <!-- Daftar Lead -->
        <template v-if="siap">
            <Card v-for="lead in leads" :key="lead.id" class="mb-4 overflow-hidden py-0">
                <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="font-mono text-xs">{{ lead.kode_lead }}</Badge>
                            <Badge v-if="lead.lag" variant="outline" class="font-mono text-xs">{{ lead.lag }}</Badge>
                        </div>
                        <p class="mt-1 text-sm font-medium whitespace-pre-wrap">{{ lead.nama_lead }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-muted-foreground text-xs">Rata-rata bulan ini</p>
                        <p :class="cn('text-lg font-semibold tabular-nums', warnaPersen(rataLead(lead)))">
                            {{ rataLead(lead) }}%
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <template v-if="sedangDiedit === lead.id">
                            <Button variant="outline" size="sm" @click="batalEdit">
                                <X class="mr-1.5 size-4" />
                                Batal
                            </Button>
                            <Button size="sm" :disabled="form.processing" @click="simpan(lead)">
                                <Save class="mr-1.5 size-4" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                            </Button>
                        </template>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            :disabled="sedangDiedit !== null"
                            @click="mulaiEdit(lead)"
                        >
                            <Pencil class="mr-1.5 size-4" />
                            Edit
                        </Button>
                    </div>
                </div>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="w-28 pl-4">Minggu</TableHead>
                                    <TableHead class="w-44 text-right">Target</TableHead>
                                    <TableHead class="w-44 text-right">Realisasi</TableHead>
                                    <TableHead class="w-28 text-center">% Capaian</TableHead>
                                    <TableHead class="pr-4">Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="m in barisLead(lead)" :key="m.minggu_ke">
                                    <TableCell class="pl-4 text-sm font-medium">Minggu {{ m.minggu_ke }}</TableCell>

                                    <TableCell>
                                        <Input
                                            v-if="sedangDiedit === lead.id"
                                            v-model.number="m.target"
                                            type="number"
                                            step="any"
                                            class="text-right"
                                        />
                                        <p v-else class="text-right text-sm tabular-nums">{{ angka(m.target) }}</p>
                                    </TableCell>

                                    <TableCell>
                                        <Input
                                            v-if="sedangDiedit === lead.id"
                                            v-model.number="m.realisasi"
                                            type="number"
                                            step="any"
                                            class="text-right"
                                        />
                                        <p v-else class="text-right text-sm tabular-nums">{{ angka(m.realisasi) }}</p>
                                    </TableCell>

                                    <TableCell :class="cn('text-center text-sm font-medium tabular-nums', warnaPersen(persen(m)))">
                                        {{ persen(m) }}%
                                    </TableCell>

                                    <TableCell class="pr-4">
                                        <Input
                                            v-if="sedangDiedit === lead.id"
                                            v-model="m.keterangan"
                                            placeholder="Opsional"
                                        />
                                        <p v-else class="text-muted-foreground text-sm whitespace-pre-wrap">
                                            {{ m.keterangan || '—' }}
                                        </p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="leads.length === 0">
                <CardContent class="py-16">
                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                        <ClipboardCheck class="size-8 opacity-40" />
                        <p class="text-sm">
                            Belum ada Lead Measure aktif untuk kombinasi ini.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </template>

        <Card v-else>
            <CardContent class="py-16">
                <div class="text-muted-foreground flex flex-col items-center gap-2">
                    <ClipboardCheck class="size-8 opacity-40" />
                    <p class="text-sm">Pilih WIG dan Kantor Cabang dulu untuk menampilkan grid input.</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
