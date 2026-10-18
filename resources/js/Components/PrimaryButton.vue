<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'default' | 'auth' | 'app';
    }>(),
    {
        variant: 'default',
    },
);
</script>

<template>
    <button
        :class="
            variant === 'auth'
                ? 'inline-flex w-full items-center justify-center rounded-full border border-transparent bg-[#FBEB9A] px-5 py-3 text-sm font-bold text-[#14391f] transition duration-150 ease-in-out hover:bg-[#f5df7a] focus:ring-2 focus:ring-[#FBEB9A] focus:ring-offset-2 focus:ring-offset-[#14391f] focus:outline-none active:bg-[#f5df7a]'
                : variant === 'app'
                  ? 'inline-flex items-center rounded-full border border-transparent bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition duration-150 ease-in-out hover:bg-[#0f2d19] focus:ring-2 focus:ring-[#14391f] focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50'
                  : 'inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900'
        "
    >
        <slot />
    </button>
</template>
