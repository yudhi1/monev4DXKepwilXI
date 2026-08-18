<script setup>
import { computed, onMounted, ref, watch } from 'vue';
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
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
    ChartNoAxesCombined,
    ChevronDown,
    FolderKanban,
    Grid2x2,
    LogOut,
    Menu,
    PanelLeft,
} from '@lucide/vue';
import { cn } from '@/lib/utils';
import { BRAND_MODUL, MENU_MODUL } from './menu';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles ?? []);

/* --- Modul yang sedang dibuka & modul lain yang boleh diakses --- */
const modulAktif = computed(() => page.props.modul?.aktif ?? '4dx');
const modulTersedia = computed(() => page.props.modul?.daftar ?? []);
const bisaGantiModul = computed(() => modulTersedia.value.length > 1);
const brand = computed(() => BRAND_MODUL[modulAktif.value] ?? BRAND_MODUL['4dx']);

const IKON_MODUL = { ChartNoAxesCombined, FolderKanban };

const ROLE_LABEL = {
    admin: 'Admin',
    kedeputian_wilayah: 'Wilayah',
    kantor_cabang: 'Cabang',
};

/*
 | Pegawai perorangan tidak punya role spatie — role itu milik modul 4DX dan
 | hanya dipakai akun unit kerja. Untuk mereka yang ditampilkan adalah role PM
 | beserta bidangnya.
 */
const PM_ROLE_LABEL = {
    member: 'Member',
    project_manager: 'Project Manager',
    pimpinan: 'Pimpinan',
};

const roleLabel = computed(() => {
    if (user.value?.tipe === 'pegawai') {
        return PM_ROLE_LABEL[user.value?.pm_role] ?? 'Pegawai';
    }

    return ROLE_LABEL[roles.value[0]] ?? '-';
});
const initial = computed(() => (user.value?.name ?? '?').charAt(0).toUpperCase());

const bisa = (...izin) => izin.some((r) => roles.value.includes(r));

/* Menu diambil sesuai modul aktif, lalu disaring menurut role user. */
const menu = computed(() =>
    (MENU_MODUL[modulAktif.value] ?? [])
        .filter((m) => m.roles === null || bisa(...m.roles))
        .map((m) => ({
            ...m,
            items: m.items?.filter((i) => !i.roles || bisa(...i.roles)),
        }))
);

const currentPath = computed(() => page.url.split('?')[0]);

const aktif = (item) => {
    if (item.href) {
        return currentPath.value === item.href || currentPath.value.startsWith(item.href + '/');
    }

    return (item.items ?? []).some((i) => aktif(i));
};

/* --- Ciut / lebar, diingat antar kunjungan --- */
const KUNCI_CIUT = 'monev4dx.sidebar.ciut';
const ciut = ref(false);

onMounted(() => {
    ciut.value = localStorage.getItem(KUNCI_CIUT) === '1';
});

watch(ciut, (nilai) => localStorage.setItem(KUNCI_CIUT, nilai ? '1' : '0'));

/* --- Grup yang terbuka; grup yang sedang aktif otomatis terbuka --- */
const grupTerbuka = ref({});

watch(
    menu,
    (daftar) => {
        daftar.forEach((m) => {
            if (m.items && aktif(m) && grupTerbuka.value[m.label] === undefined) {
                grupTerbuka.value[m.label] = true;
            }
        });
    },
    { immediate: true }
);

const toggleGrup = (label) => (grupTerbuka.value[label] = !grupTerbuka.value[label]);

/* --- Mobile --- */
const laciTerbuka = ref(false);

