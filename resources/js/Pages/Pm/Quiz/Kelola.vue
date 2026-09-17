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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    ArrowLeft,
    Check,
    CircleAlert,
    ListChecks,
    Pencil,
    Plus,
    Trash2,
    Trophy,
    X,
} from '@lucide/vue';

const props = defineProps({
    quiz: { type: Object, required: true },
    jumlahPercobaan: { type: Number, default: 0 },
    pilihanSasaran: { type: Object, required: true },
    tipeSasaran: { type: Array, default: () => [] },
    opsi: { type: Object, required: true },
});

/* ================= Pengaturan quiz ================= */

const formQuiz = useForm({
    judul: props.quiz.judul,
    deskripsi: props.quiz.deskripsi ?? '',
    sasaran_tipe: props.quiz.sasaran_tipe,
    sasaran: [...props.quiz.sasaran],
    durasi_menit: props.quiz.durasi_menit,
    jumlah_soal: props.quiz.jumlah_soal,
    nilai_lulus: props.quiz.nilai_lulus,
    maks_percobaan: props.quiz.maks_percobaan,
    acak_soal: props.quiz.acak_soal,
    acak_opsi: props.quiz.acak_opsi,
    tampilkan_pembahasan: props.quiz.tampilkan_pembahasan,
});

const simpanQuiz = () => formQuiz.put(`/pm/quiz/${props.quiz.id}`, { preserveScroll: true });

const ubahStatus = (status) =>
    router.patch(`/pm/quiz/${props.quiz.id}/status`, { status }, { preserveScroll: true });

/* ================= Soal ================= */

const dialogSoal = ref(false);
const soalDiedit = ref(null);

/*
 | Kunci jawaban disimpan sebagai indeks opsi, bukan flag per opsi. Bentuk ini
 | membuat "tepat satu jawaban benar" mustahil dilanggar dari form.
 */
const formSoal = useForm({
    pertanyaan: '',
    pembahasan: '',
    poin: 1,
    opsis: [{ teks: '' }, { teks: '' }],
    kunci: 0,
});

const bukaTambahSoal = () => {
    soalDiedit.value = null;
    formSoal.reset();
    formSoal.clearErrors();
    formSoal.opsis = [{ teks: '' }, { teks: '' }];
    dialogSoal.value = true;
};

const bukaEditSoal = (soal) => {
    soalDiedit.value = soal;
    formSoal.clearErrors();
    formSoal.pertanyaan = soal.pertanyaan;
    formSoal.pembahasan = soal.pembahasan ?? '';
    formSoal.poin = soal.poin;
    formSoal.opsis = soal.opsis.map((o) => ({ teks: o.teks }));
    formSoal.kunci = Math.max(0, soal.opsis.findIndex((o) => o.benar));
    dialogSoal.value = true;
};

const tambahOpsi = () => formSoal.opsis.push({ teks: '' });

const hapusOpsi = (i) => {
    formSoal.opsis.splice(i, 1);

    // Kunci ikut bergeser bila opsi di atasnya dibuang.
    if (formSoal.kunci >= formSoal.opsis.length) {
        formSoal.kunci = formSoal.opsis.length - 1;
    }
};

const simpanSoal = () => {
    const opsi = {
        preserveScroll: true,
        onSuccess: () => {
            dialogSoal.value = false;
            formSoal.reset();
        },
    };

    if (soalDiedit.value) {
        formSoal.put(`/pm/quiz/${props.quiz.id}/soal/${soalDiedit.value.id}`, opsi);
    } else {
        formSoal.post(`/pm/quiz/${props.quiz.id}/soal`, opsi);
    }
};

const soalDihapus = ref(null);
const dialogHapusSoal = ref(false);

const konfirmasiHapusSoal = (soal) => {
    soalDihapus.value = soal;
    dialogHapusSoal.value = true;
};

const hapusSoal = () =>
    router.delete(`/pm/quiz/${props.quiz.id}/soal/${soalDihapus.value.id}`, { preserveScroll: true });

/* ================= Hapus quiz ================= */

const dialogHapusQuiz = ref(false);
const hapusQuiz = () => router.delete(`/pm/quiz/${props.quiz.id}`);

const totalPoin = computed(() => props.quiz.soals.reduce((n, s) => n + s.poin, 0));
</script>

