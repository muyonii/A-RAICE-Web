<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { index as encyclopediaIndex } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { register } from '@/routes';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="A-RAICE" />

    <PublicLayout landing>
        <section
            class="relative grid flex-1 grid-cols-1 grid-rows-[auto_auto_1fr_auto] gap-12 py-10 sm:py-12 lg:grid-cols-2 lg:grid-rows-[1fr_auto] lg:gap-x-10 lg:gap-y-8 lg:py-16"
        >
            <h1
                class="relative z-30 text-5xl leading-[0.9] font-bold tracking-tight text-[#FBEB9A] sm:text-7xl lg:text-8xl"
            >
                Rooted in Bataan
            </h1>

            <div
                class="relative z-30 flex flex-col items-end gap-6 text-right lg:justify-self-end"
            >
                <div>
                    <div
                        class="text-6xl leading-[0.9] font-bold text-[#FBEB9A]"
                    >
                        12
                    </div>
                    <div class="mt-2 text-sm text-white">
                        Municipalities monitored
                    </div>
                </div>
                <div>
                    <div
                        class="text-6xl leading-[0.9] font-bold text-[#FBEB9A]"
                    >
                        13
                    </div>
                    <div class="mt-2 text-sm text-white">
                        Rice diseases covered
                    </div>
                </div>
            </div>

            <div class="relative z-30 flex flex-col justify-end gap-6">
                <p class="max-w-xl text-lg leading-relaxed text-white/90">
                    AI-assisted rice disease detection and outbreak monitoring
                    for Bataan farmers and LGUs
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Link
                        :href="encyclopediaIndex().url"
                        class="flex items-center gap-4 rounded-2xl border border-white/20 bg-white/10 p-5 text-white backdrop-blur transition hover:bg-white/15"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            class="h-6 w-6 shrink-0"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 7.5v13m0-13C10.8 6.5 8.7 6 6.5 6S2 6.5 2 7.5v12c0-1 2.3-1.5 4.5-1.5s4.3.5 5.5 1.5m0-12C13.2 6.5 15.3 6 17.5 6S22 6.5 22 7.5v12c0-1-2.3-1.5-4.5-1.5s-4.3.5-5.5 1.5"
                            />
                        </svg>
                        <span
                            class="h-10 border-l border-white/20"
                            aria-hidden="true"
                        ></span>
                        <span class="text-lg font-semibold"
                            >Disease Encyclopedia</span
                        >
                    </Link>
                    <Link
                        :href="user ? '/dashboard' : register().url"
                        class="flex items-center gap-4 rounded-2xl border border-white/20 bg-white/10 p-5 text-white backdrop-blur transition hover:bg-white/15"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            class="h-6 w-6 shrink-0"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 12h14m-6-6 6 6-6 6"
                            />
                        </svg>
                        <span
                            class="h-10 border-l border-white/20"
                            aria-hidden="true"
                        ></span>
                        <span class="text-lg font-semibold">
                            {{ user ? 'Go to Dashboard' : 'Get Started' }}
                        </span>
                    </Link>
                </div>
            </div>

            <h2
                class="relative z-30 self-end text-right text-5xl leading-[0.9] font-bold tracking-tight whitespace-nowrap text-white sm:text-7xl lg:text-8xl"
            >
                Driven by AI
            </h2>
        </section>
    </PublicLayout>
</template>
