<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
});

const deleteLead = () => {
    if (confirm('Delete this lead?')) {
        router.delete(route('leads.destroy', props.lead.id));
    }
};
</script>

<template>
    <Head :title="lead.full_name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ lead.full_name }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>Back to Leads</SecondaryButton>
                    </Link>
                    <DangerButton @click="deleteLead">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Lead details</h3>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900">{{ lead.email || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Phone</dt>
                            <dd class="text-sm text-gray-900">{{ lead.phone || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Company</dt>
                            <dd class="text-sm text-gray-900">{{ lead.company || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Job title</dt>
                            <dd class="text-sm text-gray-900">{{ lead.job_title || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Source</dt>
                            <dd class="text-sm text-gray-900">{{ lead.source || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="text-sm text-gray-900">{{ lead.status || 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Assigned to</dt>
                            <dd class="text-sm text-gray-900">{{ lead.assigned_to || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created by</dt>
                            <dd class="text-sm text-gray-900">{{ lead.created_by || '—' }}</dd>
                        </div>
                    </dl>
                    <div v-if="lead.notes" class="mt-4">
                        <dt class="text-sm text-gray-500">Notes</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ lead.notes }}</dd>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Score history</h3>
                    <div v-if="lead.scores.length === 0" class="mt-4 text-sm text-gray-500">
                        No scores recorded yet.
                    </div>
                    <ul v-else class="mt-4 divide-y divide-gray-200">
                        <li
                            v-for="score in lead.scores"
                            :key="score.id"
                            class="py-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">
                                    {{ score.score }} ({{ score.score_grade }})
                                </span>
                                <span class="text-sm text-gray-500">
                                    {{ score.calculated_at }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
