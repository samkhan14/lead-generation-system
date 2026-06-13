<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
import IntelligenceScoreCard from '@/Components/Admin/IntelligenceScoreCard.vue';
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

const intelligence = () => props.lead.latest_score?.factors ?? {};
const priorityClasses = {
    high: 'bg-red-50 text-red-700 ring-red-100',
    medium: 'bg-amber-50 text-amber-700 ring-amber-100',
    low: 'bg-slate-50 text-slate-700 ring-slate-100',
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
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Lead intelligence</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Engine {{ lead.latest_score.scoring_version ?? 'current' }} — weighted final score
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-slate-900">{{ lead.latest_score.score }}</div>
                        <div class="text-sm text-slate-500">Grade {{ lead.latest_score.score_grade }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <IntelligenceScoreCard
                        title="Intent"
                        :score="lead.latest_score.intent_score ?? intelligence().intent?.score ?? 0"
                        :signals="intelligence().intent?.signals ?? []"
                        accent="indigo"
                    />
                    <IntelligenceScoreCard
                        title="Opportunity"
                        :score="lead.latest_score.opportunity_score ?? intelligence().opportunity?.score ?? 0"
                        :signals="intelligence().opportunity?.signals ?? []"
                        accent="emerald"
                    />
                    <IntelligenceScoreCard
                        title="Authenticity"
                        :score="lead.latest_score.authenticity_score ?? intelligence().authenticity?.score ?? 0"
                        :signals="intelligence().authenticity?.signals ?? []"
                        accent="sky"
                    />
                </div>

                <div v-if="intelligence().final?.weights" class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                    Final = (Intent × {{ intelligence().final.weights.intent }})
                    + (Opportunity × {{ intelligence().final.weights.opportunity }})
                    + (Authenticity × {{ intelligence().final.weights.authenticity }})
                </div>
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
                    <div v-if="lead.metadata?.address">
                        <dt class="text-sm text-slate-500">Address</dt>
                        <dd class="text-sm text-slate-900">{{ lead.metadata.address }}</dd>
                    </div>
                    <div v-if="lead.metadata?.rating">
                        <dt class="text-sm text-slate-500">Google rating</dt>
                        <dd class="text-sm text-slate-900">
                            {{ lead.metadata.rating }}
                            <span v-if="lead.metadata.review_count">({{ lead.metadata.review_count }} reviews)</span>
                        </dd>
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
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">What to pitch</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Suggested services based on lead gaps, Google profile signals, and available contact details.
                        </p>
                    </div>
                    <span
                        v-if="lead.latest_score?.temperature"
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase text-slate-600"
                    >
                        {{ lead.latest_score.temperature }} lead
                    </span>
                </div>

                <div v-if="lead.pitch_recommendations?.length" class="mt-5 grid gap-4">
                    <div
                        v-for="recommendation in lead.pitch_recommendations"
                        :key="recommendation.service"
                        class="rounded-lg border border-slate-200 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-slate-900">{{ recommendation.service }}</h4>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1"
                                :class="priorityClasses[recommendation.priority] ?? priorityClasses.low"
                            >
                                {{ recommendation.priority }} priority
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ recommendation.reason }}</p>
                        <div class="mt-3 rounded-lg bg-slate-50 p-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Suggested opener
                            </div>
                            <p class="mt-1 text-sm text-slate-700">{{ recommendation.opener }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No pitch recommendation available yet. Add contact, website, or Google profile data to improve suggestions.
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-medium text-slate-900">Score history</h3>
                <div v-if="lead.scores.length === 0" class="mt-4 text-sm text-slate-500">
                    No scores recorded yet.
                </div>
                <ul v-else class="mt-4 divide-y divide-slate-200">
                    <li v-for="score in lead.scores" :key="score.id" class="py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-slate-900">
                                    {{ score.score }} ({{ score.score_grade }})
                                </span>
                                <TemperatureBadge :temperature="score.temperature" />
                            </div>
                            <span class="text-sm text-slate-500">{{ score.calculated_at }}</span>
                        </div>
                        <div v-if="score.intent_score !== undefined" class="mt-2 flex gap-3 text-xs text-slate-500">
                            <span>Intent {{ score.intent_score }}</span>
                            <span>Opportunity {{ score.opportunity_score }}</span>
                            <span>Authenticity {{ score.authenticity_score }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
