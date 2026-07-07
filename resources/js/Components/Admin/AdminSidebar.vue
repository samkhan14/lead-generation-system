<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarLink from '@/Components/Admin/SidebarLink.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    can: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
            <Link :href="route('dashboard')" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <ApplicationLogo class="h-auto w-auto" style="height: 24px; width: auto;" />
                </span>
                <span class="app-brand-text demo menu-text fw-bold ms-2">SalesIntel</span>
            </Link>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ri-menu-4-line d-block d-xl-none" />
                <i class="ri-arrow-left-s-line d-none d-xl-block align-middle" />
            </a>
        </div>

        <div class="menu-inner-shadow" />

        <ul class="menu-inner py-1">
            <SidebarLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
                icon="ri-home-smile-line"
            >
                Dashboard
            </SidebarLink>

            <SidebarLink
                v-if="can('leads.view')"
                :href="route('leads.index')"
                :active="route().current('leads.*')"
                icon="ri-user-search-line"
            >
                Leads
            </SidebarLink>

            <SidebarLink
                v-if="can('leads.view')"
                :href="route('leads.pipeline')"
                :active="route().current('leads.pipeline')"
                icon="ri-kanban-view"
            >
                Pipeline
            </SidebarLink>

            <SidebarLink
                v-if="can('crm.tasks.view')"
                :href="route('tasks.index')"
                :active="route().current('tasks.*')"
                icon="ri-checkbox-line"
            >
                Tasks
            </SidebarLink>

            <SidebarLink
                v-if="can('scraper.view')"
                :href="route('scraper.index')"
                :active="route().current('scraper.*')"
                icon="ri-search-eye-line"
            >
                Scraper
            </SidebarLink>

            <SidebarLink
                v-if="can('services.view')"
                :href="route('admin.services.index')"
                :active="route().current('admin.services.*')"
                icon="ri-briefcase-line"
            >
                Services
            </SidebarLink>

            <li
                v-if="can('ai.providers.view') || can('ai.models.view') || can('ai.employees.view') || can('ai.prompts.view') || can('ai.knowledge.view') || can('ai.logs.view')"
                class="menu-header small text-uppercase"
            >
                <span class="menu-header-text">AI Platform</span>
            </li>

            <SidebarLink
                v-if="can('ai.providers.view')"
                :href="route('admin.ai.providers.index')"
                :active="route().current('admin.ai.providers.*')"
                icon="ri-plug-line"
            >
                Providers
            </SidebarLink>

            <SidebarLink
                v-if="can('ai.models.view')"
                :href="route('admin.ai.models.index')"
                :active="route().current('admin.ai.models.*')"
                icon="ri-cpu-line"
            >
                Models
            </SidebarLink>

            <SidebarLink
                v-if="can('ai.employees.view')"
                :href="route('admin.ai.employees.index')"
                :active="route().current('admin.ai.employees.*')"
                icon="ri-robot-2-line"
            >
                Employees
            </SidebarLink>

            <SidebarLink
                v-if="can('ai.prompts.view')"
                :href="route('admin.ai.prompts.index')"
                :active="route().current('admin.ai.prompts.*')"
                icon="ri-file-text-line"
            >
                Prompts
            </SidebarLink>

            <SidebarLink
                v-if="can('ai.knowledge.view')"
                :href="route('admin.ai.knowledge.index')"
                :active="route().current('admin.ai.knowledge.*')"
                icon="ri-book-2-line"
            >
                Knowledge
            </SidebarLink>

            <SidebarLink
                v-if="can('ai.logs.view')"
                :href="route('admin.ai.logs.index')"
                :active="route().current('admin.ai.logs.*')"
                icon="ri-file-list-3-line"
            >
                AI Logs
            </SidebarLink>

            <li
                v-if="can('voice.providers.view') || can('voice.calls.view')"
                class="menu-header small text-uppercase"
            >
                <span class="menu-header-text">Voice</span>
            </li>

            <SidebarLink
                v-if="can('voice.providers.view')"
                :href="route('admin.voice.providers.index')"
                :active="route().current('admin.voice.providers.*')"
                icon="ri-phone-line"
            >
                Voice Providers
            </SidebarLink>

            <SidebarLink
                v-if="can('voice.calls.view')"
                :href="route('admin.voice.calls.index')"
                :active="route().current('admin.voice.calls.*')"
                icon="ri-speak-line"
            >
                Voice Calls
            </SidebarLink>

            <li
                v-if="can('email.providers.view') || can('email.campaigns.view') || can('email.sends.view')"
                class="menu-header small text-uppercase"
            >
                <span class="menu-header-text">Email</span>
            </li>

            <SidebarLink
                v-if="can('email.providers.view')"
                :href="route('admin.email.providers.index')"
                :active="route().current('admin.email.providers.*')"
                icon="ri-mail-settings-line"
            >
                Email Providers
            </SidebarLink>

            <SidebarLink
                v-if="can('email.campaigns.view')"
                :href="route('admin.email.campaigns.index')"
                :active="route().current('admin.email.campaigns.*')"
                icon="ri-megaphone-line"
            >
                Campaigns
            </SidebarLink>

            <SidebarLink
                v-if="can('email.sends.view')"
                :href="route('admin.email.sends.index')"
                :active="route().current('admin.email.sends.*')"
                icon="ri-send-plane-line"
            >
                Email Sends
            </SidebarLink>
        </ul>
    </aside>
</template>
