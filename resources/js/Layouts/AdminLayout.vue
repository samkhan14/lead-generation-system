<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarLink from '@/Components/Admin/SidebarLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useAuth } from '@/composables/useAuth';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const sidebarOpen = ref(false);
const { user, can } = useAuth();
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 transition-transform duration-200 lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-4">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-white" />
                    <span class="text-sm font-semibold text-white">Lead CRM</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <SidebarLink
                    :href="route('dashboard')"
                    :active="route().current('dashboard')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </template>
                    Dashboard
                </SidebarLink>

                <SidebarLink
                    v-if="can('leads.view')"
                    :href="route('leads.index')"
                    :active="route().current('leads.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </template>
                    Leads
                </SidebarLink>

                <SidebarLink
                    v-if="can('scraper.view')"
                    :href="route('scraper.index')"
                    :active="route().current('scraper.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </template>
                    Scraper
                </SidebarLink>

                <SidebarLink
                    v-if="can('services.view')"
                    :href="route('admin.services.index')"
                    :active="route().current('admin.services.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </template>
                    Services
                </SidebarLink>

                <div v-if="can('ai.providers.view') || can('ai.models.view') || can('ai.employees.view') || can('ai.prompts.view') || can('ai.knowledge.view') || can('ai.logs.view')" class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">AI Platform</p>
                </div>

                <SidebarLink
                    v-if="can('ai.providers.view')"
                    :href="route('admin.ai.providers.index')"
                    :active="route().current('admin.ai.providers.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </template>
                    Providers
                </SidebarLink>

                <SidebarLink
                    v-if="can('ai.models.view')"
                    :href="route('admin.ai.models.index')"
                    :active="route().current('admin.ai.models.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                        </svg>
                    </template>
                    Models
                </SidebarLink>

                <SidebarLink
                    v-if="can('ai.employees.view')"
                    :href="route('admin.ai.employees.index')"
                    :active="route().current('admin.ai.employees.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </template>
                    Employees
                </SidebarLink>

                <SidebarLink
                    v-if="can('ai.prompts.view')"
                    :href="route('admin.ai.prompts.index')"
                    :active="route().current('admin.ai.prompts.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </template>
                    Prompts
                </SidebarLink>

                <SidebarLink
                    v-if="can('ai.knowledge.view')"
                    :href="route('admin.ai.knowledge.index')"
                    :active="route().current('admin.ai.knowledge.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </template>
                    Knowledge
                </SidebarLink>

                <SidebarLink
                    v-if="can('ai.logs.view')"
                    :href="route('admin.ai.logs.index')"
                    :active="route().current('admin.ai.logs.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </template>
                    AI Logs
                </SidebarLink>

                <div v-if="can('voice.providers.view') || can('voice.calls.view')" class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Voice</p>
                </div>

                <SidebarLink
                    v-if="can('voice.providers.view')"
                    :href="route('admin.voice.providers.index')"
                    :active="route().current('admin.voice.providers.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </template>
                    Voice Providers
                </SidebarLink>

                <SidebarLink
                    v-if="can('voice.calls.view')"
                    :href="route('admin.voice.calls.index')"
                    :active="route().current('admin.voice.calls.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6a9 9 0 010 12m-3.536-8.464a5 5 0 000 7.072" />
                        </svg>
                    </template>
                    Voice Calls
                </SidebarLink>

                <div v-if="can('email.providers.view') || can('email.campaigns.view') || can('email.sends.view')" class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p>
                </div>

                <SidebarLink
                    v-if="can('email.providers.view')"
                    :href="route('admin.email.providers.index')"
                    :active="route().current('admin.email.providers.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </template>
                    Email Providers
                </SidebarLink>

                <SidebarLink
                    v-if="can('email.campaigns.view')"
                    :href="route('admin.email.campaigns.index')"
                    :active="route().current('admin.email.campaigns.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </template>
                    Campaigns
                </SidebarLink>

                <SidebarLink
                    v-if="can('email.sends.view')"
                    :href="route('admin.email.sends.index')"
                    :active="route().current('admin.email.sends.*')"
                >
                    <template #icon>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </template>
                    Email Sends
                </SidebarLink>
            </nav>

            <div class="border-t border-slate-800 p-4">
                <div class="truncate text-sm font-medium text-white">{{ user?.name }}</div>
                <div class="truncate text-xs text-slate-400">{{ user?.email }}</div>
                <div v-if="user?.roles?.length" class="mt-2 flex flex-wrap gap-1">
                    <span
                        v-for="role in user.roles"
                        :key="role"
                        class="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-300"
                    >
                        {{ role.replace('_', ' ') }}
                    </span>
                </div>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div v-if="$slots.header">
                        <slot name="header" />
                    </div>
                </div>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                        >
                            {{ user?.name }}
                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                    </template>
                </Dropdown>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
