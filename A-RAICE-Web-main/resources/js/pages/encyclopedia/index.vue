<script setup lang="ts">
import { computed } from 'vue';
import EncyclopediaLayout from '@/Layouts/EncyclopediaLayout.vue';
import { create as createDisease } from '@/actions/App/Http/Controllers/Admin/DiseaseController';
import { show } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { DiseaseSummary } from '@/types';

defineProps<{
    diseases: DiseaseSummary[];
}>();

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
</script>

<template>
    <Head title="Encyclopedia" />

    <EncyclopediaLayout>
        <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            <div v-if="isAdmin" class="mb-4 flex justify-end">
                <Link
                    :href="createDisease().url"
                    class="inline-flex shrink-0 items-center rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
                >
                    Add disease
                </Link>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="disease in diseases"
                    :key="disease.id"
                    :href="show(disease.id)"
                    class="card"
                >
                    <div class="face face1">
                        <div class="overview-content">
                            <h2 class="text-lg font-semibold">
                                {{ disease.name }}
                            </h2>
                            <p
                                class="mt-3 line-clamp-5 text-sm leading-6 text-gray-600"
                            >
                                {{ disease.description }}
                            </p>
                            <div class="overview-footer">
                                <span class="text-xs text-gray-500">
                                    {{ disease.treatments_count }} treatments
                                </span>
                                <span
                                    class="text-sm font-medium text-green-700"
                                >
                                    Click for more information
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="face face2">
                        <img
                            v-if="disease.image_path"
                            :src="`/${disease.image_path}`"
                            alt=""
                            class="disease-photo"
                        />
                        <div v-else class="disease-photo-fallback">
                            Image not available
                        </div>
                        <h2>{{ disease.name }}</h2>
                    </div>
                </Link>
            </div>
        </div>
    </EncyclopediaLayout>
</template>

<style scoped>
.card {
    position: relative;
    width: 100%;
    height: 320px;
    margin: 0 auto;
    overflow: hidden;
    border: 1px solid rgb(229 231 235);
    border-radius: 0.75rem;
    background: white;
    box-shadow: 0 4px 12px rgb(0 0 0 / 8%);
    color: inherit;
    text-decoration: none;
}

.card:focus-visible {
    outline: 2px solid rgb(21 128 61);
    outline-offset: 3px;
}

.card .face {
    position: absolute;
    bottom: 0;
    left: 0;
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
}

.card .face.face1 {
    box-sizing: border-box;
    align-items: flex-start;
    padding: 20px;
}

.overview-content {
    display: flex;
    height: 100%;
    flex-direction: column;
    width: 100%;
}

.overview-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: auto;
    border-top: 1px solid rgb(243 244 246);
    padding-top: 0.75rem;
}

.card .face.face2 {
    flex-direction: column;
    overflow: hidden;
    border-radius: 0.75rem;
    background: rgb(249 250 251);
    transition:
        height 650ms ease,
        border-radius 650ms ease;
}

.disease-photo {
    box-sizing: border-box;
    width: 100%;
    min-height: 0;
    flex: 1;
    background: rgb(249 250 251);
    object-fit: contain;
    padding: 0.75rem;
    transition:
        flex-basis 650ms ease,
        padding 650ms ease,
        opacity 450ms ease,
        transform 650ms ease;
}

.disease-photo-fallback {
    display: grid;
    width: 100%;
    min-height: 0;
    flex: 1;
    place-items: center;
    color: rgb(107 114 128);
    font-size: 0.875rem;
    transition:
        flex-basis 650ms ease,
        padding 650ms ease,
        opacity 450ms ease;
}

.card .face.face2 h2 {
    box-sizing: border-box;
    display: flex;
    width: 100%;
    flex: 0 0 64px;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0 1rem;
    color: rgb(17 24 39);
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1.2;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    transition:
        font-size 500ms ease,
        line-height 500ms ease;
}

.card:hover .face.face2,
.card:focus-visible .face.face2 {
    height: 64px;
    border-radius: 0 0 0.75rem 0.75rem;
}

.card:hover .disease-photo,
.card:focus-visible .disease-photo,
.card:hover .disease-photo-fallback,
.card:focus-visible .disease-photo-fallback {
    flex-basis: 0;
    padding: 0;
    opacity: 0;
}

.card:hover .face.face2 h2,
.card:focus-visible .face.face2 h2 {
    font-size: 1rem;
}

@media (prefers-reduced-motion: reduce) {
    .card .face.face2,
    .disease-photo,
    .disease-photo-fallback,
    .card .face.face2 h2 {
        transition-duration: 450ms;
    }
}
</style>
