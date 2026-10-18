<script setup lang="ts">
import {
    destroy as destroyTreatment,
    store as storeTreatment,
    update as updateTreatment,
} from '@/actions/App/Http/Controllers/Admin/TreatmentController';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Treatment, TreatmentType } from '@/types';

const props = defineProps<{
    diseaseId: number;
    treatments?: Treatment[];
}>();

const treatmentTypes: { value: TreatmentType; label: string }[] = [
    { value: 'chemical', label: 'Chemical' },
    { value: 'biological', label: 'Biological' },
    { value: 'cultural', label: 'Cultural' },
    { value: 'organic', label: 'Organic' },
];

const treatmentTypeClasses: Record<TreatmentType, string> = {
    chemical: 'bg-orange-100 text-orange-800',
    biological: 'bg-blue-100 text-blue-800',
    cultural: 'bg-green-100 text-green-800',
    organic: 'bg-yellow-100 text-yellow-800',
};

const addForm = useForm({
    title: '',
    description: '',
    type: '' as TreatmentType | '',
});

const editForm = useForm({
    title: '',
    description: '',
    type: '' as TreatmentType | '',
});

const deleteForm = useForm<Record<string, string>>({});
const editingTreatmentId = ref<number | null>(null);

function addTreatment() {
    addForm.submit(storeTreatment({ disease: props.diseaseId }), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

function startEditing(treatment: Treatment) {
    editingTreatmentId.value = treatment.id;
    editForm.title = treatment.title;
    editForm.description = treatment.description;
    editForm.type = treatment.type;
    editForm.clearErrors();
}

function cancelEditing() {
    editingTreatmentId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveTreatment() {
    const treatmentId = editingTreatmentId.value;
    if (treatmentId === null) {
        return;
    }

    editForm.submit(updateTreatment({ treatment: treatmentId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingTreatmentId.value = null;
            editForm.reset();
        },
    });
}

function removeTreatment(treatment: Treatment) {
    if (!window.confirm(`Delete the treatment "${treatment.title}"?`)) {
        return;
    }

    deleteForm.delete(destroyTreatment({ treatment: treatment.id }).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <section
        class="space-y-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8"
        aria-labelledby="treatments-heading"
    >
        <div>
            <h2
                id="treatments-heading"
                class="text-lg font-semibold text-[#14391f]"
            >
                Treatments
            </h2>
            <p v-if="!treatments?.length" class="mt-2 text-sm text-gray-500">
                No treatments have been added for this disease.
            </p>

            <ul v-else class="mt-4 divide-y divide-gray-100">
                <li
                    v-for="treatment in treatments"
                    :key="treatment.id"
                    class="py-4 first:pt-0 last:pb-0"
                >
                    <div
                        v-if="editingTreatmentId !== treatment.id"
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-medium text-gray-900">
                                    {{ treatment.title }}
                                </h3>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="
                                        treatmentTypeClasses[treatment.type]
                                    "
                                >
                                    {{
                                        treatmentTypes.find(
                                            (option) =>
                                                option.value === treatment.type,
                                        )?.label
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-2 text-sm whitespace-pre-line text-gray-700"
                            >
                                {{ treatment.description }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                class="rounded-full px-3 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#FBF8EA]"
                                @click="startEditing(treatment)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                :disabled="deleteForm.processing"
                                class="rounded-full px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="removeTreatment(treatment)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <form
                        v-else
                        class="space-y-4 rounded-2xl bg-[#FBF8EA] p-4"
                        @submit.prevent="saveTreatment"
                    >
                        <div>
                            <label
                                :for="`edit-treatment-title-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Title
                            </label>
                            <input
                                :id="`edit-treatment-title-${treatment.id}`"
                                v-model="editForm.title"
                                type="text"
                                required
                                maxlength="255"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                            />
                            <p
                                v-if="editForm.errors.title"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.title }}
                            </p>
                        </div>
                        <div>
                            <label
                                :for="`edit-treatment-description-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Description
                            </label>
                            <textarea
                                :id="`edit-treatment-description-${treatment.id}`"
                                v-model="editForm.description"
                                required
                                rows="3"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                            ></textarea>
                            <p
                                v-if="editForm.errors.description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.description }}
                            </p>
                        </div>
                        <div>
                            <label
                                :for="`edit-treatment-type-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Type
                            </label>
                            <select
                                :id="`edit-treatment-type-${treatment.id}`"
                                v-model="editForm.type"
                                required
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                            >
                                <option value="" disabled>
                                    Select treatment type
                                </option>
                                <option
                                    v-for="option in treatmentTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p
                                v-if="editForm.errors.type"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.type }}
                            </p>
                        </div>
                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-full px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-white"
                                @click="cancelEditing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Save
                            </button>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        <form
            class="space-y-4 border-t border-gray-100 pt-5"
            @submit.prevent="addTreatment"
        >
            <h3 class="font-medium text-[#14391f]">Add treatment</h3>
            <div>
                <label
                    for="new-treatment-title"
                    class="block text-sm font-medium text-gray-700"
                >
                    Title
                </label>
                <input
                    id="new-treatment-title"
                    v-model="addForm.title"
                    type="text"
                    required
                    maxlength="255"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                />
                <p
                    v-if="addForm.errors.title"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ addForm.errors.title }}
                </p>
            </div>
            <div>
                <label
                    for="new-treatment-description"
                    class="block text-sm font-medium text-gray-700"
                >
                    Description
                </label>
                <textarea
                    id="new-treatment-description"
                    v-model="addForm.description"
                    required
                    rows="3"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                ></textarea>
                <p
                    v-if="addForm.errors.description"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ addForm.errors.description }}
                </p>
            </div>
            <div>
                <label
                    for="new-treatment-type"
                    class="block text-sm font-medium text-gray-700"
                >
                    Type
                </label>
                <select
                    id="new-treatment-type"
                    v-model="addForm.type"
                    required
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                >
                    <option value="" disabled>Select treatment type</option>
                    <option
                        v-for="option in treatmentTypes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="addForm.errors.type" class="mt-1 text-sm text-red-600">
                    {{ addForm.errors.type }}
                </p>
            </div>
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="addForm.processing"
                    class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2.5 text-sm font-semibold text-[#FBEB9A] shadow-sm transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Add treatment
                </button>
            </div>
        </form>
    </section>
</template>
