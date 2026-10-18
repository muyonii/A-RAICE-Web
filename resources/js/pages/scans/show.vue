<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index as scansIndex } from '@/actions/App/Http/Controllers/ScanController';
import { formatDate } from '@/lib/formatDate';
import type { Scan } from '@/types';

defineProps<{ scan: Scan }>();

const statusClasses: Record<Scan['status'], string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};
</script>

<template>
    <Head title="Scan Result" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <Link
                :href="scansIndex().url"
                class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#14391f] shadow-sm transition hover:bg-[#FBF8EA]"
            >
                &larr; Back to scans
            </Link>

            <div
                class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]"
            >
                <section
                    class="overflow-hidden rounded-2xl bg-white shadow-sm"
                    aria-label="Scan photo"
                >
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    Scan photo
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Uploaded {{ formatDate(scan.scan_date) }}
                                </p>
                            </div>
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                                :class="statusClasses[scan.status]"
                            >
                                {{ scan.status }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="flex min-h-96 items-center justify-center bg-[#FBF8EA] p-4"
                    >
                        <img
                            :src="`/storage/${scan.image_url}`"
                            alt="Uploaded scan"
                            class="max-h-[36rem] w-full rounded-lg object-contain"
                        />
                    </div>
                </section>

                <div class="space-y-6">
                    <section class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">
                            Analysis summary
                        </h3>

                        <div class="mt-5 divide-y divide-gray-100">
                            <div
                                v-if="scan.status === 'completed'"
                                class="flex items-start justify-between gap-4 py-3"
                            >
                                <span class="text-sm text-gray-500"
                                    >Disease</span
                                >
                                <span
                                    class="text-right text-sm font-medium text-gray-900"
                                >
                                    {{ scan.disease?.name ?? 'No match found' }}
                                </span>
                            </div>
                            <div
                                v-if="scan.confidence_score !== null"
                                class="flex items-center justify-between gap-4 py-3"
                            >
                                <span class="text-sm text-gray-500"
                                    >Confidence</span
                                >
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        (scan.confidence_score * 100).toFixed(
                                            1,
                                        )
                                    }}%
                                </span>
                            </div>
                            <div
                                v-if="scan.farmer"
                                class="flex items-start justify-between gap-4 py-3"
                            >
                                <span class="text-sm text-gray-500"
                                    >Farmer</span
                                >
                                <span
                                    class="text-right text-sm font-medium text-gray-900"
                                >
                                    {{ scan.farmer.full_name }}
                                    <span
                                        v-if="scan.farmer.contact_number"
                                        class="mt-1 block font-normal text-gray-500"
                                    >
                                        {{ scan.farmer.contact_number }}
                                    </span>
                                </span>
                            </div>
                            <div
                                v-if="scan.gps_lat !== null"
                                class="flex items-center justify-between gap-4 py-3"
                            >
                                <span class="text-sm text-gray-500"
                                    >Location</span
                                >
                                <span
                                    class="text-right text-sm font-medium text-gray-900"
                                >
                                    {{ scan.gps_lat }}, {{ scan.gps_long }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="scan.status === 'failed'"
                            role="alert"
                            class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                        >
                            Analysis failed. Please try again with a clearer
                            photo.
                        </div>
                        <div
                            v-else-if="
                                scan.status === 'pending' ||
                                scan.status === 'processing'
                            "
                            role="status"
                            class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
                        >
                            Your scan is still being analyzed.
                        </div>
                    </section>

                    <section
                        v-if="scan.status === 'completed' && scan.disease"
                        class="space-y-5 rounded-2xl bg-white p-6 shadow-sm"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-[#14391f] uppercase"
                            >
                                Disease information
                            </p>
                            <h3
                                class="mt-1 text-lg font-semibold text-gray-900"
                            >
                                {{ scan.disease.name }}
                            </h3>
                        </div>

                        <div
                            v-if="scan.disease.description"
                            class="border-t border-gray-100 pt-4"
                        >
                            <h4 class="text-sm font-semibold text-gray-900">
                                Description
                            </h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                {{ scan.disease.description }}
                            </p>
                        </div>
                        <div
                            v-if="scan.disease.symptoms"
                            class="border-t border-gray-100 pt-4"
                        >
                            <h4 class="text-sm font-semibold text-gray-900">
                                Symptoms
                            </h4>
                            <p
                                class="mt-2 text-sm leading-6 whitespace-pre-line text-gray-600"
                            >
                                {{ scan.disease.symptoms }}
                            </p>
                        </div>
                        <div
                            v-if="scan.disease.causes"
                            class="border-t border-gray-100 pt-4"
                        >
                            <h4 class="text-sm font-semibold text-gray-900">
                                Causes
                            </h4>
                            <p
                                class="mt-2 text-sm leading-6 whitespace-pre-line text-gray-600"
                            >
                                {{ scan.disease.causes }}
                            </p>
                        </div>
                        <div
                            v-if="scan.disease.prevention_tips"
                            class="border-t border-gray-100 pt-4"
                        >
                            <h4 class="text-sm font-semibold text-gray-900">
                                Prevention tips
                            </h4>
                            <p
                                class="mt-2 text-sm leading-6 whitespace-pre-line text-gray-600"
                            >
                                {{ scan.disease.prevention_tips }}
                            </p>
                        </div>
                        <div
                            v-if="scan.disease.treatments.length"
                            class="border-t border-gray-100 pt-4"
                        >
                            <h4 class="text-sm font-semibold text-gray-900">
                                Treatments
                            </h4>
                            <ul class="mt-3 space-y-3">
                                <li
                                    v-for="treatment in scan.disease.treatments"
                                    :key="treatment.id"
                                    class="rounded-xl bg-[#FBF8EA] p-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <h5
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ treatment.title }}
                                        </h5>
                                        <span
                                            class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200"
                                        >
                                            {{ treatment.type }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-2 text-sm leading-6 text-gray-600"
                                    >
                                        {{ treatment.description }}
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
