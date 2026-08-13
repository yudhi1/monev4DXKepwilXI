<script setup>
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Trophy } from '@lucide/vue';
import { cn } from '@/lib/utils';

/**
 * Peringkat unit kerja pada satu minggu.
 * Dipakai berulang agar dua minggu bisa dibandingkan berdampingan.
 */
defineProps({
    periode: { type: String, required: true },
    baris: { type: Array, required: true },
    /** Unit kerja yang sedang disorot, bila ada. */
    cabangDipilih: { type: [Number, null], default: null },
});

const emit = defineEmits(['pilih']);

const KELAS_STATUS = {
    on: 'border-success/30 bg-success/10 text-success',
    waspada: 'border-warning/40 bg-warning/10 text-warning-foreground',
    awas: 'border-destructive/30 bg-destructive/10 text-destructive',
};

const LABEL_STATUS = { on: 'On Track', waspada: 'Waspada', awas: 'Awas' };

const KELAS_BILAH = {
    on: 'bg-success',
    waspada: 'bg-warning',
    awas: 'bg-destructive',
};
</script>

<template>
    <Card class="overflow-hidden py-0">
        <div class="bg-muted/60 flex flex-wrap items-center gap-2 border-b px-4 py-2.5">
            <Trophy class="size-4 shrink-0 opacity-70" />
            <h2 class="text-sm font-semibold">Rincian Peringkat — {{ periode }}</h2>
            <span class="text-muted-foreground ml-auto text-xs">Klik baris untuk melihat Lead Measure-nya</span>
        </div>

        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-14 pl-4 text-center">#</TableHead>
                            <TableHead class="min-w-[10rem]">Unit Kerja</TableHead>
                            <TableHead class="w-28 text-center">Lead Diisi</TableHead>
                            <TableHead class="w-44">% Capaian</TableHead>
                            <TableHead class="w-28 text-center">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="(item, i) in baris"
                            :key="item.cabang_id"
                            :class="cn('cursor-pointer', item.cabang_id === cabangDipilih && 'bg-secondary')"
                            @click="emit('pilih', item.cabang_id)"
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
                            <TableCell class="text-sm font-medium whitespace-normal">{{ item.nama }}</TableCell>
                            <TableCell class="text-center text-sm tabular-nums">{{ item.jumlah_lead }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <div class="bg-muted h-2 flex-1 overflow-hidden rounded-full">
                                        <div
                                            :class="cn('h-full rounded-full', KELAS_BILAH[item.status])"
                                            :style="{ width: `${Math.min(item.pct, 100)}%` }"
                                        />
                                    </div>
                                    <span class="w-16 text-right text-sm font-medium tabular-nums">
                                        {{ item.pct }}%
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="outline" :class="KELAS_STATUS[item.status]">
                                    {{ LABEL_STATUS[item.status] }}
                                </Badge>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="baris.length === 0" class="hover:bg-transparent">
                            <TableCell colspan="5" class="py-12">
                                <div class="text-muted-foreground flex flex-col items-center gap-2">
                                    <Building2 class="size-8 opacity-40" />
                                    <p class="text-sm">Belum ada unit kerja pada wilayah ini.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
