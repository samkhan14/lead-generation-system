<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    leads: {
        type: Object,
        required: true,
    },
});

const { can } = useAuth();
</script>

<template>
    <Head title="Leads" />

    <AdminLayout>
        <template #header>
            <div class="flex w-full items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-slate-900">Leads</h1>
                <Link v-if="can('leads.create')" :href="route('leads.create')">
                    <PrimaryButton>New Lead</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Assigned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm">
                                <Link :href="route('leads.show', lead.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ lead.full_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.email || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.company || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.source || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <span v-if="lead.latest_score">
                                    {{ lead.latest_score.score }} ({{ lead.latest_score.score_grade }})
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.assigned_to || '—' }}</td>
                        </tr>
                        <tr v-if="leads.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No leads yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
