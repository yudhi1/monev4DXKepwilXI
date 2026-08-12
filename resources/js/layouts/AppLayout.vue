<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { toast, Toaster } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ChartNoAxesCombined, ChevronDown, LogOut, Menu, X } from '@lucide/vue';
import { cn } from '@/lib/utils';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles ?? []);

const ROLE_LABEL = {
    admin: 'Admin',
    kedeputian_wilayah: 'Wilayah',
    kantor_cabang: 'Cabang',
};

const roleLabel = computed(() => ROLE_LABEL[roles.value[0]] ?? '-');
const initial = computed(() => (user.value?.name ?? '?').charAt(0).toUpperCase());

const bisa = (...izin) => izin.some((r) => roles.value.includes(r));

/*
 | Struktur menu mengikuti navbar Blade lama (layouts/app.blade.php)
 | supaya informasi arsitekturnya tidak berubah bagi user — hanya tampilannya
 | yang pindah ke shadcn. `roles: null` berarti terbuka untuk semua role.
 */
const menu = computed(() =>
    [
        { label: 'Dashboard', href: '/dashboard', roles: null },
        {
            label: 'Master',
            roles: ['admin'],
            items: [
                { label: 'User', href: '/users' },
                { label: 'Wilayah', href: '/wilayahs' },
                { label: 'Cabang', href: '/cabangs' },
            ],
        },
        {
            label: 'WIG',
            roles: ['admin', 'kedeputian_wilayah'],
            items: [
                { label: 'Input Data WIG', href: '/wigs' },
                { label: 'Input Target WIG', href: '/wig-targets' },
                { label: 'Input Realisasi WIG Bulanan', href: '/wig-realisasi' },
            ],
        },
        { label: 'Lag', href: '/lag-measures', roles: ['admin', 'kedeputian_wilayah'] },
        { label: 'Realisasi WIG', href: '/wig-realisasi', roles: ['kantor_cabang'] },
        {
            label: 'Lead Measure',
            roles: null,
            items: [
                { label: 'Input Data Lead Measure', href: '/lead-measures' },
                { label: 'Input Realisasi Lead Measure', href: '/realisasi' },
            ],
        },
        {
            label: 'Prioritas',
            roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'],
            items: [
                { label: 'Iuran', href: '/monitoring-prioritas/iuran' },
                { separator: true },
                { heading: 'Monev Iuran' },
                { label: 'Master Segmen', href: '/monev-iuran/segmen', roles: ['admin', 'kedeputian_wilayah'] },
                { label: 'Input Realisasi', href: '/monev-iuran/input' },
            ],
        },
        {
            label: 'Monitoring Kinerja',
            roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'],
            items: [
                { label: 'Capaian Total APC', href: '/monitoring-kinerja/total' },
                { separator: true },
                { heading: 'Indikator APC' },
                { label: 'Peserta Aktif', href: '/monitoring-kinerja/peserta-aktif' },
                { label: 'Tingkat Kepuasan Peserta', href: '/monitoring-kinerja/kepuasan' },
                { label: 'Jumlah Penerimaan Iuran', href: '/monitoring-kinerja/penerimaan-iuran' },
                { label: 'Realisasi Biaya Manfaat', href: '/monitoring-kinerja/biaya-manfaat' },
                { label: 'Biaya Operasional', href: '/monitoring-kinerja/biaya-operasional' },
            ],
        },
        { label: 'Laporan', href: '/laporan', roles: ['admin', 'kedeputian_wilayah', 'kantor_cabang'] },
    ]
        .filter((m) => m.roles === null || bisa(...m.roles))
        .map((m) => ({
            ...m,
            items: m.items?.filter((i) => !i.roles || bisa(...i.roles)),
        }))
);

const currentPath = computed(() => page.url.split('?')[0]);

const aktif = (menuItem) => {
    if (menuItem.href) {
        return currentPath.value.startsWith(menuItem.href);
    }

    return (menuItem.items ?? []).some((i) => i.href && currentPath.value.startsWith(i.href));
};

const menuMobileTerbuka = ref(false);

watch(currentPath, () => {
    menuMobileTerbuka.value = false;
});

/*
 | Halaman Livewire lama merender flash sendiri lewat Blade. Halaman Inertia
 | mengambilnya dari props share() dan menampilkannya sebagai toast.
 */
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true, deep: true }
);

const logout = () => router.post('/logout');
</script>

