<script setup>
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Search, Users } from '@lucide/vue';

/*
 | Pemilih tujuan quiz: semua pegawai, per kantor cabang, per bidang, atau
 | orang per orang.
 |
 | Dipakai bersama oleh form quiz baru dan halaman kelola supaya aturan
 | tujuannya tidak ditulis dua kali dengan cara yang perlahan berbeda.
 */
const props = defineProps({
    tipe: { type: String, default: 'semua' },
    terpilih: { type: Array, default: () => [] },
    pilihan: { type: Object, required: true },
    // Jenis tujuan yang boleh dipakai user ini; ditentukan server.
    tipeTersedia: { type: Array, default: () => ['semua', 'cabang', 'unit_kerja', 'pegawai'] },
    galat: { type: String, default: null },
});

const emit = defineEmits(['update:tipe', 'update:terpilih']);

const SEMUA_TIPE = [
    { nilai: 'semua', label: 'Semua Pegawai', ket: 'Terbuka untuk seluruh pengguna Project Management' },
    { nilai: 'cabang', label: 'Kantor Cabang', ket: 'Semua pegawai di cabang yang dipilih' },
    { nilai: 'unit_kerja', label: 'Bidang', ket: 'Hanya bidang tertentu, bisa lintas cabang' },
    { nilai: 'pegawai', label: 'Pegawai Tertentu', ket: 'Ditunjuk orang per orang' },
];

/*
 | PM kantor cabang hanya menjangkau kantornya sendiri, jadi "Semua Pegawai"
 | dan "Bidang" tidak ditawarkan kepadanya — daftarnya datang dari server,
 | yang juga menolak kiriman di luar jangkauan.
 */
const TIPE = computed(() =>
    SEMUA_TIPE.filter((t) => props.tipeTersedia.includes(t.nilai) || t.nilai === props.tipe)
);

/*
 | Quiz lama bisa memakai tujuan yang kini di luar jangkauan pembuatnya —
 | misalnya dibuat saat PM cabang masih boleh menyasar semua pegawai. Tujuan
 | itu tetap ditampilkan supaya terbaca apa adanya, tetapi server menolak
 | penyimpanan sampai diganti.
 */
const tipeDiLuarCakupan = computed(
    () => props.tipe !== null && props.tipeTersedia.length > 0 && ! props.tipeTersedia.includes(props.tipe)
);

const cari = ref('');

/* Daftar yang sedang relevan mengikuti tipe yang dipilih. */
const daftar = computed(() => {
    if (props.tipe === 'semua') {
        return [];
    }

    const isi = props.pilihan[props.tipe] ?? [];
    const kata = cari.value.trim().toLowerCase();

    if (! kata) {
        return isi;
    }

    return isi.filter(
        (d) =>
            d.nama.toLowerCase().includes(kata) ||
            (d.induk ?? '').toLowerCase().includes(kata) ||
            (d.npp ?? '').toLowerCase().includes(kata)
    );
});

/*
 | Berganti tipe mengosongkan pilihan: id cabang dan id bidang hidup di
 | daftar yang berbeda, jadi membawanya menyeberang akan menyasar entitas
 | yang sama sekali lain.
 */
const gantiTipe = (nilai) => {
    if (nilai === props.tipe) {
        return;
    }

    emit('update:tipe', nilai);
    emit('update:terpilih', []);
    cari.value = '';
};

const alihkan = (id) => {
    const kini = [...props.terpilih];
    const i = kini.indexOf(id);

    if (i === -1) {
        kini.push(id);
    } else {
        kini.splice(i, 1);
    }

    emit('update:terpilih', kini);
};

const semuaTampil = () => emit('update:terpilih', [...new Set([...props.terpilih, ...daftar.value.map((d) => d.id)])]);
const kosongkan = () => emit('update:terpilih', []);

watch(() => props.tipe, () => (cari.value = ''));
</script>

<template>
    <div class="space-y-3">
        <Label>Ditujukan Kepada</Label>

        <!-- Pilihan jenis tujuan -->
        <div class="grid gap-2 sm:grid-cols-2">
            <button
                v-for="t in TIPE"
                :key="t.nilai"
                type="button"
                class="rounded-lg border p-3 text-left transition"
                :class="tipe === t.nilai ? 'border-primary bg-primary/5' : 'hover:border-primary/40'"
                @click="gantiTipe(t.nilai)"
            >
                <p class="text-sm font-medium">{{ t.label }}</p>
                <p class="text-muted-foreground mt-0.5 text-xs">{{ t.ket }}</p>
            </button>
        </div>

        <p v-if="tipeDiLuarCakupan" class="text-destructive text-sm">
            Tujuan quiz ini di luar jangkauan akun Anda. Pilih tujuan lain sebelum menyimpan.
        </p>

        <p v-if="galat" class="text-destructive text-sm">{{ galat }}</p>

        <p v-if="tipe === 'semua'" class="text-muted-foreground flex items-center gap-2 text-xs">
            <Users class="size-3.5" />
            Quiz ini akan tampil bagi semua pengguna modul Project Management.
        </p>

        <!-- Daftar tujuan -->
        <div v-else class="space-y-2">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="cari" placeholder="Cari..." class="pl-9" />
                </div>
                <button type="button" class="text-primary shrink-0 text-xs hover:underline" @click="semuaTampil">
                    Pilih semua
                </button>
                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground shrink-0 text-xs hover:underline"
                    @click="kosongkan"
                >
                    Kosongkan
                </button>
            </div>

            <div class="gulir-terlihat max-h-56 space-y-1 overflow-y-auto rounded-lg border p-2">
                <label
                    v-for="d in daftar"
                    :key="d.id"
                    class="hover:bg-muted/50 flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5"
                >
                    <Checkbox
                        :model-value="terpilih.includes(d.id)"
                        @update:model-value="alihkan(d.id)"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm">{{ d.nama }}</span>
                        <span v-if="d.induk" class="text-muted-foreground block text-xs">
                            <template v-if="d.npp">{{ d.npp }} · </template>{{ d.induk }}
                        </span>
                    </span>
                </label>

                <p v-if="daftar.length === 0" class="text-muted-foreground p-3 text-center text-xs">
                    Tidak ada yang cocok dengan pencarian.
                </p>
            </div>

            <p class="text-muted-foreground text-xs">
                {{ terpilih.length }} dipilih.
                <template v-if="terpilih.length === 0">
                    Quiz belum akan sampai ke siapa pun sebelum ada yang dipilih.
                </template>
            </p>
        </div>
    </div>
</template>
