<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    leads: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ temperature: null }),
    },
});

const { can } = useAuth();

const setFilter = (temperature) => {
    router.get(
        route('leads.index'),
        temperature ? { temperature } : {},
        { preserveState: true, replace: true },
    );
};

const isActive = (temperature) => props.filters.temperature === temperature;
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

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                :class="!filters.temperature ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                @click="setFilter(null)"
            >
                All
            </button>
            <button
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                :class="isActive('hot') ? 'bg-red-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                @click="setFilter('hot')"
            >
                HOT
            </button>
            <button
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                :class="isActive('warm') ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                @click="setFilter('warm')"
            >
                WARM
            </button>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Temp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm">
                                <Link :href="route('leads.show', lead.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ lead.full_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div>{{ lead.email || '—' }}</div>
                                <div v-if="lead.phone" class="text-xs text-slate-500">{{ lead.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.company || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.source || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <span v-if="lead.latest_score">
                                    {{ lead.latest_score.score }} ({{ lead.latest_score.score_grade }})
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                            </td>
                        </tr>
                        <tr v-if="leads.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                No leads match this filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
