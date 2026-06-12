<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_leads: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Total leads</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total_leads }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">With scores</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.leads_with_scores }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Added today</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.leads_today }}</p>
                </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-slate-900">Recent leads</h2>
                    <Link
                        :href="route('leads.index')"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        View all
                    </Link>
                </div>
                <div v-if="recent_leads.length === 0" class="px-6 py-8 text-center text-sm text-slate-500">
                    No leads yet.
                </div>
                <ul v-else class="divide-y divide-slate-200">
                    <li
                        v-for="lead in recent_leads"
                        :key="lead.id"
                        class="flex items-center justify-between px-6 py-4"
                    >
                        <div>
                            <Link
                                :href="route('leads.show', lead.id)"
                                class="font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                {{ lead.full_name }}
                            </Link>
                            <p class="text-sm text-slate-500">{{ lead.email || 'No email' }}</p>
                        </div>
                        <div class="text-right text-sm text-slate-500">
                            <span v-if="lead.latest_score !== null">Score: {{ lead.latest_score }}</span>
                            <span v-else>No score</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
