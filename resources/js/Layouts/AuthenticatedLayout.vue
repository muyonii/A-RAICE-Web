<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { index as alertsIndex } from '@/actions/App/Http/Controllers/AlertController';
import { index as encyclopediaIndex } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { index as mapIndex } from '@/actions/App/Http/Controllers/MapController';
import { index as outbreaksIndex } from '@/actions/App/Http/Controllers/OutbreakController';
import { edit as profileEdit } from '@/actions/App/Http/Controllers/ProfileController';
import { index as reportsIndex } from '@/actions/App/Http/Controllers/ReportController';
import {
    create as scansCreate,
    index as scansIndex,
} from '@/actions/App/Http/Controllers/ScanController';
import { destroy as logout } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';

const showingNavigationDropdown = ref(false);
const page = usePage();

function isCurrent(path: string, includeChildren = false): boolean {
    const currentPath =
        page.url.replace(/[?#].*$/, '').replace(/\/+$/, '') || '/';
    const targetPath = path.replace(/[?#].*$/, '').replace(/\/+$/, '') || '/';

    return includeChildren
        ? currentPath === targetPath || currentPath.startsWith(`${targetPath}/`)
        : currentPath === targetPath;
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-[#F4F2EA]">
            <div class="bg-[#14391f] pb-6 print:hidden">
                <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
                    <nav class="rounded-2xl bg-white shadow-sm">
                        <!-- Primary Navigation Menu -->
                        <div
                            class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6"
                        >
                            <div class="flex">
                                <!-- Logo -->
                                <div class="flex shrink-0 items-center gap-2">
                                    <Link :href="dashboard().url">
                                        <ApplicationLogo
                                            class="block h-8 w-auto fill-current text-[#14391f]"
                                        />
                                    </Link>
                                    <Link
                                        :href="dashboard().url"
                                        class="text-lg font-bold text-[#14391f]"
                                    >
                                        A-RAICE
                                    </Link>
                                </div>

                                <!-- Navigation Links -->
                                <div
                                    class="hidden flex-wrap items-center gap-1 sm:ms-6 sm:flex"
                                >
                                    <NavLink
                                        :href="dashboard().url"
                                        :active="isCurrent(dashboard().url)"
                                    >
                                        Dashboard
                                    </NavLink>
                                    <NavLink
                                        :href="scansIndex().url"
                                        :active="isCurrent(scansIndex().url)"
                                    >
                                        Scans
                                    </NavLink>
                                    <NavLink
                                        :href="scansCreate().url"
                                        :active="isCurrent(scansCreate().url)"
                                    >
                                        New Scan
                                    </NavLink>
                                    <NavLink
                                        v-if="
                                            $page.props.auth.user.role !==
                                            'farmer'
                                        "
                                        :href="reportsIndex().url"
                                        :active="
                                            isCurrent(reportsIndex().url, true)
                                        "
                                    >
                                        Reports
                                    </NavLink>
                                    <NavLink
                                        v-if="
                                            $page.props.auth.user.role !==
                                            'farmer'
                                        "
                                        :href="outbreaksIndex().url"
                                        :active="
                                            isCurrent(
                                                outbreaksIndex().url,
                                                true,
                                            )
                                        "
                                    >
                                        Outbreaks
                                    </NavLink>
                                    <NavLink
                                        :href="alertsIndex().url"
                                        :active="
                                            isCurrent(alertsIndex().url, true)
                                        "
                                    >
                                        Alerts
                                        <span
                                            v-if="
                                                $page.props
                                                    .unread_alerts_count > 0
                                            "
                                            class="ms-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] leading-none text-white"
                                        >
                                            {{
                                                $page.props.unread_alerts_count
                                            }}
                                        </span>
                                    </NavLink>
                                    <NavLink
                                        :href="mapIndex().url"
                                        :active="isCurrent(mapIndex().url)"
                                    >
                                        Map
                                    </NavLink>
                                    <NavLink
                                        :href="encyclopediaIndex().url"
                                        :active="
                                            isCurrent(encyclopediaIndex().url)
                                        "
                                    >
                                        Encyclopedia
                                    </NavLink>
                                </div>
                            </div>

                            <div class="hidden sm:ms-3 sm:flex sm:items-center">
                                <!-- Settings Dropdown -->
                                <div class="relative ms-3">
                                    <Dropdown
                                        align="right"
                                        width="48"
                                        content-classes="rounded-xl bg-white p-1"
                                    >
                                        <template #trigger>
                                            <span
                                                class="inline-flex rounded-full"
                                            >
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm leading-4 font-medium text-gray-700 transition duration-150 ease-in-out hover:border-[#14391f]/20 hover:text-[#14391f] focus:outline-none"
                                                >
                                                    {{
                                                        $page.props.auth.user
                                                            .name
                                                    }}

                                                    <svg
                                                        class="ms-2 -me-0.5 h-4 w-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <DropdownLink
                                                :href="profileEdit().url"
                                            >
                                                Profile
                                            </DropdownLink>
                                            <DropdownLink
                                                :href="logout().url"
                                                method="post"
                                                as="button"
                                            >
                                                Log Out
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Hamburger -->
                            <div class="-me-2 flex items-center sm:hidden">
                                <button
                                    @click="
                                        showingNavigationDropdown =
                                            !showingNavigationDropdown
                                    "
                                    class="inline-flex items-center justify-center rounded-full p-2 text-gray-500 transition duration-150 ease-in-out hover:bg-[#FBF8EA] hover:text-[#14391f] focus:bg-[#FBF8EA] focus:text-[#14391f] focus:outline-none"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            :class="{
                                                hidden: showingNavigationDropdown,
                                                'inline-flex':
                                                    !showingNavigationDropdown,
                                            }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            :class="{
                                                hidden: !showingNavigationDropdown,
                                                'inline-flex':
                                                    showingNavigationDropdown,
                                            }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Responsive Navigation Menu -->
                        <div
                            :class="{
                                block: showingNavigationDropdown,
                                hidden: !showingNavigationDropdown,
                            }"
                            class="rounded-b-2xl px-2 pb-3 sm:hidden"
                        >
                            <div
                                class="space-y-1 border-t border-gray-100 pt-3"
                            >
                                <ResponsiveNavLink
                                    :href="dashboard().url"
                                    :active="isCurrent(dashboard().url)"
                                >
                                    Dashboard
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="scansIndex().url"
                                    :active="isCurrent(scansIndex().url)"
                                >
                                    Scans
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="scansCreate().url"
                                    :active="isCurrent(scansCreate().url)"
                                >
                                    New Scan
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="
                                        $page.props.auth.user.role !== 'farmer'
                                    "
                                    :href="reportsIndex().url"
                                    :active="
                                        isCurrent(reportsIndex().url, true)
                                    "
                                >
                                    Reports
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="
                                        $page.props.auth.user.role !== 'farmer'
                                    "
                                    :href="outbreaksIndex().url"
                                    :active="
                                        isCurrent(outbreaksIndex().url, true)
                                    "
                                >
                                    Outbreaks
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="alertsIndex().url"
                                    :active="isCurrent(alertsIndex().url, true)"
                                >
                                    Alerts
                                    <span
                                        v-if="
                                            $page.props.unread_alerts_count > 0
                                        "
                                        class="ms-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] leading-none text-white"
                                    >
                                        {{ $page.props.unread_alerts_count }}
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="mapIndex().url"
                                    :active="isCurrent(mapIndex().url)"
                                >
                                    Map
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="encyclopediaIndex().url"
                                    :active="isCurrent(encyclopediaIndex().url)"
                                >
                                    Encyclopedia
                                </ResponsiveNavLink>
                            </div>

                            <!-- Responsive Settings Options -->
                            <div
                                class="mt-2 border-t border-gray-100 pt-4 pb-1"
                            >
                                <div class="px-4">
                                    <div
                                        class="text-base font-medium text-[#14391f]"
                                    >
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>

                                <div class="mt-3 space-y-1">
                                    <ResponsiveNavLink
                                        :href="profileEdit().url"
                                    >
                                        Profile
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        :href="logout().url"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </ResponsiveNavLink>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
