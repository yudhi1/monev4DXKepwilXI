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
    /** Kelas tinggi Tailwind, mis. "h-64". Diabaikan bila `tinggiPx` diisi. */
    tinggi: { type: String, default: 'h-72' },
    /** Tinggi dalam piksel, untuk grafik yang tingginya bergantung jumlah data. */
    tinggiPx: { type: Number, default: null },
});

const komponen = computed(() => (props.tipe === 'line' ? Line : Bar));
</script>

<template>
    <div
        class="relative w-full"
        :class="tinggiPx ? '' : tinggi"
        :style="tinggiPx ? { height: `${tinggiPx}px` } : null"
    >
        <component :is="komponen" :data="data" :options="opsi" />
    </div>
</template>
