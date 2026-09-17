<script setup>
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Eye, EyeOff } from '@lucide/vue';

/*
 | Kolom password dengan tombol lihat/sembunyikan.
 |
 | Dipakai bersama di form login dan form akun agar admin bisa memastikan
 | password yang diketiknya benar sebelum disimpan — kesalahan ketik pada
 | kolom bertitik baru ketahuan saat penggunanya gagal masuk.
 */
// id/required diteruskan ke <input>, bukan ke pembungkusnya, agar <Label for> tetap mengait.
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: 'new-password' },
});

defineEmits(['update:modelValue']);

const terlihat = ref(false);
</script>

<template>
    <div class="relative">
        <Input
            v-bind="$attrs"
            :model-value="modelValue"
            :type="terlihat ? 'text' : 'password'"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            class="pr-10"
            @update:model-value="$emit('update:modelValue', String($event))"
        />
        <button
            type="button"
            tabindex="-1"
            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 transition"
            :title="terlihat ? 'Sembunyikan password' : 'Lihat password'"
            :aria-label="terlihat ? 'Sembunyikan password' : 'Lihat password'"
            @click="terlihat = ! terlihat"
        >
            <EyeOff v-if="terlihat" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
