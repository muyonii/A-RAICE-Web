<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/AlertController';
import { formatDate } from '@/lib/formatDate';

type Severity = 'low' | 'medium' | 'high';

interface AlertDetail {
    id: number;
    message: string;
    severity: Severity;
    disease: string | null;
    municipality: string;
    created_at: string | null;
    read_at: string | null;
}

defineProps<{ alert: AlertDetail }>();

const severityClasses: Record<Severity, string> = {
    low: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    medium: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    high: 'bg-red-100 text-red-800 ring-red-600/20',
};
</script>

<template>
    <Head title="Alert" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <Link
                :href="index().url"
                class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#14391f] shadow-sm transition hover:bg-[#FBF8EA]"
            >
                &larr; Back to alerts
            </Link>

            <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                        :class="severityClasses[alert.severity]"
                    >
                        {{ alert.severity }} severity
                    </span>
                    <span
                        class="rounded-full bg-[#FBF8EA] px-3 py-1 text-xs font-medium text-[#14391f] capitalize"
                    >
                        {{ alert.municipality }}
                    </span>
                </div>

                <p class="mt-4 text-2xl font-semibold text-[#14391f]">
                    {{ alert.disease ?? 'Unknown disease' }}
                </p>

                <p class="mt-3 text-sm leading-relaxed text-gray-700">
                    {{ alert.message }}
                </p>

                <dl
                    class="mt-6 grid gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2"
                >
                    <div>
                        <dt class="text-gray-500">Issued</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            {{ formatDate(alert.created_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Read</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            {{ formatDate(alert.read_at) }}
                        </dd>
                    </div>
                </dl>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
