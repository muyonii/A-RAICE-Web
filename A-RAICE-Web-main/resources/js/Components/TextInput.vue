<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="auth-input rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
        v-model="model"
        ref="input"
    />
</template>
