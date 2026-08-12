import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Monitoring 4DX';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Halaman dimuat terpisah (bukan eager) supaya Chart.js dan komponen berat
    // lain hanya ikut terunduh pada halaman yang memakainya.
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});