<template>
    <div class="bg-muted/30 min-h-screen">
        <Toaster position="top-right" rich-colors close-button />

        <header class="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky top-0 z-40 border-b backdrop-blur">
            <div class="mx-auto flex h-14 max-w-[1600px] items-center gap-3 px-4">
                <Link href="/dashboard" class="flex shrink-0 items-center gap-2">
                    <span class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg">
                        <ChartNoAxesCombined class="size-4" />
                    </span>
                    <span class="text-[15px] font-semibold tracking-tight">
                        Monev <span class="text-muted-foreground font-normal">4DX</span>
                    </span>
                </Link>

                <!-- Navigasi desktop -->
                <nav class="ml-2 hidden items-center gap-0.5 xl:flex">
                    <template v-for="item in menu" :key="item.label">
                        <Link
                            v-if="item.href"
                            :href="item.href"
                            :class="
                                cn(
                                    'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    aktif(item)
                                        ? 'bg-secondary text-secondary-foreground'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-secondary/60'
                                )
                            "
                        >
                            {{ item.label }}
                        </Link>

                        <DropdownMenu v-else>
                            <DropdownMenuTrigger as-child>
                                <button
                                    :class="
                                        cn(
                                            'flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                            aktif(item)
                                                ? 'bg-secondary text-secondary-foreground'
                                                : 'text-muted-foreground hover:text-foreground hover:bg-secondary/60'
                                        )
                                    "
                                >
                                    {{ item.label }}
                                    <ChevronDown class="size-3.5 opacity-60" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-60">
                                <template v-for="(sub, i) in item.items" :key="i">
                                    <DropdownMenuSeparator v-if="sub.separator" />
                                    <DropdownMenuLabel v-else-if="sub.heading" class="text-muted-foreground text-xs">
                                        {{ sub.heading }}
                                    </DropdownMenuLabel>
                                    <DropdownMenuItem v-else as-child>
                                        <Link :href="sub.href" class="w-full cursor-pointer">{{ sub.label }}</Link>
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <Link
                        href="/panduan"
                        class="text-muted-foreground hover:text-foreground hidden rounded-md px-3 py-1.5 text-sm font-medium xl:block"
                    >
                        Panduan
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="hover:bg-secondary/60 flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition-colors">
                                <span
                                    class="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-full text-sm font-semibold"
                                >
                                    {{ initial }}
                                </span>
                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block text-sm font-medium">{{ user?.name }}</span>
                                    <span class="text-muted-foreground block text-xs">{{ roleLabel }}</span>
                                </span>
                                <ChevronDown class="size-3.5 opacity-60" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel>
                                <p class="text-sm font-medium">{{ user?.name }}</p>
                                <p class="text-muted-foreground text-xs font-normal">{{ user?.email }}</p>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem class="cursor-pointer" @select="logout">
                                <LogOut class="mr-2 size-4" />
                                Keluar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button variant="ghost" size="icon" class="xl:hidden" @click="menuMobileTerbuka = !menuMobileTerbuka">
                        <component :is="menuMobileTerbuka ? X : Menu" class="size-5" />
                    </Button>
                </div>
            </div>

            <!-- Navigasi mobile -->
            <nav v-if="menuMobileTerbuka" class="bg-background max-h-[70vh] overflow-y-auto border-t px-4 py-3 xl:hidden">
                <template v-for="item in menu" :key="item.label">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        :class="
                            cn(
                                'block rounded-md px-3 py-2 text-sm font-medium',
                                aktif(item) ? 'bg-secondary' : 'text-muted-foreground'
                            )
                        "
                    >
                        {{ item.label }}
                    </Link>
                    <div v-else class="py-1">
                        <p class="text-muted-foreground px-3 py-1 text-xs font-semibold tracking-wide uppercase">
                            {{ item.label }}
                        </p>
                        <template v-for="(sub, i) in item.items" :key="i">
                            <Link
                                v-if="sub.href"
                                :href="sub.href"
                                class="text-muted-foreground hover:text-foreground block rounded-md px-3 py-2 text-sm"
                            >
                                {{ sub.label }}
                            </Link>
                        </template>
                    </div>
                </template>
                <Link href="/panduan" class="text-muted-foreground block rounded-md px-3 py-2 text-sm font-medium">
                    Panduan
                </Link>
            </nav>
        </header>

        <main class="mx-auto max-w-[1600px] px-4 py-6">
            <div v-if="$slots.header" class="mb-6">
                <slot name="header" />
            </div>
            <slot />
        </main>
    </div>
</template>
