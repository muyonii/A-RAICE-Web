<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ inheritAttrs: false });
</script>

<template>
    <div
        class="landing relative isolate flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#14391f] bg-gradient-to-br from-[#14391f] to-[#2f6b3a] px-4 py-10 sm:px-6"
    >
        <img
            src="/images/hero-rice.jpg"
            alt=""
            loading="eager"
            class="absolute inset-0 z-0 h-full w-full scale-105 object-cover object-center"
        />
        <div
            class="pointer-events-none absolute inset-0 z-10 bg-[#14391f]/50"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute inset-0 z-20 bg-[radial-gradient(circle_at_center,rgba(190,210,110,0.35),transparent_60%)]"
            aria-hidden="true"
        ></div>

        <div class="relative z-30 flex w-full flex-col items-center">
            <Link href="/" class="mb-2">
                <ApplicationLogo class="h-12 w-12 brightness-0 invert" />
            </Link>

            <Link
                href="/"
                class="mb-5 rounded-md text-sm font-medium text-white/85 transition hover:text-[#FBEB9A] focus:ring-2 focus:ring-[#FBEB9A] focus:outline-none"
            >
                ← Back to home
            </Link>

            <div
                :class="[
                    'guest-card w-full overflow-hidden rounded-3xl border border-white/20 bg-white/10 px-6 py-7 shadow-2xl shadow-black/20 backdrop-blur-xl sm:px-9 sm:py-9',
                    $attrs.class || 'max-w-xl',
                ]"
                v-bind="{ ...$attrs, class: undefined }"
            >
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.guest-card :deep(.auth-input) {
    border-color: rgb(255 255 255 / 0.28);
    border-radius: 0.75rem;
    background-color: rgb(255 255 255 / 0.1);
    color: white;
}

.guest-card :deep(.auth-input::placeholder) {
    color: rgb(255 255 255 / 0.6);
}

.guest-card :deep(.auth-input:focus) {
    border-color: #fbeb9a;
    --tw-ring-color: #fbeb9a;
}

.guest-card :deep(.auth-input-label) {
    color: rgb(255 255 255 / 0.9);
}

.guest-card :deep(.auth-input-error) {
    color: #fecaca;
}

.guest-card :deep(a) {
    color: rgb(255 255 255 / 0.85);
}

.guest-card :deep(a:hover) {
    color: #fbeb9a;
}

.guest-card :deep(input[type='checkbox']) {
    border-color: rgb(255 255 255 / 0.5);
    background-color: rgb(255 255 255 / 0.1);
    color: #fbeb9a;
    --tw-ring-color: #fbeb9a;
}

.guest-card :deep(select) {
    border-color: rgb(255 255 255 / 0.28);
    border-radius: 0.75rem;
    background-color: rgb(255 255 255 / 0.1);
    color: white;
}

.guest-card :deep(select option) {
    color: #14391f;
}

.guest-card :deep(select:focus) {
    border-color: #fbeb9a;
    --tw-ring-color: #fbeb9a;
}
</style>
