<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BilahProgress from '@/components/pm/BilahProgress.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Check, Timer } from '@lucide/vue';

const props = defineProps({
    percobaan: { type: Object, required: true },
    soals: { type: Array, required: true },
});

/*
 | Jawaban disimpan ke server begitu dipilih, lalu dicerminkan di sini supaya
 | tampilan tidak perlu menunggu balasan. Kalau hanya mengandalkan prop dari
 | server, setiap klik akan terasa tersendat.
 */
const jawaban = ref(Object.fromEntries(props.soals.map((s) => [s.id, s.dijawab])));

const terjawab = computed(() => Object.values(jawaban.value).filter((v) => v !== null && v !== undefined).length);

const persen = computed(() =>
    props.soals.length === 0 ? 0 : Math.round((terjawab.value / props.soals.length) * 100)
);

const pilih = (soalId, opsiId) => {
    jawaban.value[soalId] = opsiId;

    router.patch(
        `/pm/quiz/percobaan/${props.percobaan.id}/jawab`,
        { soal_id: soalId, opsi_id: opsiId },
        { preserveScroll: true, preserveState: true, only: [] }
    );
};

/* ================= Timer ================= */

/*
 | Angka awal datang dari server; browser hanya menghitung mundur dari situ.
 | Jam peserta tidak dipercaya — server tetap menolak jawaban yang masuk
 | setelah batas waktu, berapa pun yang tertulis di layar ini.
 */
const sisa = ref(props.percobaan.sisa_detik);
let jam = null;

const habis = computed(() => sisa.value !== null && sisa.value <= 0);

const waktu = computed(() => {
    if (sisa.value === null) {
        return null;
    }

    const menit = Math.floor(Math.max(0, sisa.value) / 60);
    const detik = Math.max(0, sisa.value) % 60;

    return `${String(menit).padStart(2, '0')}:${String(detik).padStart(2, '0')}`;
});

/* Lima menit terakhir ditandai merah agar peserta sempat menutup pekerjaannya. */
const mendesak = computed(() => sisa.value !== null && sisa.value <= 300);

const selesaikan = () => router.post(`/pm/quiz/percobaan/${props.percobaan.id}/selesai`);

onMounted(() => {
    if (sisa.value === null) {
        return;
    }

    jam = setInterval(() => {
        sisa.value -= 1;

        // Habis waktu: server yang menutup dan menghitung skornya.
        if (sisa.value <= 0) {
            clearInterval(jam);
            selesaikan();
        }
    }, 1000);
});

onBeforeUnmount(() => clearInterval(jam));

const dialogSelesai = ref(false);

/*
 | Soal yang masih kosong, dipakai dua kali: mengunci tombol Selesaikan, dan
 | menjadi pintasan agar peserta tidak perlu menggulir mencari yang terlewat.
 */
const soalBelumDijawab = computed(() =>
    props.soals.filter((s) => jawaban.value[s.id] === null || jawaban.value[s.id] === undefined)
);

const belumTerjawab = computed(() => soalBelumDijawab.value.length);

const lompatKe = (soalId) =>
    document.getElementById(`soal-${soalId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>

<template>
    <Head :title="percobaan.judul" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ percobaan.judul }}</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ percobaan.jumlah_soal }} soal · jawaban tersimpan otomatis setiap kali Anda memilih.
                    </p>
                </div>

                <div
                    v-if="waktu"
                    class="flex items-center gap-2 rounded-lg border px-4 py-2 tabular-nums"
                    :class="mendesak ? 'border-destructive/40 bg-destructive/10 text-destructive' : ''"
                >
                    <Timer class="size-4" />
                    <span class="text-xl font-semibold">{{ waktu }}</span>
                </div>
            </div>
        </template>

        <!-- Progress pengerjaan -->
        <Card class="mb-4">
            <CardContent class="p-4">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium">Progress</span>
                    <span class="text-muted-foreground tabular-nums">
                        {{ terjawab }} dari {{ soals.length }} soal terjawab
                    </span>
                </div>
                <BilahProgress :nilai="persen" />
            </CardContent>
        </Card>

        <div class="space-y-4">
            <Card v-for="soal in soals" :id="`soal-${soal.id}`" :key="soal.id">
                <CardContent class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium">
                            <span class="text-muted-foreground mr-1.5 tabular-nums">{{ soal.nomor }}.</span>
                            {{ soal.pertanyaan }}
                        </p>
                        <span class="text-muted-foreground shrink-0 text-xs tabular-nums">
                            {{ soal.poin }} poin
                        </span>
                    </div>

                    <div class="mt-4 space-y-2">
                        <button
                            v-for="o in soal.opsis"
                            :key="o.id"
                            type="button"
                            class="flex w-full items-start gap-3 rounded-lg border p-3 text-left text-sm transition"
                            :class="
                                jawaban[soal.id] === o.id
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:border-primary/40 hover:bg-muted/50'
                            "
                            :disabled="habis"
                            @click="pilih(soal.id, o.id)"
                        >
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border"
                                :class="jawaban[soal.id] === o.id ? 'border-primary bg-primary text-white' : ''"
                            >
                                <Check v-if="jawaban[soal.id] === o.id" class="size-3" />
                            </span>
                            {{ o.teks }}
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!--
          Selesai baru terbuka setelah semua soal terjawab. Dulu tombolnya
          selalu aktif dan konfirmasinya hanya memperingatkan, sehingga satu
          klik keliru menutup lembar dan soal yang kosong langsung dihitung
          salah — padahal waktunya masih ada.
        -->
        <Card class="mt-6">
            <CardContent class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p v-if="belumTerjawab > 0" class="text-sm font-medium">
                            Masih ada {{ belumTerjawab }} soal yang belum dijawab.
                        </p>
                        <p v-else class="text-success text-sm font-medium">
                            Semua soal sudah terjawab — quiz siap diselesaikan.
                        </p>
                        <p v-if="belumTerjawab > 0" class="text-muted-foreground mt-0.5 text-xs">
                            Lengkapi dulu untuk bisa menyelesaikan quiz. Klik nomornya untuk melompat ke soal.
                        </p>
                    </div>

                    <Button size="lg" :disabled="belumTerjawab > 0" @click="dialogSelesai = true">
                        Selesaikan Quiz
                    </Button>
                </div>

                <!-- Pintasan ke soal yang terlewat -->
                <div v-if="belumTerjawab > 0" class="mt-3 flex flex-wrap gap-1.5 border-t pt-3">
                    <button
                        v-for="s in soalBelumDijawab"
                        :key="s.id"
                        type="button"
                        class="border-destructive/40 text-destructive hover:bg-destructive/10 size-8 rounded-md border text-sm font-medium tabular-nums transition"
                        :title="`Ke soal ${s.nomor}`"
                        @click="lompatKe(s.id)"
                    >
                        {{ s.nomor }}
                    </button>
                </div>
            </CardContent>
        </Card>

        <AlertDialog v-model:open="dialogSelesai">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Selesaikan quiz sekarang?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Seluruh {{ soals.length }} soal sudah terjawab. Jawaban tidak bisa diubah
                        setelah ini, dan skor Anda langsung masuk papan peringkat.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Periksa Lagi</AlertDialogCancel>
                    <AlertDialogAction @click="selesaikan">Selesaikan</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
