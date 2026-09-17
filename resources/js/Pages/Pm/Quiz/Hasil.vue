<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeft, Check, Trophy, X } from '@lucide/vue';

const props = defineProps({
    hasil: { type: Object, required: true },
    rincian: { type: Array, required: true },
});

const durasi = (detik) => {
    if (detik === null || detik === undefined) {
        return '—';
    }

    const menit = Math.floor(detik / 60);

    return menit > 0 ? `${menit} menit ${detik % 60} detik` : `${detik} detik`;
};

/* posisi dari server berbasis 0; null bila peserta belum masuk papan. */
const peringkat = props.hasil.posisi === null || props.hasil.posisi === false ? null : props.hasil.posisi + 1;
</script>

<template>
    <Head :title="`Hasil — ${hasil.judul}`" />

    <AppLayout>
        <template #header>
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2">
                    <Link href="/pm/quiz">
                        <ArrowLeft class="mr-1.5 size-4" />
                        Daftar Quiz
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ hasil.judul }}</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Hasil pengerjaan {{ hasil.nama_peserta }} · selesai {{ hasil.selesai_pada }}
                    </p>
                </div>
            </div>
        </template>

        <!-- ===== Skor ===== -->
        <Card class="mb-4">
            <CardContent class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div
                            class="flex size-24 shrink-0 flex-col items-center justify-center rounded-full border-4"
                            :class="hasil.lulus ? 'border-success text-success' : 'border-destructive text-destructive'"
                        >
                            <span class="text-3xl font-bold tabular-nums">{{ hasil.skor }}</span>
                            <span class="text-xs">dari 100</span>
                        </div>
                        <div>
                            <p class="text-lg font-semibold" :class="hasil.lulus ? 'text-success' : 'text-destructive'">
                                {{ hasil.lulus ? 'Lulus' : 'Belum Lulus' }}
                            </p>
                            <p class="text-muted-foreground text-sm">
                                Nilai lulus quiz ini {{ hasil.nilai_lulus }}.
                            </p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ hasil.benar }} dari {{ hasil.jumlah_soal }} soal benar ·
                                {{ hasil.poin_didapat }}/{{ hasil.poin_maksimal }} poin ·
                                dikerjakan {{ durasi(hasil.durasi_detik) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <p v-if="peringkat" class="text-muted-foreground text-sm">
                            Peringkat
                            <span class="text-foreground font-semibold">{{ peringkat }}</span>
                            dari {{ hasil.jumlahPeserta }} peserta
                        </p>
                        <p v-if="hasil.sisaPercobaan !== null" class="text-muted-foreground text-xs">
                            Sisa kesempatan: {{ hasil.sisaPercobaan }}×
                        </p>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="`/pm/quiz/${hasil.quiz_id}/peringkat`">
                                <Trophy class="mr-1.5 size-4" />
                                Lihat Peringkat
                            </Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- ===== Rincian jawaban ===== -->
        <h2 class="mb-3 font-medium">Rincian Jawaban</h2>

        <div class="space-y-3">
            <Card v-for="r in rincian" :key="r.nomor">
                <CardContent class="p-4">
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
                            :class="r.benar ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'"
                        >
                            <Check v-if="r.benar" class="size-4" />
                            <X v-else class="size-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="font-medium">
                                <span class="text-muted-foreground mr-1.5 tabular-nums">{{ r.nomor }}.</span>
                                {{ r.pertanyaan }}
                            </p>

                            <p class="mt-2 text-sm">
                                <span class="text-muted-foreground">Jawaban Anda:</span>
                                <span :class="r.benar ? 'text-success' : 'text-destructive'">
                                    {{ r.dijawab ?? 'tidak dijawab' }}
                                </span>
                            </p>

                            <!-- Kunci hanya muncul bila pembuat quiz mengizinkan pembahasan. -->
                            <p v-if="! r.benar && r.kunci" class="text-success mt-1 text-sm">
                                <span class="text-muted-foreground">Jawaban benar:</span> {{ r.kunci }}
                            </p>

                            <p v-if="r.pembahasan" class="text-muted-foreground bg-muted/50 mt-2 rounded-md p-2 text-xs">
                                <span class="font-medium">Pembahasan:</span> {{ r.pembahasan }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <p v-if="! hasil.adaPembahasan" class="text-muted-foreground mt-4 text-center text-xs">
            Pembahasan dan kunci jawaban disembunyikan oleh pembuat quiz ini.
        </p>
    </AppLayout>
</template>
