<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'flex w-full items-center rounded-full bg-[#FBEB9A] px-4 py-2 text-start text-sm font-medium text-[#14391f] transition duration-150 ease-in-out focus:outline-none'
        : 'flex w-full items-center rounded-full px-4 py-2 text-start text-sm font-medium text-gray-700 transition duration-150 ease-in-out hover:bg-[#FBF8EA] hover:text-[#14391f] focus:bg-[#FBF8EA] focus:text-[#14391f] focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
