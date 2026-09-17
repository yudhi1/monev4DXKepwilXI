<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Lencana from '@/components/pm/Lencana.vue';
import PemilihSasaran from '@/components/pm/PemilihSasaran.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { GraduationCap, Play, Plus, Search, Settings2, Shuffle, Timer, Trophy, Users } from '@lucide/vue';

const props = defineProps({
    quizzes: { type: Array, required: true },
    izin: { type: Object, required: true },
    opsi: { type: Object, required: true },
    pilihanSasaran: { type: Object, default: null },
    tipeSasaran: { type: Array, default: () => [] },
});

/* --- Filter --- */
const cari = ref('');
const status = ref('semua');

/*
 | Disaring di browser, bukan lewat permintaan baru ke server: daftar quiz
 | tidak dipaginasi dan jumlahnya kecil, jadi menyaring di sini terasa
 | seketika dan tidak memuat ulang halaman.
 */
const terlihat = computed(() => {
    const kata = cari.value.trim().toLowerCase();

    return props.quizzes.filter((q) => {
        const cocokStatus = status.value === 'semua' || q.status === status.value;
        const cocokKata =
            ! kata ||
            q.judul.toLowerCase().includes(kata) ||
            (q.deskripsi ?? '').toLowerCase().includes(kata) ||
            (q.pembuat ?? '').toLowerCase().includes(kata);

        return cocokStatus && cocokKata;
    });
});

/* --- Buat quiz --- */
const dialogBuat = ref(false);

/*
 | Tujuan bawaan mengikuti jangkauan pembuatnya: "semua" untuk Kedeputian
 | Wilayah dan admin, kantor cabangnya sendiri untuk PM cabang.
 */
const form = useForm({
    judul: '',
    deskripsi: '',
    sasaran_tipe: props.tipeSasaran[0] ?? 'semua',
    sasaran: [],
    durasi_menit: 15,
    jumlah_soal: null,
    nilai_lulus: 70,
    maks_percobaan: 1,
    acak_soal: true,
    acak_opsi: true,
    tampilkan_pembahasan: true,
});

/*
 | Setelah tersimpan, server mengalihkan ke halaman kelola supaya pembuatnya
 | langsung menyusun soal — quiz tanpa soal tidak bisa diterbitkan.
 */
const simpan = () => form.post('/pm/quiz', { onSuccess: () => (dialogBuat.value = false) });

const mulai = (quiz) => router.post(`/pm/quiz/${quiz.id}/mulai`);

/* Kesempatan mengerjakan masih tersisa? */
const masihBisaMulai = (q) =>
    q.bisaDikerjakan &&
    (q.sedangBerjalan || ! q.maks_percobaan || q.percobaanSaya < q.maks_percobaan);

const labelTombol = (q) => (q.sedangBerjalan ? 'Lanjutkan' : q.percobaanSaya > 0 ? 'Ulangi' : 'Mulai');
</script>

