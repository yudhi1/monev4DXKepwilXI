<script setup>
import { Link } from '@inertiajs/vue3';

/**
 * Membungkus paginator Laravel. `data` adalah objek hasil ->paginate()
 * yang dikirim apa adanya sebagai prop Inertia.
 */
defineProps({
    data: { type: Object, required: true },
});
</script>

<template>
    <div v-if="data.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
        <p class="text-muted-foreground text-sm">
            Menampilkan {{ data.from }}–{{ data.to }} dari {{ data.total }}
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(tautan, i) in data.links" :key="i">
                <Link
                    v-if="tautan.url"
                    :href="tautan.url"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'rounded-md border px-3 py-1.5 text-sm transition-colors',
                        tautan.active ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-secondary',
                    ]"
                    v-html="tautan.label"
                />
                <span
                    v-else
                    class="text-muted-foreground rounded-md border px-3 py-1.5 text-sm opacity-50"
                    v-html="tautan.label"
                />
            </template>
        </div>
    </div>
</template>
