<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store as registerUser } from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { login } from '@/routes';

defineProps<{
    municipalities: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    barangay: '',
    municipality: '',
    contact_number: '',
    farm_lat: '',
    farm_long: '',
});

const locating = ref(false);
const locationError = ref('');

function useMyLocation() {
    if (!navigator.geolocation) {
        locationError.value = 'Location is not supported on this device.';
        return;
    }

    locating.value = true;
    locationError.value = '';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.farm_lat = position.coords.latitude.toFixed(7);
            form.farm_long = position.coords.longitude.toFixed(7);
            locating.value = false;
        },
        () => {
            locationError.value =
                'Could not get your location. Enter it manually.';
            locating.value = false;
        },
        { timeout: 10000, maximumAge: 60000 },
    );
}

const submit = () => {
    form.post(registerUser().url, {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout class="max-w-2xl">
        <Head title="Register" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-white">
                Create your account
            </h1>
            <p class="mt-1 text-sm text-white/75">
                Join A-RAICE to connect your farm with local resources.
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="barangay" value="Barangay" />
                    <TextInput
                        id="barangay"
                        v-model="form.barangay"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.barangay" />
                </div>

                <div>
                    <InputLabel for="municipality" value="Municipality" />
                    <select
                        id="municipality"
                        v-model="form.municipality"
                        class="mt-1 block w-full rounded-md border-gray-300"
                        required
                    >
                        <option value="" disabled>Select municipality</option>
                        <option v-for="m in municipalities" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.municipality"
                    />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="contact_number" value="Contact number" />
                <TextInput
                    id="contact_number"
                    v-model="form.contact_number"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.contact_number"
                />
            </div>

            <div class="mt-4">
                <button
                    type="button"
                    class="rounded-full border border-white/40 bg-white/5 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/15 focus:ring-2 focus:ring-[#FBEB9A] focus:outline-none disabled:opacity-50"
                    :disabled="locating"
                    @click="useMyLocation()"
                >
                    {{
                        locating
                            ? 'Getting location...'
                            : 'Use my current location'
                    }}
                </button>
                <p v-if="locationError" class="mt-2 text-sm text-red-200">
                    {{ locationError }}
                </p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="farm_lat" value="Farm latitude" />
                    <TextInput
                        id="farm_lat"
                        v-model="form.farm_lat"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.farm_lat" />
                </div>

                <div>
                    <InputLabel for="farm_long" value="Farm longitude" />
                    <TextInput
                        id="farm_long"
                        v-model="form.farm_long"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.farm_long" />
                </div>
            </div>

            <div class="mt-6">
                <Link
                    :href="login().url"
                    class="block rounded-md text-sm font-medium text-white/85 underline underline-offset-4 transition hover:text-[#FBEB9A] focus:ring-2 focus:ring-[#FBEB9A] focus:outline-none"
                >
                    Already registered?
                </Link>
            </div>

            <div class="mt-4">
                <PrimaryButton
                    variant="auth"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
