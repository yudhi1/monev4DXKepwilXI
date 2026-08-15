<script setup>
import { computed } from 'vue';

/*
 | Lencana status/prioritas. Warnanya berasal dari config/pm.php yang dikirim
 | lewat prop `opsi`, jadi menambah status di backend tidak perlu menyentuh
 | komponen ini — cukup pastikan nama warnanya ada di peta di bawah.
 */
const props = defineProps({
    nilai: { type: String, default: null },
    peta: { type: Object, required: true },
});

const meta = computed(() => props.peta?.[props.nilai] ?? null);

const KELAS = {
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
};
</script>

<template>
    <span
        v-if="meta"
        :class="[
            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap',
            KELAS[meta.warna] ?? KELAS.slate,
        ]"
    >
        {{ meta.label }}
    </span>
</template>
