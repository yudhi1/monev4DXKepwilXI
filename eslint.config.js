import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

/*
 | Penyaring statis untuk kode Vue.
 |
 | Tujuannya sempit tapi penting: menangkap variabel/komponen yang dipakai
 | tanpa diimpor. Kesalahan seperti itu lolos dari `npm run build` — Vue baru
 | mengeluhkannya saat render, dan akibatnya halaman tampil kosong tanpa pesan
 | apa pun. Aturan gaya sengaja tidak diaktifkan; Prettier/Pint sudah menangani
 | itu.
 */
export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'resources/js/components/ui/**'],
    },

    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],

    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                route: 'readonly',
            },
        },
        rules: {
            // Inti dari konfigurasi ini.
            'no-undef': 'error',
            'vue/no-undef-components': ['error', { ignorePatterns: ['component'] }],

            // Variabel menganggur menandakan impor tertinggal atau kode mati.
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],

            // Nama komponen satu kata lazim dipakai di proyek ini (Lencana, Paginasi).
            'vue/multi-word-component-names': 'off',

            /*
             | Satu-satunya pemakaian v-html adalah label paginator Laravel di
             | components/Paginasi.vue, yang memang memuat entitas HTML dan
             | berasal dari framework, bukan input pengguna.
             */
            'vue/no-v-text-v-html-on-component': 'off',
        },
    },
];
