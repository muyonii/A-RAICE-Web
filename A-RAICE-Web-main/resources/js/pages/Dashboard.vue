<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import DashboardRecentScans from '@/Components/DashboardRecentScans.vue';
import { index as alertsIndex } from '@/actions/App/Http/Controllers/AlertController';
import { index as encyclopediaIndex } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { index as outbreaksIndex } from '@/actions/App/Http/Controllers/OutbreakController';
import { index as reportsIndex } from '@/actions/App/Http/Controllers/ReportController';
import { create as scansCreate } from '@/actions/App/Http/Controllers/ScanController';
import { formatDate } from '@/lib/formatDate';
import type {
    AdminStats,
    ActiveOutbreak,
    DashboardStats,
    RecentScan,
    StaffRecentScan,
} from '@/types';

const props = defineProps<{
    scans_count?: number;
    stats?: DashboardStats;
    admin_stats?: AdminStats;
    recent_scans?: (RecentScan | StaffRecentScan)[];
    active_outbreak?: ActiveOutbreak | null;
}>();

const page = usePage();

const severityClasses: Record<ActiveOutbreak['severity'], string> = {
    low: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    medium: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    high: 'bg-red-100 text-red-800 ring-red-600/20',
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <main
            v-if="props.scans_count !== undefined"
            class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
        >
            <div class="flex justify-end">
                <Link
                    :href="scansCreate().url"
                    class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                >
                    New scan
                </Link>
            </div>

            <section
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2"
                aria-label="Dashboard summary"
            >
                <article class="rounded-2xl bg-[#14391f] p-6 shadow-sm">
                    <p class="text-sm font-medium text-white/75">Total scans</p>
                    <p class="mt-2 text-3xl font-bold text-[#FBEB9A]">
                        {{ props.scans_count }}
                    </p>
                </article>

                <Link
                    :href="alertsIndex().url"
                    class="rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <p class="text-sm font-medium text-gray-500">
                        Unread alerts
                    </p>
                    <p class="mt-2 text-3xl font-bold text-[#14391f]">
                        {{ page.props.unread_alerts_count }}
                    </p>
                    <span
                        class="mt-2 block text-sm text-[#14391f] hover:underline"
                    >
                        View alerts
                    </span>
                </Link>
            </section>

            <section
                class="rounded-2xl bg-white p-6 shadow-sm"
                aria-label="Municipality outbreak status"
            >
                <h3 class="font-semibold text-gray-900">
                    Municipality outbreak status
                </h3>
                <p
                    v-if="!props.active_outbreak"
                    class="mt-3 flex items-center gap-2 text-sm text-gray-600"
                >
                    <span
                        class="h-2 w-2 rounded-full bg-green-600"
                        aria-hidden="true"
                    ></span>
                    No active outbreaks in your municipality
                </p>
                <div v-else class="mt-3 flex flex-wrap items-center gap-3">
                    <p class="font-medium text-gray-900">
                        {{ props.active_outbreak.disease }}
                    </p>
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                        :class="severityClasses[props.active_outbreak.severity]"
                    >
                        {{ props.active_outbreak.severity }} severity
                    </span>
                    <span class="text-sm text-gray-500">
                        Started
                        {{ formatDate(props.active_outbreak.started_at) }}
                    </span>
                </div>
            </section>

            <DashboardRecentScans :scans="props.recent_scans ?? []" />
        </main>

        <main
            v-else-if="props.stats !== undefined"
            class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
        >
            <section aria-label="Dashboard shortcuts">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">
                    Shortcuts
                </h3>
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="scansCreate().url"
                        class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                    >
                        New scan
                    </Link>
                    <Link
                        :href="reportsIndex().url"
                        class="inline-flex items-center rounded-full bg-white px-5 py-2 text-sm font-semibold text-[#14391f] shadow-sm transition hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                    >
                        Reports
                    </Link>
                </div>
            </section>

            <section
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                aria-label="Dashboard statistics"
            >
                <Link
                    :href="outbreaksIndex().url"
                    class="rounded-2xl bg-[#14391f] p-6 shadow-sm transition hover:bg-[#0f2d19]"
                >
                    <p class="text-sm font-medium text-white/75">
                        Active outbreaks
                    </p>
                    <p class="mt-2 text-3xl font-bold text-[#FBEB9A]">
                        {{ props.stats.active_outbreaks }}
                    </p>
                    <span
                        class="mt-2 block text-sm text-[#FBEB9A] hover:underline"
                    >
                        View outbreaks
                    </span>
                </Link>

                <article class="rounded-2xl bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Scans in the last 7 days
                    </p>
                    <p class="mt-2 text-3xl font-bold text-[#14391f]">
                        {{ props.stats.scans_last_7_days }}
                    </p>
                </article>

                <Link
                    :href="alertsIndex().url"
                    class="rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <p class="text-sm font-medium text-gray-500">
                        Unread alerts
                    </p>
                    <p class="mt-2 text-3xl font-bold text-[#14391f]">
                        {{ page.props.unread_alerts_count }}
                    </p>
                    <span
                        class="mt-2 block text-sm text-[#14391f] hover:underline"
                    >
                        View alerts
                    </span>
                </Link>

                <template v-if="props.admin_stats !== undefined">
                    <article class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">
                            Total users
                        </p>
                        <p class="mt-2 text-3xl font-bold text-[#14391f]">
                            {{ props.admin_stats.users_count }}
                        </p>
                    </article>

                    <Link
                        :href="encyclopediaIndex().url"
                        class="rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md lg:col-span-2"
                    >
                        <p class="text-sm font-medium text-gray-500">
                            Diseases
                        </p>
                        <p class="mt-2 text-3xl font-bold text-[#14391f]">
                            {{ props.admin_stats.diseases_count }}
                        </p>
                        <span
                            class="mt-2 block text-sm text-[#14391f] hover:underline"
                        >
                            View encyclopedia
                        </span>
                    </Link>
                </template>
            </section>

            <DashboardRecentScans
                :scans="props.recent_scans ?? []"
                subtitle="Latest scans across Bataan"
            />
        </main>

        <div v-else class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
