<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChartNoAxesCombined, FolderKanban, LogOut, ArrowRight } from '@lucide/vue';

defineProps({
    modules: { type: Array, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

/* Nama ikon di config/modul.php dipetakan ke komponen Lucide di sini. */
const IKON = { ChartNoAxesCombined, FolderKanban };

const GAYA = {
    primary: 'from-primary to-primary/60',
    success: 'from-success to-success/60',
};

const logout = () => router.post('/logout');

const tahun = new Date().getFullYear();
</script>

<template>
    <Head title="Pilih Aplikasi" />

    <div class="bg-muted/40 flex min-h-screen flex-col items-center justify-center p-4">
        <div class="w-full max-w-3xl">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Halo, {{ user?.name }}
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Pilih aplikasi yang ingin Anda buka.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Link v-for="m in modules" :key="m.kunci" :href="m.beranda" class="group">
                    <Card class="h-full transition-shadow hover:shadow-md">
                        <CardContent class="flex h-full flex-col gap-3 p-6">
                            <span
                                :class="[
                                    'flex size-12 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-sm',
                                    GAYA[m.warna] ?? GAYA.primary,
                                ]"
                            >
                                <component :is="IKON[m.ikon] ?? ChartNoAxesCombined" class="size-6" />
                            </span>

                            <div class="flex-1">
                                <h2 class="text-base font-semibold tracking-tight">{{ m.nama }}</h2>
                                <p class="text-muted-foreground mt-1 text-sm">{{ m.deskripsi }}</p>
                            </div>

                            <span class="text-primary flex items-center gap-1 text-sm font-medium">
                                Buka
                                <ArrowRight class="size-4 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div class="mt-8 flex items-center justify-center">
                <Button variant="ghost" size="sm" class="text-muted-foreground" @click="logout">
                    <LogOut class="size-4" />
                    Keluar
                </Button>
            </div>

            <p class="text-muted-foreground mt-6 text-center text-xs">
                Kedeputian Wilayah XI &middot; {{ tahun }}
            </p>
        </div>
    </div>
</template>
