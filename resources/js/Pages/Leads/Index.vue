<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    leads: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Leads" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Leads
                </h2>
                <Link :href="route('leads.create')">
                    <PrimaryButton>New Lead</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Name
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Email
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Company
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Source
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Score
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Assigned
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="lead in leads.data"
                                    :key="lead.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 text-sm">
                                        <Link
                                            :href="route('leads.show', lead.id)"
                                            class="font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ lead.full_name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ lead.email || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ lead.company || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ lead.source || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <span v-if="lead.latest_score">
                                            {{ lead.latest_score.score }}
                                            ({{ lead.latest_score.score_grade }})
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ lead.assigned_to || '—' }}
                                    </td>
                                </tr>
                                <tr v-if="leads.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No leads yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
