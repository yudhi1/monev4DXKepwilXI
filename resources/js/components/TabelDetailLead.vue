<script setup>
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Minus, TrendingDown, TrendingUp } from '@lucide/vue';
import { cn } from '@/lib/utils';

/**
 * Rincian Lead Measure satu kantor cabang pada satu minggu.
 * Dipakai berulang agar dua minggu bisa dibandingkan berdampingan.
 */
defineProps({
    judul: { type: String, required: true },
    periode: { type: String, required: true },
    baris: { type: Array, required: true },
});

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
    <Card class="overflow-hidden py-0">
        <div class="bg-primary text-primary-foreground flex flex-wrap items-center gap-2 px-4 py-2.5">
            <h2 class="text-sm font-semibold">{{ judul }}</h2>
            <span class="text-primary-foreground/80 text-xs">
                Periode: {{ periode }} · diurutkan dari % capaian terendah
            </span>
        </div>

        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-12 pl-4 text-center">No</TableHead>
                            <TableHead class="w-44">WIG</TableHead>
                            <TableHead class="min-w-[18rem]">Lead Measure</TableHead>
                            <TableHead class="w-28 text-right">Target</TableHead>
                            <TableHead class="w-36 text-right">Realisasi Minggu Ini</TableHead>
                            <TableHead class="w-28 text-center">% Capaian</TableHead>
                            <TableHead class="w-28 text-center">Status</TableHead>
                            <TableHead class="w-28 text-center">Tren</TableHead>
                            <TableHead class="min-w-[10rem] pr-4">Keterangan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(lead, i) in baris" :key="lead.id">
                            <TableCell class="text-muted-foreground pl-4 text-center tabular-nums">
                                {{ i + 1 }}
                            </TableCell>
                            <TableCell>
                                <Badge v-if="lead.wig" variant="secondary" class="font-mono text-xs">
                                    {{ lead.wig }}
                                </Badge>
                                <p v-if="lead.wig_nama" class="text-muted-foreground mt-1 line-clamp-2 text-xs">
                                    {{ lead.wig_nama }}
                                </p>
                            </TableCell>
                            <TableCell class="text-sm">
                                {{ lead.nama_lead }}
                                <span class="text-muted-foreground block text-xs">{{ lead.kode_lead }}</span>
                            </TableCell>
                            <TableCell class="text-right text-sm tabular-nums">{{ angka(lead.target) }}</TableCell>
                            <TableCell class="text-right text-sm tabular-nums">{{ angka(lead.realisasi) }}</TableCell>
                            <TableCell class="text-center">
                                <Badge variant="outline" :class="KELAS_STATUS[lead.status]">{{ lead.pct }}%</Badge>
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

                        <TableRow v-if="baris.length === 0" class="hover:bg-transparent">
                            <TableCell colspan="9" class="py-10">
                                <div class="text-muted-foreground flex flex-col items-center gap-2">
                                    <Building2 class="size-8 opacity-40" />
                                    <p class="text-sm">Tidak ada Lead Measure untuk konteks ini.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
