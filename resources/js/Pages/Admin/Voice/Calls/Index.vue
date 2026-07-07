<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    calls: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const statusClass = (status) => ({
    completed: 'bg-label-success',
    in_progress: 'bg-label-info',
    ringing: 'bg-label-info',
    queued: 'bg-label-secondary',
    pending: 'bg-label-primary',
    failed: 'bg-label-danger',
    cancelled: 'bg-label-secondary',
    no_answer: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="Voice Calls" />
    <AdminLayout>
        <template #header>
            <div>
                <h4 class="mb-0 fw-bold">Voice Calls</h4>
                <p class="mb-0 small text-muted">Outbound and inbound call history</p>
            </div>
        </template>

        <DataTable title="Calls" :is-empty="!calls.data.length" empty-message="No voice calls logged yet.">
            <template #head>
                <tr>
                    <th>Time</th>
                    <th>Lead</th>
                    <th>Employee</th>
                    <th>To</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="call in calls.data" :key="call.id">
                <td class="small text-muted text-nowrap">{{ call.created_at ? new Date(call.created_at).toLocaleString() : '—' }}</td>
                <td>{{ call.lead?.full_name ?? '—' }}</td>
                <td>{{ call.employee?.name ?? '—' }}</td>
                <td>{{ call.to_number ?? '—' }}</td>
                <td>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(call.status)">{{ call.status?.replace('_', ' ') }}</span>
                </td>
                <td class="text-end">
                    <Link :href="route('admin.voice.calls.show', call.id)" class="link-primary">View</Link>
                </td>
            </tr>
        </DataTable>
    </AdminLayout>
</template>
