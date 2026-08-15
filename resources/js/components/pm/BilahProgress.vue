<script setup>
import { computed } from 'vue';

const props = defineProps({
    nilai: { type: Number, default: 0 },
    tampilkanAngka: { type: Boolean, default: true },
    tinggi: { type: String, default: 'h-1.5' },
});

const persen = computed(() => Math.min(100, Math.max(0, Number(props.nilai) || 0)));

/* Warna mengikuti capaian, bukan status — supaya terbaca sekilas. */
const warna = computed(() => {
    if (persen.value >= 100) return 'bg-emerald-500';
    if (persen.value >= 60) return 'bg-primary';
    if (persen.value >= 30) return 'bg-amber-500';
    return 'bg-slate-400';
});
</script>

<template>
    <div class="flex items-center gap-2">
        <div :class="['bg-secondary w-full overflow-hidden rounded-full', tinggi]">
            <div :class="['h-full rounded-full transition-all', warna]" :style="{ width: persen + '%' }" />
        </div>
        <span v-if="tampilkanAngka" class="text-muted-foreground w-9 shrink-0 text-right text-xs tabular-nums">
            {{ persen }}%
        </span>
    </div>
</template>
