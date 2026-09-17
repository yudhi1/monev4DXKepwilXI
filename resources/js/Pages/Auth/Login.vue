<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputPassword from '@/components/InputPassword.vue';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';
import { ChartNoAxesCombined, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';

/*
 | Dua jenis akun masuk lewat pengenal yang berbeda: akun unit kerja (Monev
 | 4DX) dengan nama, akun pegawai (Project Management) dengan NPP. Modenya
 | dikirim ke server supaya kolom pengenalnya tidak ditebak dari isian.
 */
const MODE = [
    { nilai: 'unit_kerja', label: 'Login 4DX' },
    { nilai: 'pegawai', label: 'Login Project Management' },
];

const form = useForm({
    mode: 'unit_kerja',
    name: '',
    npp: '',
    password: '',
    remember: false,
});

const pegawai = computed(() => form.mode === 'pegawai');

/* Pesan kredensial salah dikembalikan pada field pengenal yang dipakai. */
const galat = computed(() => (pegawai.value ? form.errors.npp : form.errors.name));

const gantiMode = (nilai) => {
    form.mode = nilai;
    form.clearErrors();
    form.reset('name', 'npp', 'password');
};

/*
 | Password selalu dikosongkan setelah percobaan gagal supaya tidak
 | tertinggal di layar bersama pesan kesalahan.
 */
const masuk = () => form.post('/login', { onFinish: () => form.reset('password') });

const tahun = new Date().getFullYear();
</script>

<template>
    <Head title="Masuk" />

    <div class="bg-muted/40 flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Identitas aplikasi -->
            <div class="mb-6 flex flex-col items-center text-center">
                <span
                    class="from-primary to-success mb-3 flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-sm"
                >
                    <ChartNoAxesCombined class="size-7" />
                </span>
                <h1 class="text-xl font-semibold tracking-tight">
                    Monev <span class="text-muted-foreground font-normal">4DX</span>
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Monitoring Kinerja — Kedeputian Wilayah XI
                </p>
            </div>

            <Card>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="masuk">
                        <!--
                          Kesalahan kredensial dikembalikan server pada field `name`,
                          jadi ditampilkan sebagai peringatan di atas form.
                        -->
                        <!-- Pilihan jenis akun -->
                        <div class="bg-muted text-muted-foreground grid grid-cols-2 gap-1 rounded-lg p-1">
                            <button
                                v-for="m in MODE"
                                :key="m.nilai"
                                type="button"
                                class="rounded-md px-2 py-1.5 text-center text-xs font-medium transition"
                                :class="
                                    form.mode === m.nilai
                                        ? 'bg-success text-white shadow-sm'
                                        : 'hover:text-foreground'
                                "
                                @click="gantiMode(m.nilai)"
                            >
                                {{ m.label }}
                            </button>
                        </div>

                        <div
                            v-if="galat"
                            class="border-destructive/30 bg-destructive/10 text-destructive flex items-start gap-2 rounded-lg border p-3 text-sm"
                        >
                            <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                            <span>{{ galat }}</span>
                        </div>

                        <div v-if="pegawai" class="space-y-2">
                            <Label for="npp">NPP</Label>
                            <Input
                                id="npp"
                                v-model="form.npp"
                                autocomplete="username"
                                inputmode="numeric"
                                placeholder="Nomor pokok pegawai"
                                autofocus
                                required
                            />
                        </div>

                        <div v-else class="space-y-2">
                            <Label for="name">Nama</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                autocomplete="username"
                                autofocus
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <InputPassword
                                id="password"
                                v-model="form.password"
                                autocomplete="current-password"
                                required
                            />
                            <p v-if="form.errors.password" class="text-destructive text-sm">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="remember" v-model="form.remember" />
                            <Label for="remember" class="font-normal">Ingat saya</Label>
                        </div>

                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ form.processing ? 'Memproses...' : 'Masuk' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <p class="text-muted-foreground mt-6 text-center text-xs">
                Created by PIKEU — KEPWIL XI &copy; {{ tahun }}
            </p>
        </div>
    </div>
</template>
