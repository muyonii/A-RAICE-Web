<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/ReportController';
import { formatDate } from '@/lib/formatDate';

defineProps<{
    report: {
        id: number;
        municipality: string;
        range_start: string;
        range_end: string;
        created_at: string;
        generated_by: { name: string } | null;
        summary_data: {
            total_scans: number;
            confirmed_cases: number;
            per_disease: { disease: string; count: number }[];
            top_disease: string | null;
        };
    };
}>();
</script>

<template>
    <Head title="Report" />

    <AuthenticatedLayout>
        <main
            class="report-print mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
        >
            <Link
                :href="index().url"
                class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#14391f] shadow-sm transition hover:bg-[#FBF8EA] print:hidden"
            >
                Back to reports
            </Link>

            <section
                class="rounded-2xl bg-white p-6 shadow-sm sm:p-8 print:break-inside-avoid print:shadow-none"
            >
                <h2 class="text-xl font-semibold text-[#14391f]">
                    {{ report.municipality }} report
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ formatDate(report.range_start) }} to
                    {{ formatDate(report.range_end) }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Generated {{ formatDate(report.created_at) }}
                    <template v-if="report.generated_by?.name">
                        by {{ report.generated_by.name }}
                    </template>
                </p>
            </section>

            <section
                class="grid gap-4 sm:grid-cols-3 print:break-inside-avoid"
                aria-label="Report summary"
            >
                <article
                    class="rounded-2xl bg-[#14391f] p-6 shadow-sm print:bg-white print:ring-1 print:ring-gray-300"
                >
                    <p
                        class="text-sm font-medium text-white/75 print:text-gray-600"
                    >
                        Total scans
                    </p>
                    <p
                        class="mt-2 text-3xl font-bold text-[#FBEB9A] print:text-[#14391f]"
                    >
                        {{ report.summary_data.total_scans }}
                    </p>
                </article>
                <article
                    class="rounded-2xl bg-white p-6 shadow-sm print:shadow-none print:ring-1 print:ring-gray-300"
                >
                    <p class="text-sm font-medium text-gray-500">
                        Confirmed cases
                    </p>
                    <p class="mt-2 text-3xl font-bold text-[#14391f]">
                        {{ report.summary_data.confirmed_cases }}
                    </p>
                </article>
                <article
                    class="rounded-2xl bg-white p-6 shadow-sm print:shadow-none print:ring-1 print:ring-gray-300"
                >
                    <p class="text-sm font-medium text-gray-500">Top disease</p>
                    <p class="mt-2 text-xl font-bold text-[#14391f]">
                        {{ report.summary_data.top_disease ?? 'None' }}
                    </p>
                </article>
            </section>

            <section
                class="overflow-hidden rounded-2xl bg-white shadow-sm print:break-inside-avoid print:shadow-none print:ring-1 print:ring-gray-300"
                aria-label="Disease case counts"
            >
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-[#14391f]">
                        Cases by disease
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-100 text-left text-sm"
                    >
                        <thead
                            class="bg-[#FBF8EA] text-xs font-semibold tracking-wide text-gray-600 uppercase print:bg-gray-100"
                        >
                            <tr>
                                <th class="px-6 py-3">Disease</th>
                                <th class="px-6 py-3 text-right">Cases</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in report.summary_data.per_disease"
                                :key="row.disease"
                                class="print:break-inside-avoid"
                            >
                                <td
                                    class="px-6 py-4 font-medium text-[#14391f]"
                                >
                                    {{ row.disease }}
                                </td>
                                <td class="px-6 py-4 text-right text-gray-700">
                                    {{ row.count }}
                                </td>
                            </tr>
                            <tr v-if="!report.summary_data.per_disease.length">
                                <td
                                    colspan="2"
                                    class="px-6 py-8 text-center text-gray-500"
                                >
                                    No cases in this range.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