<template>
    <Head :title="`Kelola — ${quiz.judul}`" />

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
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-semibold tracking-tight">{{ quiz.judul }}</h1>
                            <Lencana :nilai="quiz.status" :peta="opsi.status" />
                        </div>
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ quiz.soals.length }} soal di bank ·
                            {{ quiz.soalDipakai }} keluar per percobaan · total {{ totalPoin }} poin ·
                            untuk {{ quiz.labelSasaran.toLowerCase() }} ({{ quiz.jumlahPeserta }} orang)
                            <template v-if="jumlahPercobaan > 0">
                                · sudah dikerjakan {{ jumlahPercobaan }}×
                            </template>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button as-child variant="outline">
                            <Link :href="`/pm/quiz/${quiz.id}/peringkat`">
                                <Trophy class="mr-1.5 size-4" />
                                Peringkat
                            </Link>
                        </Button>
                        <Button v-if="quiz.status !== 'terbit'" :disabled="! quiz.siapTerbit" @click="ubahStatus('terbit')">
                            Terbitkan
                        </Button>
                        <Button v-else variant="outline" @click="ubahStatus('ditutup')">Tutup Quiz</Button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Quiz tanpa soal tidak bisa diterbitkan; katakan sebabnya, bukan hanya mematikan tombolnya. -->
        <Card v-if="! quiz.siapTerbit" class="border-amber-500/40 bg-amber-500/5 mb-4">
            <CardContent class="flex items-start gap-2 p-4 text-sm">
                <CircleAlert class="mt-0.5 size-4 shrink-0 text-amber-600" />
                <span>
                    Quiz ini belum punya soal, jadi belum bisa diterbitkan. Tambahkan minimal satu soal
                    di bawah.
                </span>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- ===== Bank soal ===== -->
            <div class="space-y-3 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium">Bank Soal</h2>
                    <Button size="sm" @click="bukaTambahSoal">
                        <Plus class="mr-1.5 size-4" />
                        Tambah Soal
                    </Button>
                </div>

                <Card v-if="quiz.soals.length === 0">
                    <CardContent class="text-muted-foreground p-10 text-center text-sm">
                        <ListChecks class="mx-auto mb-3 size-8 opacity-40" />
                        Belum ada soal.
                    </CardContent>
                </Card>

                <Card v-for="(soal, i) in quiz.soals" :key="soal.id">
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium">
                                <span class="text-muted-foreground mr-1.5 tabular-nums">{{ i + 1 }}.</span>
                                {{ soal.pertanyaan }}
                            </p>
                            <div class="flex shrink-0 items-center gap-1">
                                <span class="text-muted-foreground text-xs tabular-nums">{{ soal.poin }} poin</span>
                                <Button variant="ghost" size="icon" title="Ubah" @click="bukaEditSoal(soal)">
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Hapus"
                                    class="text-destructive hover:text-destructive"
                                    @click="konfirmasiHapusSoal(soal)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>

                        <ul class="mt-3 space-y-1.5">
                            <li
                                v-for="o in soal.opsis"
                                :key="o.id"
                                class="flex items-start gap-2 text-sm"
                                :class="o.benar ? 'text-success font-medium' : 'text-muted-foreground'"
                            >
                                <Check v-if="o.benar" class="mt-0.5 size-4 shrink-0" />
                                <span v-else class="mt-1 ml-1 size-2 shrink-0 rounded-full border" />
                                {{ o.teks }}
                            </li>
                        </ul>

                        <p v-if="soal.pembahasan" class="text-muted-foreground bg-muted/50 mt-3 rounded-md p-2 text-xs">
                            <span class="font-medium">Pembahasan:</span> {{ soal.pembahasan }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== Pengaturan ===== -->
            <div class="space-y-3">
                <h2 class="font-medium">Pengaturan</h2>
                <Card>
                    <CardContent class="space-y-4 p-4">
                        <div class="space-y-2">
                            <Label for="judul">Judul</Label>
                            <Input id="judul" v-model="formQuiz.judul" />
                            <p v-if="formQuiz.errors.judul" class="text-destructive text-sm">
                                {{ formQuiz.errors.judul }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="deskripsi">Deskripsi</Label>
                            <Textarea id="deskripsi" v-model="formQuiz.deskripsi" rows="2" />
                        </div>

                        <div class="space-y-2">
                            <Label for="durasi">Durasi (menit)</Label>
                            <Input id="durasi" v-model="formQuiz.durasi_menit" type="number" min="1" placeholder="Tanpa timer" />
                            <p v-if="formQuiz.errors.durasi_menit" class="text-destructive text-sm">
                                {{ formQuiz.errors.durasi_menit }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="jumlah-soal">Soal per percobaan</Label>
                            <Input id="jumlah-soal" v-model="formQuiz.jumlah_soal" type="number" min="1" placeholder="Semua soal" />
                            <p class="text-muted-foreground text-xs">
                                Diambil acak dari {{ quiz.soals.length }} soal yang ada.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="lulus">Nilai lulus</Label>
                                <Input id="lulus" v-model="formQuiz.nilai_lulus" type="number" min="0" max="100" />
                            </div>
                            <div class="space-y-2">
                                <Label for="maks">Maks. percobaan</Label>
                                <Input id="maks" v-model="formQuiz.maks_percobaan" type="number" min="1" placeholder="Bebas" />
                            </div>
                        </div>

                        <div class="space-y-3 rounded-lg border p-3">
                            <div class="flex items-center justify-between">
                                <Label for="acak-soal" class="font-normal">Acak soal</Label>
                                <Switch id="acak-soal" v-model="formQuiz.acak_soal" />
                            </div>
                            <div class="flex items-center justify-between">
                                <Label for="acak-opsi" class="font-normal">Acak pilihan</Label>
                                <Switch id="acak-opsi" v-model="formQuiz.acak_opsi" />
                            </div>
                            <div class="flex items-center justify-between">
                                <Label for="bahas" class="font-normal">Tampilkan pembahasan</Label>
                                <Switch id="bahas" v-model="formQuiz.tampilkan_pembahasan" />
                            </div>
                        </div>

                        <PemilihSasaran
                            v-model:tipe="formQuiz.sasaran_tipe"
                            v-model:terpilih="formQuiz.sasaran"
                            :pilihan="pilihanSasaran"
                            :tipe-tersedia="tipeSasaran"
                            :galat="formQuiz.errors.sasaran || formQuiz.errors.sasaran_tipe"
                        />

                        <Button class="w-full" :disabled="formQuiz.processing" @click="simpanQuiz">
                            Simpan Pengaturan
                        </Button>

                        <Button variant="ghost" class="text-destructive hover:text-destructive w-full" @click="dialogHapusQuiz = true">
                            <Trash2 class="mr-1.5 size-4" />
                            Hapus Quiz
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ===== Form soal ===== -->
        <Dialog v-model:open="dialogSoal">
            <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ soalDiedit ? 'Ubah Soal' : 'Soal Baru' }}</DialogTitle>
                    <DialogDescription>
                        Pilihan ganda dengan tepat satu jawaban benar — tandai lewat tombol di kiri opsi.
                    </DialogDescription>
                </DialogHeader>

                <form class="flex min-h-0 flex-1 flex-col gap-4" @submit.prevent="simpanSoal">
                    <div class="gulir-terlihat min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        <div class="space-y-2">
                            <Label for="pertanyaan">Pertanyaan</Label>
                            <Textarea id="pertanyaan" v-model="formSoal.pertanyaan" rows="2" />
                            <p v-if="formSoal.errors.pertanyaan" class="text-destructive text-sm">
                                {{ formSoal.errors.pertanyaan }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Pilihan Jawaban</Label>
                            <p v-if="formSoal.errors.kunci" class="text-destructive text-sm">
                                {{ formSoal.errors.kunci }}
                            </p>
                            <p v-if="formSoal.errors.opsis" class="text-destructive text-sm">
                                {{ formSoal.errors.opsis }}
                            </p>

                            <div v-for="(o, i) in formSoal.opsis" :key="i" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="flex size-8 shrink-0 items-center justify-center rounded-md border transition"
                                    :class="
                                        formSoal.kunci === i
                                            ? 'border-success bg-success text-white'
                                            : 'text-muted-foreground hover:border-success'
                                    "
                                    :title="formSoal.kunci === i ? 'Jawaban benar' : 'Tandai sebagai jawaban benar'"
                                    @click="formSoal.kunci = i"
                                >
                                    <Check class="size-4" />
                                </button>
                                <Input v-model="o.teks" :placeholder="`Pilihan ${i + 1}`" />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="formSoal.opsis.length <= 2"
                                    title="Hapus pilihan"
                                    @click="hapusOpsi(i)"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="formSoal.opsis.length >= 6"
                                @click="tambahOpsi"
                            >
                                <Plus class="mr-1.5 size-4" />
                                Tambah Pilihan
                            </Button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="poin">Poin</Label>
                                <Input id="poin" v-model="formSoal.poin" type="number" min="1" />
                            </div>
                            <div class="space-y-2 sm:col-span-2">
                                <Label for="pembahasan">Pembahasan</Label>
                                <Input id="pembahasan" v-model="formSoal.pembahasan" placeholder="Opsional" />
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="shrink-0">
                        <Button type="button" variant="outline" @click="dialogSoal = false">Batal</Button>
                        <Button type="submit" :disabled="formSoal.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog v-model:open="dialogHapusSoal">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus soal ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Soal beserta pilihan jawabannya akan dihapus permanen.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="hapusSoal">Hapus</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="dialogHapusQuiz">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus quiz ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span class="font-medium">{{ quiz.judul }}</span> akan dihapus permanen beserta
                        seluruh soal
                        <template v-if="jumlahPercobaan > 0">
                            dan {{ jumlahPercobaan }} hasil pengerjaan pesertanya
                        </template>
                        — termasuk papan peringkatnya.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="hapusQuiz">Hapus</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
