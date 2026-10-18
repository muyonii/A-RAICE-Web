<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="block w-full rounded-lg px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-[#edf4e8] hover:text-[#14391f] focus:bg-[#edf4e8] focus:text-[#14391f] focus:outline-none"
    >
        <slot />
    </Link>
</template>
