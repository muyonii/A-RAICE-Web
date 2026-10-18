<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store as sendPasswordResetLink } from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(sendPasswordResetLink().url);
};
</script>

<template>
    <GuestLayout class="max-w-md">
        <Head title="Forgot Password" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-white">
                Forgot password?
            </h1>
            <p class="mt-2 text-sm leading-6 text-white/75">
                Enter your email and we’ll send you a link to reset your
                password.
            </p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-[#FBEB9A]">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    variant="auth"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Email Password Reset Link
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
