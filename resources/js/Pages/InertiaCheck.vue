<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2 } from '@lucide/vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Inertia Check" />

    <div class="mx-auto flex min-h-full max-w-3xl flex-col gap-6 p-8">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Inertia + Vue + shadcn-vue aktif</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Halaman uji Fase 1. Tidak tertaut di navigasi mana pun.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CheckCircle2 class="size-5" />
                    Data yang di-share dari server
                </CardTitle>
                <CardDescription>Berasal dari HandleInertiaRequests::share()</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-if="user" class="space-y-2 text-sm">
                    <p><span class="text-muted-foreground">Nama:</span> {{ user.name }}</p>
                    <p><span class="text-muted-foreground">Email:</span> {{ user.email }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground">Role:</span>
                        <Badge v-for="role in user.roles" :key="role" variant="secondary">{{ role }}</Badge>
                    </div>
                </div>
                <p v-else class="text-destructive text-sm">Tidak ada user ter-autentikasi.</p>
            </CardContent>
        </Card>

        <div class="flex flex-wrap gap-3">
            <Button>Primary</Button>
            <Button variant="secondary">Secondary</Button>
            <Button variant="outline">Outline</Button>
            <Button variant="destructive">Destructive</Button>
            <Button variant="ghost">Ghost</Button>
        </div>

        <a href="/dashboard" class="text-muted-foreground text-sm underline underline-offset-4">
            &larr; Kembali ke dashboard (Livewire)
        </a>
    </div>
</template>
