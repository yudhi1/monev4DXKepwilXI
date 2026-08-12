<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';
import { ChartNoAxesCombined, TriangleAlert } from '@lucide/vue';

const form = useForm({
    name: '',
    password: '',
    remember: false,
});

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
                        <div
                            v-if="form.errors.name"
                            class="border-destructive/30 bg-destructive/10 text-destructive flex items-start gap-2 rounded-lg border p-3 text-sm"
                        >
                            <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                            <span>{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-2">
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
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
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
