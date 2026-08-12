/**
 * Warna grafik diambil dari token CSS (--chart-1, --success, dst) supaya
 * grafik ikut berubah bila palet tema diubah — tidak ada hex yang
 * dikunci di dalam komponen.
 */
export function warnaToken(nama, alpha = 1) {
    if (typeof window === 'undefined') {
        return '#2563eb';
    }

    const nilai = getComputedStyle(document.documentElement).getPropertyValue(`--${nama}`).trim();

    if (! nilai) {
        return '#2563eb';
    }

    // Token disimpan sebagai oklch(...); color-mix menangani transparansinya
    // tanpa perlu mengurai komponen warnanya sendiri.
    return alpha < 1 ? `color-mix(in oklab, ${nilai} ${alpha * 100}%, transparent)` : nilai;
}

/** Opsi dasar yang dipakai semua grafik: responsif, legend di bawah, sumbu Y dalam persen. */
export function opsiDasar({ maxY = 120, persen = true, legend = true } = {}) {
    const teks = warnaToken('muted-foreground');
    const garis = warnaToken('border');

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: legend,
                position: 'bottom',
                labels: { color: teks, usePointStyle: true, boxWidth: 8, padding: 16 },
            },
            tooltip: {
                callbacks: persen
                    ? { label: (c) => `${c.dataset.label}: ${c.parsed.y}%` }
                    : undefined,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax: maxY,
                ticks: {
                    color: teks,
                    callback: persen ? (v) => `${v}%` : undefined,
                },
                grid: { color: garis },
                border: { display: false },
            },
            x: {
                ticks: { color: teks },
                grid: { display: false },
                border: { color: garis },
            },
        },
    };
}
