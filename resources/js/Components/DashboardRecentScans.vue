<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/formatDate';
import {
    create as scansCreate,
    show as scanShow,
} from '@/actions/App/Http/Controllers/ScanController';
import type { RecentScan, StaffRecentScan } from '@/types';

defineProps<{
    scans: (RecentScan | StaffRecentScan)[];
    subtitle?: string;
}>();

const statusClasses: Record<RecentScan['status'], string> = {
    pending: 'bg-gray-100 text-gray-600',
    processing: 'bg-gray-100 text-gray-600',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
};

function confidencePercentage(score: string | null): string {
    return score === null ? '—' : `${(Number(score) * 100).toFixed(1)}%`;
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm"
        aria-label="Recent scans"
    >
        <div class="pb-4">
            <h3 class="font-semibold text-gray-900">Recent scans</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ subtitle ?? 'Your latest leaf analyses' }}
            </p>
        </div>

        <ul v-if="scans.length" class="divide-y divide-gray-100">
            <li
                v-for="scan in scans"
                :key="scan.id"
                class="py-4 first:pt-0 last:pb-0"
            >
                <Link
                    :href="scanShow(scan.id).url"
                    class="flex flex-col gap-3 transition sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ scan.disease ?? 'No disease detected' }}
                        </p>
                        <p
                            v-if="'farmer_name' in scan"
                            class="mt-1 text-sm text-gray-500"
                        >
                            {{ scan.farmer_name ?? 'Farmer unavailable' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Confidence:
                            {{ confidencePercentage(scan.confidence_score) }}
                        </p>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-3 text-sm sm:justify-end"
                    >
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                            :class="statusClasses[scan.status]"
                        >
                            {{ scan.status }}
                        </span>
                        <time class="text-gray-500">
                            {{ formatDate(scan.scan_date) }}
                        </time>
                    </div>
                </Link>
            </li>
        </ul>

        <div v-else class="py-6 text-center">
            <p class="text-sm text-gray-600">
                No scans yet. Start a scan to see your results here.
            </p>
            <Link
                :href="scansCreate().url"
                class="mt-4 inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19]"
            >
                New scan
            </Link>
        </div>
    </section>
</template>
