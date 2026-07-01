<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    requestTypeOptions: { type: Array, default: () => [] },
    employeeOptions: { type: Array, default: () => [] },
});

const page = usePage();

const local = ref({
    q: page.props.filters?.q ?? '',
    status: page.props.filters?.status ?? '',
    ai_employee_id: page.props.filters?.ai_employee_id ?? '',
});

const reload = () => {
    router.get(route('admin.ai.logs.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
        ai_employee_id: local.value.ai_employee_id || undefined,
    }, { preserveState: true, replace: true });
};

const formatCost = (value) => value != null ? `$${Number(value).toFixed(4)}` : '—';
</script>

<template>
    <Head title="AI Logs" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-slate-900">AI Logs</h1>
                <p class="text-sm text-slate-500">Read-only audit trail of LLM requests</p>
            </div>
        </template>

        <DataTable title="Interaction log" :is-empty="!logs.data.length" empty-message="No AI interactions logged yet.">
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="text-xs font-medium text-slate-500">Search UUID / error</label>
                        <input v-model="local.q" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @keyup.enter="reload" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Employee</label>
                        <select v-model="local.ai_employee_id" class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in employeeOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                    <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tokens</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Cost</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm text-slate-500">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ log.employee?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ log.model?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ log.total_tokens ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ formatCost(log.cost_usd) }}</td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ log.status }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.ai.logs.show', log.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="logs.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-6">
                    <p class="text-sm text-slate-500">Showing {{ logs.from ?? 0 }}–{{ logs.to ?? 0 }} of {{ logs.total }}</p>
                    <div class="flex flex-wrap gap-1">
                        <Link v-for="link in logs.links" :key="link.label" :href="link.url || '#'" class="rounded px-3 py-1 text-sm" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" />
                    </div>
                </div>
            </template>
        </DataTable>
    </AdminLayout>
</template>
