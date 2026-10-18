<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store as confirmPassword } from '@/actions/App/Http/Controllers/Auth/ConfirmablePasswordController';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(confirmPassword().url, {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout class="max-w-md">
        <Head title="Confirm Password" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-white">
                Confirm password
            </h1>
            <p class="mt-2 text-sm leading-6 text-white/75">
                This is a secure area. Please confirm your password before
                continuing.
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    variant="auth"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirm
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
