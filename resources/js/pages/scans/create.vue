<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    index as scansIndex,
    store,
} from '@/actions/App/Http/Controllers/ScanController';

interface Farmer {
    id: number;
    full_name: string;
    barangay: string;
}

defineProps<{
    farmers: Farmer[];
}>();

const page = usePage();
const requiresFarmer = computed(() => page.props.auth.user.role !== 'farmer');
const imagePreviewUrl = ref<string | null>(null);
const isDraggingPhoto = ref(false);
const imageInputError = ref<string | null>(null);
let currentImageObjectUrl: string | null = null;
let dragDepth = 0;

const form = useForm({
    image: null as File | null,
    farmer_id: null as number | null,
    gps_lat: null as number | null,
    gps_long: null as number | null,
});

onMounted(() => {
    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.gps_lat = position.coords.latitude;
            form.gps_long = position.coords.longitude;
        },
        () => {},
        { timeout: 10000, maximumAge: 60000 },
    );
});

function setImage(image: File | null) {
    if (image && !image.type.startsWith('image/')) {
        imageInputError.value = 'Choose an image file to scan.';
        return;
    }

    imageInputError.value = null;
    form.image = image;

    if (currentImageObjectUrl) {
        URL.revokeObjectURL(currentImageObjectUrl);
        currentImageObjectUrl = null;
    }

    imagePreviewUrl.value = null;
    if (image) {
        currentImageObjectUrl = URL.createObjectURL(image);
        imagePreviewUrl.value = currentImageObjectUrl;
    }
}

function handleImage(event: Event) {
    const target = event.target as HTMLInputElement;
    setImage(target.files?.[0] ?? null);
}

function handleDragEnter(event: DragEvent) {
    if (!event.dataTransfer?.types.includes('Files')) return;
    dragDepth++;
    isDraggingPhoto.value = true;
}

function handleDragLeave() {
    dragDepth = Math.max(0, dragDepth - 1);
    if (dragDepth === 0) {
        isDraggingPhoto.value = false;
    }
}

function handleDrop(event: DragEvent) {
    dragDepth = 0;
    isDraggingPhoto.value = false;
    setImage(event.dataTransfer?.files[0] ?? null);
}

function submit() {
    form.post(store().url, { forceFormData: true });
}

onBeforeUnmount(() => {
    if (currentImageObjectUrl) {
        URL.revokeObjectURL(currentImageObjectUrl);
    }
});
</script>

<template>
    <Head title="New Scan" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm">
                <Link
                    :href="scansIndex().url"
                    class="font-medium text-[#14391f] hover:underline"
                >
                    Scans
                </Link>
                <span class="px-2 text-gray-400">/</span>
                <span class="text-gray-500">New scan</span>
            </nav>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]"
            >
                <section class="rounded-2xl bg-white p-6 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-[#14391f]">
                            Scan photo
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Choose a clear photo of a rice leaf.
                        </p>
                    </div>

                    <label
                        for="image"
                        class="mt-5 flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 text-center transition"
                        :class="
                            isDraggingPhoto
                                ? 'border-[#14391f] bg-[#FBF8EA] ring-4 ring-[#FBEB9A]/60'
                                : 'border-gray-300 bg-[#FBF8EA] hover:border-[#14391f] hover:bg-[#FBEB9A]/20'
                        "
                        @dragenter.prevent="handleDragEnter"
                        @dragover.prevent
                        @dragleave.prevent="handleDragLeave"
                        @drop.prevent="handleDrop"
                    >
                        <img
                            v-if="imagePreviewUrl"
                            :src="imagePreviewUrl"
                            alt="Selected scan photo preview"
                            class="max-h-64 w-full rounded-md object-contain"
                        />
                        <template v-else>
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-[#FBEB9A] text-xl font-semibold text-[#14391f]"
                                aria-hidden="true"
                            >
                                +
                            </span>
                            <span
                                class="mt-4 text-sm font-semibold text-[#14391f]"
                            >
                                Choose a photo
                            </span>
                            <span class="mt-1 text-xs text-gray-500">
                                Drop an image here or click to browse · up to 5
                                MB
                            </span>
                        </template>
                    </label>
                    <input
                        id="image"
                        type="file"
                        accept="image/*"
                        class="sr-only"
                        @change="handleImage"
                    />
                    <p
                        v-if="imageInputError || form.errors.image"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ imageInputError ?? form.errors.image }}
                    </p>
                </section>

                <form
                    class="space-y-6 rounded-2xl bg-white p-6 shadow-sm"
                    @submit.prevent="submit"
                >
                    <div>
                        <h3 class="text-base font-semibold text-[#14391f]">
                            Scan details
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Upload a leaf photo and select the farmer.
                        </p>
                    </div>

                    <template v-if="requiresFarmer">
                        <div>
                            <label
                                for="farmer_id"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Farmer
                            </label>
                            <select
                                id="farmer_id"
                                v-model="form.farmer_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                            >
                                <option :value="null" disabled>
                                    Select farmer
                                </option>
                                <option
                                    v-for="farmer in farmers"
                                    :key="farmer.id"
                                    :value="farmer.id"
                                >
                                    {{ farmer.full_name }} ({{
                                        farmer.barangay
                                    }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.farmer_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.farmer_id }}
                            </p>
                        </div>
                    </template>

                    <div
                        class="flex items-center justify-end border-t border-gray-100 pt-5"
                    >
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ form.processing ? 'Analyzing…' : 'Submit scan' }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
