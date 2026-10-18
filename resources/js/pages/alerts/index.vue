<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { show } from '@/actions/App/Http/Controllers/AlertController';
import { formatDate } from '@/lib/formatDate';
import type { Alert } from '@/types';

defineProps<{
    alerts: {
        data: Alert[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const severityClass = {
    low: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    medium: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    high: 'bg-red-100 text-red-800 ring-red-600/20',
} as const;
</script>
app

<template>
    <Head title="Alerts" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section
                v-if="alerts.data.length === 0"
                class="rounded-2xl bg-white px-6 py-16 text-center text-sm text-gray-500 shadow-sm"
            >
                No alerts yet.
            </section>

            <ul
                v-else
                class="divide-y divide-gray-100 overflow-hidden rounded-2xl bg-white shadow-sm"
            >
                <li
                    v-for="alert in alerts.data"
                    :key="alert.id"
                    class="border-l-4"
                    :class="
                        alert.read_at === null
                            ? 'border-[#FBEB9A] bg-[#FBF8EA]/60'
                            : 'border-transparent'
                    "
                >
                    <Link
                        :href="show(alert.id).url"
                        class="flex flex-col gap-3 p-4 transition hover:bg-[#FBF8EA]/70 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-6"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-[#14391f]">
                                    {{ alert.disease }} ·
                                    {{ alert.municipality }}
                                </span>
                                <span
                                    v-if="alert.read_at === null"
                                    class="h-2 w-2 shrink-0 rounded-full bg-[#14391f]"
                                    aria-hidden="true"
                                ></span>
                                <span
                                    v-if="alert.read_at === null"
                                    class="sr-only"
                                >
                                    Unread
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                {{ alert.message }}
                            </div>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-3 text-sm sm:shrink-0 sm:justify-end"
                        >
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                                :class="severityClass[alert.severity]"
                            >
                                {{ alert.severity }}
                            </span>
                            <time class="text-gray-500">
                                {{ formatDate(alert.created_at) }}
                            </time>
                        </div>
                    </Link>
                </li>
            </ul>

            <nav
                v-if="alerts.links.length > 3"
                aria-label="Pagination"
                class="flex flex-wrap items-center justify-end gap-1"
            >
                <template v-for="link in alerts.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border px-3 py-2 text-sm transition"
                        :class="
                            link.active
                                ? 'border-[#14391f] bg-[#14391f] text-[#FBEB9A]'
                                : 'border-gray-200 bg-white text-gray-700 hover:bg-[#FBF8EA]'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </main>
    </AuthenticatedLayout>
</template>
