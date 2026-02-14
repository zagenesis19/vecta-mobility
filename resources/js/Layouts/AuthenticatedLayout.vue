<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink 
                                    v-if="$page.props.auth.user.role !== 'admin'"
                                    :href="route('trip.history')" 
                                    :active="route().current('trip.history')"
                                >
                                    📚 Historial
                                </NavLink>
                                    <NavLink 
                                        v-if="$page.props.auth.user.role === 'admin'"
                                        :href="route('admin.verifications')" 
                                        :active="route().current('admin.verifications')"
                                    >
                                        Verificaciones 🛡️
                                    </NavLink>
                                    <NavLink 
                                        v-if="$page.props.auth.user.role === 'admin'"
                                        :href="route('admin.analytics')" 
                                        :active="route().current('admin.analytics')"
                                    >
                                        Analíticas 📊
                                    </NavLink>
                                    <NavLink 
                                        v-if="$page.props.auth.user.role === 'admin'"
                                        :href="route('admin.users.index')" 
                                        :active="route().current('admin.users.index')"
                                    >
                                        Usuarios 👥
                                    </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
    <button
        type="button"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
    >
        <div v-if="$page.props.auth.user.profile_photo_path" class="mr-2">
            <img 
                :src="'/storage/' + $page.props.auth.user.profile_photo_path" 
                class="h-8 w-8 rounded-full object-cover border border-gray-300" 
                alt="Avatar"
            />
        </div>
        <div v-else class="mr-2">
             <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                 <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
             </svg>
        </div>

        <div class="flex flex-col items-start">
            <div class="flex items-center gap-1">
                <span class="font-medium">{{ $page.props.auth.user.name }}</span>
            </div>
            <!-- Calificación por estrellas -->
            <div 
                v-if="$page.props.auth.user.role === 'driver' || $page.props.auth.user.role === 'passenger'"
                class="flex items-center gap-1 text-xs"
            >
                <span class="text-yellow-500">★</span>
                <span class="font-bold text-gray-700">{{ $page.props.auth.user.average_rating > 0 ? $page.props.auth.user.average_rating : '5.0' }}</span>
                <span class="text-gray-500">({{ $page.props.auth.user.total_ratings }})</span>
            </div>
        </div>

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
                                        <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
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
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role !== 'admin'"
                            :href="route('trip.history')" 
                            :active="route().current('trip.history')"
                        >
                            📚 Historial
                        </ResponsiveNavLink>
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role === 'admin'"
                            :href="route('admin.analytics')" 
                            :active="route().current('admin.analytics')"
                        >
                            Analíticas 📊
                        </ResponsiveNavLink>
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role === 'admin'"
                            :href="route('admin.users.index')" 
                            :active="route().current('admin.users.index')"
                        >
                            Usuarios 👥
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                            <!-- Calificación por estrellas en móvil -->
                            <div 
                                v-if="$page.props.auth.user.role === 'driver' || $page.props.auth.user.role === 'passenger'"
                                class="flex items-center gap-1 text-sm mt-1"
                            >
                                <span class="text-yellow-500">★</span>
                                <span class="font-bold text-gray-700">{{ $page.props.auth.user.average_rating > 0 ? $page.props.auth.user.average_rating : '5.0' }}</span>
                                <span class="text-gray-500">({{ $page.props.auth.user.total_ratings }} calificaciones)</span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
