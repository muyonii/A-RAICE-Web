<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { store } from '@/actions/App/Http/Controllers/Admin/DiseaseController';
import { index as encyclopediaIndex } from '@/routes/encyclopedia';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    prevention_tips: '',
    causes: '',
    symptoms: '',
    history: '',
    sources: '',
    image_path: '',
});

function submit() {
    form.submit(store());
}
</script>

<template>
    <Head title="Add disease" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <form
                class="mx-auto max-w-4xl space-y-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8"
                @submit.prevent="submit"
            >
                <div>
                    <h1 class="text-lg font-semibold text-[#14391f]">
                        Add disease
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Enter the disease details for the encyclopedia.
                    </p>
                </div>

                <div>
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Name <span aria-hidden="true">(required)</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        for="description"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Description <span aria-hidden="true">(required)</span>
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        required
                        rows="4"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.description"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <div>
                    <label
                        for="prevention_tips"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Prevention tips
                        <span aria-hidden="true">(required)</span>
                    </label>
                    <textarea
                        id="prevention_tips"
                        v-model="form.prevention_tips"
                        required
                        rows="4"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.prevention_tips"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.prevention_tips }}
                    </p>
                </div>

                <div>
                    <label
                        for="causes"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Causes
                    </label>
                    <textarea
                        id="causes"
                        v-model="form.causes"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.causes"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.causes }}
                    </p>
                </div>

                <div>
                    <label
                        for="symptoms"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Symptoms
                    </label>
                    <textarea
                        id="symptoms"
                        v-model="form.symptoms"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.symptoms"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.symptoms }}
                    </p>
                </div>

                <div>
                    <label
                        for="history"
                        class="block text-sm font-medium text-gray-700"
                    >
                        History
                    </label>
                    <textarea
                        id="history"
                        v-model="form.history"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.history"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.history }}
                    </p>
                </div>

                <div>
                    <label
                        for="sources"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Sources
                    </label>
                    <textarea
                        id="sources"
                        v-model="form.sources"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    ></textarea>
                    <p
                        v-if="form.errors.sources"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.sources }}
                    </p>
                </div>

                <div>
                    <label
                        for="image_path"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Image path
                    </label>
                    <input
                        id="image_path"
                        v-model="form.image_path"
                        type="text"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p class="mt-1 text-sm text-gray-500">
                        e.g. images/sheath_blight.jpg
                    </p>
                    <p
                        v-if="form.errors.image_path"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.image_path }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5"
                >
                    <Link
                        :href="encyclopediaIndex().url"
                        class="rounded-full px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#FBF8EA]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>
        </main>
    </AuthenticatedLayout>
</template>
