<script setup>
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Tooltip, Legend);

/**
 * Pembungkus tipis vue-chartjs. Tinggi diatur lewat wadah (bukan atribut
 * height) supaya grafik ikut melebar mengikuti kartunya.
 */
const props = defineProps({
    tipe: { type: String, default: 'bar' },
    data: { type: Object, required: true },
    opsi: { type: Object, default: () => ({}) },
    tinggi: { type: String, default: 'h-72' },
});

const komponen = computed(() => (props.tipe === 'line' ? Line : Bar));
</script>

<template>
    <div :class="tinggi" class="relative w-full">
        <component :is="komponen" :data="data" :options="opsi" />
    </div>
</template>
