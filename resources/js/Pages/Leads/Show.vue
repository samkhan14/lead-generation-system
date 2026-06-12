<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
});

const { can } = useAuth();

const deleteLead = () => {
    if (confirm('Delete this lead?')) {
        router.delete(route('leads.destroy', props.lead.id));
    }
};
</script>

<template>
    <Head :title="lead.full_name" />

    <AdminLayout>
        <template #header>
            <div class="flex w-full items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-slate-900">{{ lead.full_name }}</h1>
                <div class="flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>Back to Leads</SecondaryButton>
                    </Link>
                    <DangerButton v-if="can('leads.delete')" @click="deleteLead">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-medium text-slate-900">Lead details</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Email</dt>
                        <dd class="text-sm text-slate-900">{{ lead.email || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Phone</dt>
                        <dd class="text-sm text-slate-900">{{ lead.phone || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Company</dt>
                        <dd class="text-sm text-slate-900">{{ lead.company || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Job title</dt>
                        <dd class="text-sm text-slate-900">{{ lead.job_title || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Source</dt>
                        <dd class="text-sm text-slate-900">{{ lead.source || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd class="text-sm text-slate-900">{{ lead.status || 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Assigned to</dt>
                        <dd class="text-sm text-slate-900">{{ lead.assigned_to || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Created by</dt>
                        <dd class="text-sm text-slate-900">{{ lead.created_by || '—' }}</dd>
                    </div>
                </dl>
                <div v-if="lead.notes" class="mt-4">
                    <dt class="text-sm text-slate-500">Notes</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-900">{{ lead.notes }}</dd>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-medium text-slate-900">Score history</h3>
                <div v-if="lead.scores.length === 0" class="mt-4 text-sm text-slate-500">
                    No scores recorded yet.
                </div>
                <ul v-else class="mt-4 divide-y divide-slate-200">
                    <li v-for="score in lead.scores" :key="score.id" class="py-3">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-900">
                                {{ score.score }} ({{ score.score_grade }})
                            </span>
                            <span class="text-sm text-slate-500">{{ score.calculated_at }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
