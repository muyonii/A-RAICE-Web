<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center rounded-full bg-[#FBEB9A] px-3 py-2 text-sm font-medium leading-5 text-[#14391f] transition duration-150 ease-in-out focus:outline-none'
        : 'inline-flex items-center rounded-full px-3 py-2 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out hover:text-[#14391f] focus:text-[#14391f] focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
