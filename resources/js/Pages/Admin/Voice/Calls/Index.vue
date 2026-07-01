<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    calls: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Voice Calls" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Voice Calls</h1>
                <p class="text-sm text-slate-500">Outbound and inbound call history</p>
            </div>
        </template>

        <DataTable title="Calls" :is-empty="!calls.data.length" empty-message="No voice calls logged yet.">
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Lead</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">To</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="call in calls.data" :key="call.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm text-slate-500">{{ call.created_at ? new Date(call.created_at).toLocaleString() : '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ call.lead?.full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ call.employee?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ call.to_number ?? '—' }}</td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ call.status?.replace('_', ' ') }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.voice.calls.show', call.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
        </DataTable>
    </AdminLayout>
</template>
