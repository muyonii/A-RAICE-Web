<script setup lang="ts">
import { computed } from 'vue';
import EncyclopediaLayout from '@/Layouts/EncyclopediaLayout.vue';
import {
    destroy as destroyDisease,
    edit as editDisease,
} from '@/actions/App/Http/Controllers/Admin/DiseaseController';
import { index } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { Disease, TreatmentType } from '@/types';

const props = defineProps<{
    disease: Disease;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const deleteForm = useForm<Record<string, string>>({});

const treatmentTypeClasses: Record<TreatmentType, string> = {
    chemical: 'bg-orange-100 text-orange-800',
    biological: 'bg-blue-100 text-blue-800',
    cultural: 'bg-green-100 text-green-800',
    organic: 'bg-yellow-100 text-yellow-800',
};

function deleteDisease() {
    if (
        !window.confirm(
            'Deleting this disease will also remove all of its treatments. Are you sure you want to continue?',
        )
    ) {
        return;
    }

    deleteForm.submit(destroyDisease({ disease: props.disease.id }));
}
</script>

<template>
    <Head :title="disease.name" />

    <EncyclopediaLayout>
        <main
            :class="
                user
                    ? 'mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8'
                    : 'space-y-6 py-8'
            "
        >
            <Link
                :href="index()"
                class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-[#14391f] shadow-sm transition hover:bg-[#FBF8EA]"
            >
                &larr; Back to encyclopedia
            </Link>

            <section
                class="grid overflow-hidden rounded-2xl bg-white shadow-sm md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]"
            >
                <div
                    v-if="disease.image_path"
                    class="flex min-h-64 items-center justify-center bg-[#FBF8EA] p-6 md:min-h-80"
                >
                    <img
                        :src="`/${disease.image_path}`"
                        :alt="disease.name"
                        class="max-h-96 w-full object-contain"
                    />
                </div>
                <div class="flex flex-col justify-center p-6 sm:p-8">
                    <h1 class="text-2xl font-bold text-[#14391f] sm:text-3xl">
                        {{ disease.name }}
                    </h1>
                    <p
                        v-if="disease.description"
                        class="mt-4 leading-7 whitespace-pre-line text-gray-700"
                    >
                        {{ disease.description }}
                    </p>

                    <div
                        v-if="isAdmin"
                        class="mt-6 flex flex-wrap items-center gap-3"
                    >
                        <Link
                            :href="editDisease({ disease: disease.id }).url"
                            class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            :disabled="deleteForm.processing"
                            class="inline-flex items-center rounded-full bg-red-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="deleteDisease"
                        >
                            Delete
                        </button>
                    </div>
                    <p
                        v-if="deleteForm.errors.delete"
                        role="alert"
                        class="mt-3 text-sm text-red-600"
                    >
                        {{ deleteForm.errors.delete }}
                    </p>
                </div>
            </section>

            <div class="grid gap-4 md:grid-cols-2">
                <section
                    v-if="disease.causes"
                    class="rounded-2xl bg-white p-6 shadow-sm"
                >
                    <h2 class="font-semibold text-[#14391f]">Causes</h2>
                    <p class="mt-3 leading-6 whitespace-pre-line text-gray-700">
                        {{ disease.causes }}
                    </p>
                </section>

                <section
                    v-if="disease.symptoms"
                    class="rounded-2xl bg-white p-6 shadow-sm"
                >
                    <h2 class="font-semibold text-[#14391f]">Symptoms</h2>
                    <p class="mt-3 leading-6 whitespace-pre-line text-gray-700">
                        {{ disease.symptoms }}
                    </p>
                </section>

                <section
                    v-if="disease.history"
                    class="rounded-2xl bg-white p-6 shadow-sm"
                >
                    <h2 class="font-semibold text-[#14391f]">History</h2>
                    <p class="mt-3 leading-6 whitespace-pre-line text-gray-700">
                        {{ disease.history }}
                    </p>
                </section>

                <section
                    v-if="disease.prevention_tips"
                    class="rounded-2xl bg-white p-6 shadow-sm"
                >
                    <h2 class="font-semibold text-[#14391f]">
                        Prevention tips
                    </h2>
                    <p class="mt-3 leading-6 whitespace-pre-line text-gray-700">
                        {{ disease.prevention_tips }}
                    </p>
                </section>
            </div>

            <section
                v-if="disease.treatments.length"
                class="rounded-2xl bg-white p-6 shadow-sm sm:p-8"
            >
                <h2 class="font-semibold text-[#14391f]">Treatments</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <article
                        v-for="treatment in disease.treatments"
                        :key="treatment.id"
                        class="rounded-2xl bg-[#FBF8EA] p-5"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <h3 class="font-semibold text-gray-900">
                                {{ treatment.title }}
                            </h3>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                :class="treatmentTypeClasses[treatment.type]"
                            >
                                {{ treatment.type }}
                            </span>
                        </div>
                        <p
                            class="mt-3 text-sm leading-6 whitespace-pre-line text-gray-700"
                        >
                            {{ treatment.description }}
                        </p>
                    </article>
                </div>
            </section>

            <section
                v-if="disease.sources"
                class="rounded-2xl bg-white p-6 shadow-sm"
            >
                <h2 class="text-sm font-semibold text-[#14391f]">Sources</h2>
                <p
                    class="mt-3 text-sm leading-6 whitespace-pre-line text-gray-600"
                >
                    {{ disease.sources }}
                </p>
            </section>
        </main>
    </EncyclopediaLayout>
</template>
