<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Download, Medal, Trophy } from '@lucide/vue';

defineProps({
    quiz: { type: Object, required: true },
    peringkat: { type: Array, required: true },
    jumlahPeserta: { type: Number, default: 0 },
    rerata: { type: Number, default: 0 },
    belumMengerjakan: { type: Number, default: null },
    sasaran: { type: String, default: null },
    bisaEkspor: { type: Boolean, default: false },
});

const durasi = (detik) => {
    if (detik === null || detik === undefined) {
        return '—';
    }

    const menit = Math.floor(detik / 60);

    return menit > 0 ? `${menit}m ${detik % 60}d` : `${detik}d`;
};

/* Tiga teratas diberi warna medali; sisanya nomor biasa. */
const medali = { 1: 'text-amber-500', 2: 'text-slate-400', 3: 'text-amber-700' };
</script>

<template>
    <Head :title="`Peringkat — ${quiz.judul}`" />

    <AppLayout>
        <template #header>
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2">
                    <Link href="/pm/quiz">
                        <ArrowLeft class="mr-1.5 size-4" />
                        Daftar Quiz
                    </Link>
                </Button>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ quiz.judul }}</h1>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Papan peringkat — satu baris per peserta, diambil dari percobaan terbaiknya.
                            Skor sama diurutkan dari waktu pengerjaan tercepat.
                            <template v-if="sasaran"> Ditujukan untuk {{ sasaran.toLowerCase() }}.</template>
                        </p>
                    </div>

                    <!--
                      Unduhan berkas memakai <a> biasa, bukan <Link> Inertia:
                      responsnya berkas, bukan halaman, jadi Inertia tidak
                      punya apa pun untuk dirender.
                    -->
                    <Button v-if="bisaEkspor" as-child variant="outline">
                        <a :href="`/pm/quiz/${quiz.id}/peringkat/ekspor`">
                            <Download class="mr-1.5 size-4" />
                            Ekspor Nilai
                        </a>
                    </Button>
                </div>
            </div>
        </template>

        <!-- Ringkasan -->
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <Card>
                <CardContent class="p-4">
                    <p class="text-muted-foreground text-xs">Peserta</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ jumlahPeserta }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-muted-foreground text-xs">Rata-rata skor</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ rerata }}</p>
                </CardContent>
            </Card>
            <Card v-if="belumMengerjakan !== null">
                <CardContent class="p-4">
                    <p class="text-muted-foreground text-xs">Belum mengerjakan</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ belumMengerjakan }}</p>
                </CardContent>
            </Card>
        </div>

        <Card v-if="peringkat.length === 0">
            <CardContent class="text-muted-foreground p-12 text-center text-sm">
                <Trophy class="mx-auto mb-3 size-8 opacity-40" />
                Belum ada yang menyelesaikan quiz ini.
            </CardContent>
        </Card>

        <Card v-else class="overflow-hidden py-0">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-16 pl-4">#</TableHead>
                                <TableHead>Peserta</TableHead>
                                <TableHead class="w-24 text-center">Skor</TableHead>
                                <TableHead class="w-28 text-center">Benar</TableHead>
                                <TableHead class="w-28 text-right">Waktu</TableHead>
                                <TableHead class="w-44 pr-4 text-right">Selesai</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="p in peringkat"
                                :key="p.user_id"
                                :class="p.saya ? 'bg-primary/5' : ''"
                            >
                                <TableCell class="pl-4">
                                    <span v-if="p.posisi <= 3" class="flex items-center gap-1">
                                        <Medal class="size-4" :class="medali[p.posisi]" />
                                        <span class="font-semibold tabular-nums">{{ p.posisi }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground tabular-nums">{{ p.posisi }}</span>
                                </TableCell>

                                <TableCell>
                                    <p class="font-medium">
                                        {{ p.nama }}
                                        <Badge v-if="p.saya" variant="outline" class="ml-1.5">Anda</Badge>
                                    </p>
                                    <p v-if="p.unitKerja" class="text-muted-foreground text-xs">
                                        {{ p.unitKerja }}
                                    </p>
                                </TableCell>

                                <TableCell class="text-center">
                                    <span
                                        class="font-semibold tabular-nums"
                                        :class="p.skor >= quiz.nilai_lulus ? 'text-success' : 'text-destructive'"
                                    >
                                        {{ p.skor }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-muted-foreground text-center text-sm tabular-nums">
                                    {{ p.benar }}/{{ p.jumlah_soal }}
                                </TableCell>

                                <TableCell class="text-muted-foreground text-right text-sm tabular-nums">
                                    {{ durasi(p.durasi_detik) }}
                                </TableCell>

                                <TableCell class="text-muted-foreground pr-4 text-right text-xs">
                                    {{ p.selesai_pada }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
