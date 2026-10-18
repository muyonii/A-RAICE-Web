<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { close as closeOutbreak } from '@/actions/App/Http/Controllers/OutbreakController';
import { formatDate } from '@/lib/formatDate';

interface OutbreakRow {
    id: number;
    disease: string;
    municipality: string;
    status: 'active' | 'resolved';
    severity: 'low' | 'medium' | 'high' | null;
    scans_count: number;
    created_at: string;
    closed_at: string | null;
    closed_by: string | null;
}

defineProps<{
    outbreaks: OutbreakRow[];
}>();

const severityClass = {
    low: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    medium: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    high: 'bg-red-100 text-red-800 ring-red-600/20',
};

function close(id: number): void {
    if (!window.confirm('Close this outbreak? This cannot be undone.')) {
        return;
    }

    router.patch(closeOutbreak(id).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Outbreaks" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm">
                <p
                    v-if="outbreaks.length === 0"
                    class="py-10 text-center text-sm text-gray-500"
                >
                    No outbreaks yet.
                </p>

                <div v-else class="-mx-6 overflow-x-auto sm:mx-0">
                    <table
                        class="min-w-full divide-y divide-gray-100 text-left text-sm"
                    >
                        <thead
                            class="bg-[#FBF8EA] text-xs font-semibold tracking-wide text-gray-600 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-3 sm:px-6">Disease</th>
                                <th class="px-4 py-3 sm:px-6">Municipality</th>
                                <th class="px-4 py-3 sm:px-6">Severity</th>
                                <th class="px-4 py-3 sm:px-6">Scans</th>
                                <th class="px-4 py-3 sm:px-6">Opened</th>
                                <th class="px-4 py-3 sm:px-6">Status</th>
                                <th class="px-4 py-3 sm:px-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="o in outbreaks"
                                :key="o.id"
                                class="transition hover:bg-[#FBF8EA]/50"
                            >
                                <td
                                    class="px-4 py-4 font-semibold whitespace-nowrap text-[#14391f] sm:px-6"
                                >
                                    {{ o.disease }}
                                </td>
                                <td
                                    class="px-4 py-4 whitespace-nowrap text-gray-700 capitalize sm:px-6"
                                >
                                    {{ o.municipality }}
                                </td>
                                <td class="px-4 py-4 sm:px-6">
                                    <span
                                        v-if="o.severity"
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                                        :class="severityClass[o.severity]"
                                    >
                                        {{ o.severity }}
                                    </span>
                                    <span v-else class="text-gray-500">—</span>
                                </td>
                                <td class="px-4 py-4 text-gray-700 sm:px-6">
                                    {{ o.scans_count }}
                                </td>
                                <td
                                    class="px-4 py-4 whitespace-nowrap text-gray-600 sm:px-6"
                                >
                                    {{ formatDate(o.created_at) }}
                                </td>
                                <td class="px-4 py-4 sm:px-6">
                                    <span
                                        v-if="o.status === 'active'"
                                        class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset"
                                    >
                                        Active
                                    </span>
                                    <span v-else class="text-gray-500">
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                        >
                                            Resolved
                                        </span>
                                        <span class="mt-1 block text-xs">
                                            {{ formatDate(o.closed_at) }}
                                            <template v-if="o.closed_by">
                                                by {{ o.closed_by }}
                                            </template>
                                        </span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right sm:px-6">
                                    <button
                                        v-if="o.status === 'active'"
                                        type="button"
                                        class="inline-flex items-center rounded-full bg-[#14391f] px-4 py-2 text-xs font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                                        @click="close(o.id)"
                                    >
                                        Close
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
