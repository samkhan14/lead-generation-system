<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
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

const factorLabels = {
    completeness: 'Completeness',
    source_quality: 'Source quality',
    contact_richness: 'Contact richness',
};
</script>

<template>
    <Head :title="lead.full_name" />

    <AdminLayout>
        <template #header>
            <div class="flex w-full items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold text-slate-900">{{ lead.full_name }}</h1>
                    <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                </div>
                <div class="flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>Back to Leads</SecondaryButton>
                    </Link>
                    <DangerButton v-if="can('leads.delete')" @click="deleteLead">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <div v-if="lead.latest_score" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-medium text-slate-900">Lead score</h3>
                <div class="mt-2 flex items-baseline gap-3">
                    <span class="text-3xl font-bold text-slate-900">{{ lead.latest_score.score }}</span>
                    <span class="text-sm text-slate-500">Grade {{ lead.latest_score.score_grade }}</span>
                </div>
                <dl v-if="lead.latest_score.factors" class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div v-for="(value, key) in lead.latest_score.factors" :key="key" class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">{{ factorLabels[key] ?? key }}</dt>
                        <dd class="text-lg font-semibold text-slate-900">{{ value }}</dd>
                    </div>
                </dl>
            </div>

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
                        <dt class="text-sm text-slate-500">Website</dt>
                        <dd class="text-sm text-slate-900">{{ lead.website || '—' }}</dd>
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
                    <li v-for="score in lead.scores" :key="score.id" class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-slate-900">
                                {{ score.score }} ({{ score.score_grade }})
                            </span>
                            <TemperatureBadge :temperature="score.temperature" />
                        </div>
                        <span class="text-sm text-slate-500">{{ score.calculated_at }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
