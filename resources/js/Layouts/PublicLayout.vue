<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const props = defineProps<{
    landing?: boolean;
}>();
</script>

<template>
    <div
        :class="
            props.landing
                ? 'landing relative isolate flex min-h-screen flex-col overflow-hidden bg-[#14391f] bg-gradient-to-br from-[#14391f] to-[#2f6b3a] lg:h-screen'
                : 'flex min-h-screen flex-col bg-[#F4F2EA]'
        "
    >
        <template v-if="props.landing">
            <img
                src="/images/hero-rice.jpg"
                alt=""
                loading="eager"
                class="absolute inset-0 z-0 h-full w-full scale-105 object-cover object-center"
            />
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-[#14391f]/50"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute inset-0 z-20 bg-[radial-gradient(circle_at_center,rgba(190,210,110,0.35),transparent_60%)]"
                aria-hidden="true"
            ></div>
        </template>

        <template v-if="props.landing">
            <div class="relative z-30 mx-4 pt-4">
                <nav class="rounded-2xl bg-white shadow-sm">
                    <div
                        class="grid h-16 grid-cols-3 items-center px-4 sm:px-6"
                    >
                        <Link
                            href="/"
                            class="flex items-center gap-2 text-lg font-bold text-[#14391f]"
                        >
                            <ApplicationLogo class="h-8 w-8" />
                            A-RAICE
                        </Link>

                        <Link
                            href="/encyclopedia"
                            class="justify-self-center text-sm font-medium text-gray-700 transition hover:text-[#14391f]"
                        >
                            Encyclopedia
                        </Link>

                        <div class="flex items-center gap-3 justify-self-end">
                            <Link
                                v-if="user"
                                href="/dashboard"
                                class="rounded-full bg-[#FBEB9A] px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#f5df7a]"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    href="/login"
                                    class="text-sm font-medium text-gray-700 transition hover:text-[#14391f]"
                                    >Login</Link
                                >
                                <Link
                                    href="/register"
                                    class="rounded-full bg-[#FBEB9A] px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#f5df7a]"
                                    >Register</Link
                                >
                            </template>
                        </div>
                    </div>
                </nav>
            </div>

            <main
                class="relative z-30 mx-auto flex w-full max-w-7xl flex-1 flex-col px-4"
            >
                <slot />
            </main>
        </template>

        <template v-else>
            <div class="bg-[#14391f] pb-6">
                <div
                    class="relative z-30 mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6"
                >
                    <nav class="rounded-2xl bg-white shadow-sm">
                        <div
                            class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6"
                        >
                            <Link
                                href="/"
                                class="flex items-center gap-2 text-lg font-bold text-[#14391f]"
                            >
                                <ApplicationLogo class="h-8 w-8" />
                                A-RAICE
                            </Link>
                            <Link
                                href="/encyclopedia"
                                class="ms-auto text-sm font-medium text-gray-700 transition hover:text-[#14391f]"
                            >
                                Encyclopedia
                            </Link>
                            <div class="flex items-center gap-3">
                                <Link
                                    v-if="user"
                                    href="/dashboard"
                                    class="rounded-full bg-[#FBEB9A] px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#f5df7a]"
                                >
                                    Dashboard
                                </Link>
                                <template v-else>
                                    <Link
                                        href="/login"
                                        class="text-sm font-medium text-gray-700 transition hover:text-[#14391f]"
                                    >
                                        Login
                                    </Link>
                                    <Link
                                        href="/register"
                                        class="rounded-full bg-[#FBEB9A] px-4 py-2 text-sm font-semibold text-[#14391f] transition hover:bg-[#f5df7a]"
                                    >
                                        Register
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <main class="mx-auto w-full max-w-7xl px-4 sm:px-6">
                <slot />
            </main>
        </template>
    </div>
</template>