watch(currentPath, () => (laciTerbuka.value = false));

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

        <!-- ============ Sidebar desktop ============ -->
        <aside
            :class="
                cn(
                    'bg-background fixed inset-y-0 left-0 z-40 hidden flex-col border-r transition-[width] duration-200 lg:flex',
                    ciut ? 'w-[4.5rem]' : 'w-64'
                )
            "
        >
            <!-- Brand: mengikuti modul yang sedang dibuka -->
            <div class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <Link :href="brand.beranda" class="flex items-center gap-2 overflow-hidden">
                    <span
                        class="from-primary to-success flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br text-white shadow-sm"
                    >
                        <ChartNoAxesCombined class="size-4" />
                    </span>
                    <span v-if="!ciut" class="text-[15px] font-semibold tracking-tight whitespace-nowrap">
                        {{ brand.judul }} <span class="text-muted-foreground font-normal">{{ brand.sub }}</span>
                    </span>
                </Link>
            </div>

            <!-- Navigasi -->
            <TooltipProvider :delay-duration="0">
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <template v-for="item in menu" :key="item.label">
                        <!-- Tautan tunggal -->
                        <template v-if="item.href">
                            <Tooltip v-if="ciut">
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="item.href"
                                        :class="
                                            cn(
                                                'flex h-10 items-center justify-center rounded-md transition-colors',
                                                aktif(item)
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                            )
                                        "
                                    >
                                        <component :is="item.icon" class="size-[18px]" />
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent side="right">{{ item.label }}</TooltipContent>
                            </Tooltip>

                            <Link
                                v-else
                                :href="item.href"
                                :class="
                                    cn(
                                        'flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
                                        aktif(item)
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                    )
                                "
                            >
                                <component :is="item.icon" class="size-[18px] shrink-0" />
                                <span class="truncate">{{ item.label }}</span>
                            </Link>
                        </template>

                        <!-- Grup: saat ciut jadi dropdown, saat lebar jadi accordion -->
                        <template v-else>
                            <DropdownMenu v-if="ciut">
                                <DropdownMenuTrigger as-child>
                                    <button
                                        :class="
                                            cn(
                                                'flex h-10 w-full items-center justify-center rounded-md transition-colors',
                                                aktif(item)
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                            )
                                        "
                                    >
                                        <component :is="item.icon" class="size-[18px]" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent side="right" align="start" class="w-60">
                                    <DropdownMenuLabel>{{ item.label }}</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem v-for="sub in item.items" :key="sub.href" as-child>
                                        <Link :href="sub.href" class="w-full cursor-pointer">{{ sub.label }}</Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <div v-else>
                                <button
                                    :class="
                                        cn(
                                            'flex h-10 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors',
                                            aktif(item)
                                                ? 'text-foreground'
                                                : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                        )
                                    "
                                    @click="toggleGrup(item.label)"
                                >
                                    <component :is="item.icon" class="size-[18px] shrink-0" />
                                    <span class="truncate">{{ item.label }}</span>
                                    <ChevronDown
                                        :class="
                                            cn(
                                                'ml-auto size-4 shrink-0 opacity-60 transition-transform',
                                                grupTerbuka[item.label] && 'rotate-180'
                                            )
                                        "
                                    />
                                </button>

                                <div v-if="grupTerbuka[item.label]" class="border-border mt-1 ml-[1.4rem] space-y-0.5 border-l pl-3">
                                    <Link
                                        v-for="sub in item.items"
                                        :key="sub.href"
                                        :href="sub.href"
                                        :class="
                                            cn(
                                                'block rounded-md px-3 py-2 text-sm transition-colors',
                                                aktif(sub)
                                                    ? 'bg-secondary text-foreground font-medium'
                                                    : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground'
                                            )
                                        "
                                    >
                                        {{ sub.label }}
                                    </Link>
                                </div>
                            </div>
                        </template>
                    </template>
                </nav>
            </TooltipProvider>

            <!-- Kaki: identitas user -->
            <div class="shrink-0 border-t p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            :class="
                                cn(
                                    'hover:bg-secondary flex w-full items-center gap-2 rounded-md p-2 transition-colors',
                                    ciut && 'justify-center'
                                )
                            "
                        >
                            <span
                                class="from-primary to-success flex size-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-sm font-semibold text-white"
                            >
                                {{ initial }}
                            </span>
                            <span v-if="!ciut" class="min-w-0 flex-1 text-left leading-tight">
                                <span class="block truncate text-sm font-medium">{{ user?.name }}</span>
                                <span class="text-muted-foreground block text-xs">{{ roleLabel }}</span>
                            </span>
                            <ChevronDown v-if="!ciut" class="size-4 shrink-0 opacity-60" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent side="top" align="start" class="w-56">
                        <DropdownMenuLabel>
                            <p class="text-sm font-medium">{{ user?.name }}</p>
                            <p class="text-muted-foreground text-xs font-normal">{{ user?.email }}</p>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />

                        <!-- Pindah modul tanpa perlu keluar dulu -->
                        <template v-if="bisaGantiModul">
                            <DropdownMenuLabel class="text-muted-foreground text-xs font-normal">
                                Aplikasi
                            </DropdownMenuLabel>
                            <DropdownMenuItem v-for="m in modulTersedia" :key="m.kunci" as-child>
                                <Link
                                    :href="m.beranda"
                                    :class="
                                        cn(
                                            'w-full cursor-pointer',
                                            m.kunci === modulAktif && 'bg-secondary font-medium'
                                        )
                                    "
                                >
                                    <component :is="IKON_MODUL[m.ikon] ?? Grid2x2" class="mr-2 size-4" />
                                    {{ m.nama }}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                        </template>

                        <DropdownMenuItem class="cursor-pointer" @select="logout">
                            <LogOut class="mr-2 size-4" />
                            Keluar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>

        <!-- ============ Laci mobile ============ -->
        <Sheet v-model:open="laciTerbuka">
            <SheetContent side="left" class="w-72 p-0">
                <div class="flex h-14 items-center gap-2 border-b px-4">
                    <span
                        class="from-primary to-success flex size-8 items-center justify-center rounded-lg bg-gradient-to-br text-white shadow-sm"
                    >
                        <ChartNoAxesCombined class="size-4" />
                    </span>
                    <span class="text-[15px] font-semibold tracking-tight">
                        {{ brand.judul }} <span class="text-muted-foreground font-normal">{{ brand.sub }}</span>
                    </span>
                </div>
                <nav class="h-[calc(100vh-3.5rem)] space-y-1 overflow-y-auto p-3">
                    <template v-for="item in menu" :key="item.label">
                        <Link
                            v-if="item.href"
                            :href="item.href"
                            :class="
                                cn(
                                    'flex h-10 items-center gap-3 rounded-md px-3 text-sm font-medium',
                                    aktif(item) ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'
                                )
                            "
                        >
                            <component :is="item.icon" class="size-[18px] shrink-0" />
                            {{ item.label }}
                        </Link>
                        <div v-else class="py-1">
                            <p class="text-muted-foreground flex items-center gap-3 px-3 py-2 text-xs font-semibold tracking-wide uppercase">
                                <component :is="item.icon" class="size-4 shrink-0" />
                                {{ item.label }}
                            </p>
                            <Link
                                v-for="sub in item.items"
                                :key="sub.href"
                                :href="sub.href"
                                :class="
                                    cn(
                                        'ml-[1.9rem] block rounded-md px-3 py-2 text-sm',
                                        aktif(sub) ? 'bg-secondary font-medium' : 'text-muted-foreground'
                                    )
                                "
                            >
                                {{ sub.label }}
                            </Link>
                        </div>
                    </template>
                </nav>
            </SheetContent>
        </Sheet>

        <!-- ============ Konten ============ -->
        <div :class="cn('transition-[padding] duration-200', ciut ? 'lg:pl-[4.5rem]' : 'lg:pl-64')">
            <header
                class="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky top-0 z-30 flex h-14 items-center gap-2 border-b px-4 backdrop-blur"
            >
                <Button variant="ghost" size="icon" class="lg:hidden" @click="laciTerbuka = true">
                    <Menu class="size-5" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="hidden lg:inline-flex"
                    :title="ciut ? 'Lebarkan sidebar' : 'Ciutkan sidebar'"
                    @click="ciut = !ciut"
                >
                    <PanelLeft class="size-5" />
                </Button>

                <div class="min-w-0 flex-1">
                    <slot name="judul" />
                </div>

                <slot name="aksi-header" />
            </header>

            <main class="p-4 lg:p-6">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
