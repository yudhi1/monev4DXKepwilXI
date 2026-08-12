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
import { Flag, Save } from '@lucide/vue';
import { kelasBidang } from '@/lib/bidang';

const props = defineProps({
    wigs: { type: Array, required: true },
    wig: { type: [Object, null], default: null },
    baris: { type: Array, required: true },
});

const SATUAN = ['Rp', '%', 'orang', 'unit', 'transaksi', 'kasus'];

const wigId = ref(props.wig ? String(props.wig.id) : '');

watch(wigId, (nilai) => {
    router.get('/wig-targets', { wig_id: nilai }, { preserveState: false, replace: true });
});

/*
 | Seluruh baris cabang diisi di satu tabel lalu dikirim sekali.
 | Versi Livewire menyimpan tiap perubahan lewat wire:model — di sini
 | pengetikan sepenuhnya lokal, tidak ada permintaan ke server.
 */
const form = useForm({
    wig_id: props.wig?.id,
    baris: props.baris.map((b) => ({ ...b })),
});

watch(
    () => props.baris,
    (baru) => {
        form.wig_id = props.wig?.id;
        form.baris = baru.map((b) => ({ ...b }));
    }
);

const simpan = () => form.post('/wig-targets', { preserveScroll: true });

const rentang = (b) => Number(b.nilai_target ?? 0) - Number(b.nilai_awal ?? 0);

const jumlahTerisi = computed(() => form.baris.filter((b) => Number(b.nilai_target) > 0).length);
</script>

<template>
    <Head title="Input Target WIG" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Input Target WIG</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Tetapkan nilai awal dan target tiap kantor cabang untuk satu WIG.
                    </p>
                </div>
                <Button :disabled="!wig || form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Target' }}
                </Button>
            </div>
        </template>

        <Card class="mb-4">
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label>WIG</Label>
                    <Select v-model="wigId">
                        <SelectTrigger class="w-full max-w-2xl"><SelectValue placeholder="Pilih WIG" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="w in wigs" :key="w.id" :value="String(w.id)">
                                [{{ w.tahun }}] {{ w.kode_wig }} — {{ w.nama_wig }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="wig" class="bg-accent/40 flex flex-wrap items-center gap-2 rounded-lg border p-3 text-sm">
                    <Badge variant="secondary" class="font-mono">{{ wig.kode_wig }}</Badge>
                    <Badge v-if="wig.bidang" variant="outline" :class="kelasBidang(wig.bidang)">{{ wig.bidang }}</Badge>
                    <Badge variant="outline">{{ wig.tahun }}</Badge>
                    <span class="text-muted-foreground">{{ wig.nama_wig }}</span>
                </div>
            </CardContent>
        </Card>

        <Card v-if="wig" class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <h2 class="text-sm font-medium">Target per Cabang</h2>
                <span class="text-muted-foreground ml-auto text-sm">
                    {{ jumlahTerisi }} dari {{ form.baris.length }} cabang sudah bertarget
                </span>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-14 pl-4">#</TableHead>
                                <TableHead class="min-w-[14rem]">Cabang</TableHead>
                                <TableHead class="w-44 text-right">Nilai Awal</TableHead>
                                <TableHead class="w-44 text-right">Nilai Target</TableHead>
                                <TableHead class="w-40 text-right">Rentang</TableHead>
                                <TableHead class="w-36">Satuan</TableHead>
                                <TableHead class="w-44 pr-4">Tanggal Target</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(b, i) in form.baris" :key="b.cabang_id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">{{ i + 1 }}</TableCell>
                                <TableCell class="text-sm font-medium">{{ b.cabang_nama }}</TableCell>
                                <TableCell>
                                    <Input v-model.number="b.nilai_awal" type="number" step="any" class="text-right" />
                                </TableCell>
                                <TableCell>
                                    <Input v-model.number="b.nilai_target" type="number" step="any" class="text-right" />
                                </TableCell>
                                <TableCell
                                    class="text-right text-sm tabular-nums"
                                    :class="rentang(b) > 0 ? 'text-success' : 'text-muted-foreground'"
                                >
                                    {{ rentang(b).toLocaleString('id-ID') }}
                                </TableCell>
                                <TableCell>
                                    <Select v-model="b.satuan">
                                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="s in SATUAN" :key="s" :value="s">{{ s }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell class="pr-4">
                                    <Input v-model="b.tanggal_target" type="date" />
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="form.baris.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="7" class="py-12">
                                    <div class="text-muted-foreground flex flex-col items-center gap-2">
                                        <Flag class="size-8 opacity-40" />
                                        <p class="text-sm">Belum ada kantor cabang.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <div class="flex justify-end border-t px-4 py-3">
                <Button :disabled="form.processing" @click="simpan">
                    <Save class="mr-1.5 size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Target' }}
                </Button>
            </div>
        </Card>

        <Card v-else>
            <CardContent class="py-16">
                <div class="text-muted-foreground flex flex-col items-center gap-2">
                    <Flag class="size-8 opacity-40" />
                    <p class="text-sm">Pilih WIG terlebih dahulu.</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