<template>
    <Head title="Quiz" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Quiz</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Uji pemahaman lewat soal pilihan ganda — skor dihitung otomatis dan hasilnya
                        masuk papan peringkat.
                    </p>
                </div>
                <Button v-if="izin.buat" @click="dialogBuat = true">
                    <Plus class="mr-1.5 size-4" />
                    Quiz Baru
                </Button>
            </div>
        </template>

        <Card v-if="quizzes.length === 0">
            <CardContent class="text-muted-foreground p-12 text-center text-sm">
                <GraduationCap class="mx-auto mb-3 size-8 opacity-40" />
                <p>Belum ada quiz yang ditujukan kepada Anda.</p>
                <p v-if="izin.buat" class="mt-1 text-xs">
                    Buat quiz pertama lewat tombol Quiz Baru di atas.
                </p>
            </CardContent>
        </Card>

        <!--
          Tabel, bukan kartu — mengikuti halaman Projects dan Tugas. Satu quiz
          cukup satu baris, sehingga aturan mainnya (soal, durasi, tujuan,
          skor) sejajar kolom per kolom dan mudah dibandingkan sekali lihat.
        -->
        <Card v-else class="overflow-hidden py-0">
            <div class="flex flex-wrap items-center gap-3 border-b px-4 py-3">
                <div class="relative w-full max-w-xs">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari judul atau pembuat..." class="pl-9" />
                </div>

                <Select v-model="status">
                    <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua status</SelectItem>
                        <SelectItem v-for="(meta, kunci) in opsi.status" :key="kunci" :value="kunci">
                            {{ meta.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <span class="text-muted-foreground ml-auto text-sm">{{ terlihat.length }} quiz</span>
            </div>

            <CardContent class="p-0">
                <p v-if="terlihat.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                    Tidak ada quiz yang cocok dengan filter ini.
                </p>

                <div v-else class="gulir-terlihat overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-12 pl-4">#</TableHead>
                                <TableHead>Quiz</TableHead>
                                <TableHead class="w-28">Status</TableHead>
                                <TableHead class="w-44">Ditujukan</TableHead>
                                <TableHead class="w-40">Aturan</TableHead>
                                <TableHead class="w-32 text-center">Skor Saya</TableHead>
                                <TableHead class="w-56 pr-4 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="(q, i) in terlihat" :key="q.id">
                                <TableCell class="text-muted-foreground pl-4 tabular-nums">
                                    {{ i + 1 }}
                                </TableCell>

                                <TableCell>
                                    <!-- Judul menjadi tautan hanya bagi yang boleh mengelolanya. -->
                                    <Link
                                        v-if="q.bisaDikelola"
                                        :href="`/pm/quiz/${q.id}/kelola`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ q.judul }}
                                    </Link>
                                    <p v-else class="font-medium">{{ q.judul }}</p>

                                    <p v-if="q.deskripsi" class="text-muted-foreground line-clamp-1 text-xs">
                                        {{ q.deskripsi }}
                                    </p>
                                    <p v-if="q.pembuat" class="text-muted-foreground mt-0.5 text-xs">
                                        Dibuat {{ q.pembuat }}
                                    </p>
                                </TableCell>

                                <TableCell><Lencana :nilai="q.status" :peta="opsi.status" /></TableCell>

                                <TableCell>
                                    <span class="text-muted-foreground flex items-center gap-1.5 text-sm">
                                        <Users class="size-3.5 shrink-0" />
                                        <span class="truncate">{{ q.sasaran }}</span>
                                    </span>
                                </TableCell>

                                <!-- Aturan main dipadatkan jadi satu kolom agar barisnya tetap pendek. -->
                                <TableCell class="text-muted-foreground text-xs">
                                    <p class="text-foreground text-sm tabular-nums">
                                        {{ q.jumlah_soal }} soal
                                        <span v-if="q.acak_soal" class="text-muted-foreground">
                                            <Shuffle class="mb-0.5 inline size-3" />
                                        </span>
                                    </p>
                                    <p class="flex items-center gap-1">
                                        <Timer class="size-3 shrink-0" />
                                        {{ q.durasi_menit ? `${q.durasi_menit} menit` : 'Tanpa batas waktu' }}
                                    </p>
                                    <p>
                                        Lulus {{ q.nilai_lulus }} ·
                                        {{ q.maks_percobaan ? `maks ${q.maks_percobaan}×` : 'bebas ulang' }}
                                    </p>
                                </TableCell>

                                <TableCell class="text-center">
                                    <template v-if="q.percobaanSaya > 0">
                                        <span
                                            class="text-lg font-semibold tabular-nums"
                                            :class="q.skorTerbaik >= q.nilai_lulus ? 'text-success' : 'text-destructive'"
                                        >
                                            {{ q.skorTerbaik }}
                                        </span>
                                        <p class="text-muted-foreground text-xs">
                                            {{ q.percobaanSaya }}× dikerjakan
                                        </p>
                                    </template>
                                    <span v-else-if="q.sedangBerjalan" class="text-xs font-medium text-amber-600">
                                        Sedang dikerjakan
                                    </span>
                                    <span v-else class="text-muted-foreground text-sm">—</span>
                                </TableCell>

                                <TableCell class="pr-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            v-if="masihBisaMulai(q)"
                                            size="sm"
                                            :variant="q.sedangBerjalan ? 'default' : 'outline'"
                                            @click="mulai(q)"
                                        >
                                            <Play class="mr-1.5 size-4" />
                                            {{ labelTombol(q) }}
                                        </Button>

                                        <Button as-child variant="ghost" size="icon" title="Papan peringkat">
                                            <Link :href="`/pm/quiz/${q.id}/peringkat`">
                                                <Trophy class="size-4" />
                                            </Link>
                                        </Button>

                                        <Button
                                            v-if="q.bisaDikelola"
                                            as-child
                                            variant="ghost"
                                            size="icon"
                                            title="Kelola quiz"
                                        >
                                            <Link :href="`/pm/quiz/${q.id}/kelola`">
                                                <Settings2 class="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- ===== Quiz baru ===== -->
        <Dialog v-model:open="dialogBuat">
            <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-lg">
                <DialogHeader class="shrink-0">
                    <DialogTitle>Quiz Baru</DialogTitle>
                    <DialogDescription>
                        Aturan mainnya diatur di sini; soalnya disusun setelah quiz tersimpan.
                    </DialogDescription>
                </DialogHeader>

                <form class="flex min-h-0 flex-1 flex-col gap-4" @submit.prevent="simpan">
                    <div class="gulir-terlihat min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        <div class="space-y-2">
                            <Label for="judul">Judul</Label>
                            <Input id="judul" v-model="form.judul" placeholder="Sosialisasi Regulasi Iuran 2026" />
                            <p v-if="form.errors.judul" class="text-destructive text-sm">{{ form.errors.judul }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="deskripsi">Deskripsi</Label>
                            <Textarea id="deskripsi" v-model="form.deskripsi" rows="2" />
                        </div>

                        <PemilihSasaran
                            v-if="pilihanSasaran"
                            v-model:tipe="form.sasaran_tipe"
                            v-model:terpilih="form.sasaran"
                            :pilihan="pilihanSasaran"
                            :tipe-tersedia="tipeSasaran"
                            :galat="form.errors.sasaran || form.errors.sasaran_tipe"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="durasi">Durasi (menit)</Label>
                                <Input
                                    id="durasi"
                                    v-model="form.durasi_menit"
                                    type="number"
                                    min="1"
                                    placeholder="Kosongkan bila tanpa timer"
                                />
                                <p v-if="form.errors.durasi_menit" class="text-destructive text-sm">
                                    {{ form.errors.durasi_menit }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="jumlah-soal">Soal per percobaan</Label>
                                <Input id="jumlah-soal" v-model="form.jumlah_soal" type="number" min="1" placeholder="Semua soal" />
                                <p class="text-muted-foreground text-xs">Diambil acak dari bank soal.</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="lulus">Nilai lulus</Label>
                                <Input id="lulus" v-model="form.nilai_lulus" type="number" min="0" max="100" />
                                <p v-if="form.errors.nilai_lulus" class="text-destructive text-sm">
                                    {{ form.errors.nilai_lulus }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="maks">Maksimal percobaan</Label>
                                <Input id="maks" v-model="form.maks_percobaan" type="number" min="1" placeholder="Kosongkan bila bebas" />
                            </div>
                        </div>

                        <div class="space-y-3 rounded-lg border p-3">
                            <div class="flex items-center justify-between">
                                <Label for="acak-soal" class="font-normal">Acak urutan soal</Label>
                                <Switch id="acak-soal" v-model="form.acak_soal" />
                            </div>
                            <div class="flex items-center justify-between">
                                <Label for="acak-opsi" class="font-normal">Acak urutan pilihan jawaban</Label>
                                <Switch id="acak-opsi" v-model="form.acak_opsi" />
                            </div>
                            <div class="flex items-center justify-between">
                                <Label for="bahas" class="font-normal">Tampilkan pembahasan di hasil</Label>
                                <Switch id="bahas" v-model="form.tampilkan_pembahasan" />
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="shrink-0">
                        <Button type="button" variant="outline" @click="dialogBuat = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan & Susun Soal</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
