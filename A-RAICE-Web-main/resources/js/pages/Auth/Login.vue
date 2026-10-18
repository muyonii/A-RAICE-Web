<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store as authenticate } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { register } from '@/routes';
import { request as passwordRequest } from '@/routes/password';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(authenticate().url, {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout class="max-w-md">
        <Head title="Log in" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-white">
                Welcome back
            </h1>
            <p class="mt-1 text-sm text-white/75">
                Sign in to continue to your account.
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

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-white/80">Remember me</span>
                </label>
            </div>

            <div
                class="mt-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-3"
            >
                <Link
                    :href="register().url"
                    class="rounded-md text-sm font-medium text-white/85 underline underline-offset-4 transition hover:text-[#FBEB9A] focus:ring-2 focus:ring-[#FBEB9A] focus:outline-none"
                >
                    Register
                </Link>

                <Link
                    v-if="canResetPassword"
                    :href="passwordRequest().url"
                    class="rounded-md text-sm font-medium text-white/85 underline underline-offset-4 transition hover:text-[#FBEB9A] focus:ring-2 focus:ring-[#FBEB9A] focus:outline-none"
                >
                    Forgot your password?
                </Link>
            </div>

            <div class="mt-5">
                <PrimaryButton
                    variant="auth"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
