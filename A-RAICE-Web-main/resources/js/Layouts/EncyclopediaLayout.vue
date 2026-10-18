<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <AuthenticatedLayout v-if="user">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </AuthenticatedLayout>
    <PublicLayout v-else>
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </PublicLayout>
</template>
