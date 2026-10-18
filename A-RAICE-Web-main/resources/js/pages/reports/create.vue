<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/ReportController';

defineProps<{
    municipalities: string[];
    diseases: { id: number; name: string }[];
}>();

const form = useForm({
    municipality: '',
    range_start: '',
    range_end: '',
    disease_id: '',
});

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="New Report" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="mx-auto max-w-3xl space-y-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8"
            >
                <div>
                    <h2 class="text-base font-semibold text-[#14391f]">
                        Report details
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Choose a municipality, date range, and optional disease.
                    </p>
                </div>

                <div>
                    <label
                        for="municipality"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Municipality
                    </label>
                    <select
                        id="municipality"
                        v-model="form.municipality"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    >
                        <option value="" disabled>Select municipality</option>
                        <option v-for="m in municipalities" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.municipality"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.municipality }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="range_start"
                            class="block text-sm font-medium text-gray-700"
                        >
                            From
                        </label>
                        <input
                            id="range_start"
                            v-model="form.range_start"
                            type="date"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                        />
                        <p
                            v-if="form.errors.range_start"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.range_start }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="range_end"
                            class="block text-sm font-medium text-gray-700"
                        >
                            To
                        </label>
                        <input
                            id="range_end"
                            v-model="form.range_end"
                            type="date"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                        />
                        <p
                            v-if="form.errors.range_end"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.range_end }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        for="disease_id"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Disease (optional)
                    </label>
                    <select
                        id="disease_id"
                        v-model="form.disease_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    >
                        <option value="">All diseases</option>
                        <option v-for="d in diseases" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.disease_id"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.disease_id }}
                    </p>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-5">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                        @click="submit"
                    >
                        Generate report
                    </button>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
