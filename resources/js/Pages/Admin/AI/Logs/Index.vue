<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
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

const statusClass = (status) => ({
    success: 'bg-label-success',
    error: 'bg-label-danger',
    timeout: 'bg-label-warning',
    rate_limited: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="AI Logs" />
    <AdminLayout>
        <template #header>
            <div>
                <h4 class="mb-0 fw-bold">AI Logs</h4>
                <p class="mb-0 small text-muted">Read-only audit trail of LLM requests</p>
            </div>
        </template>

        <DataTable title="Interaction log" :is-empty="!logs.data.length" empty-message="No AI interactions logged yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Search UUID / error</label>
                        <input v-model="local.q" type="search" class="form-control form-control-sm" @keyup.enter="reload" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employee</label>
                        <select v-model="local.ai_employee_id" class="form-select form-select-sm" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in employeeOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                    </div>
                </div>
            </template>
            <template #head>
                <tr>
                    <th>Time</th>
                    <th>Employee</th>
                    <th>Model</th>
                    <th>Tokens</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="log in logs.data" :key="log.id">
                <td class="small text-muted text-nowrap">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '—' }}</td>
                <td>{{ log.employee?.name ?? '—' }}</td>
                <td>{{ log.model?.name ?? '—' }}</td>
                <td>{{ log.total_tokens ?? '—' }}</td>
                <td>{{ formatCost(log.cost_usd) }}</td>
                <td>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(log.status)">{{ log.status?.replace('_', ' ') }}</span>
                </td>
                <td class="text-end">
                    <Link :href="route('admin.ai.logs.show', log.id)" class="link-primary">View</Link>
                </td>
            </tr>
            <template #footer>
                <Pagination
                    :paginator="logs"
                    item-label="logs"
                    route-name="admin.ai.logs.index"
                    :query="{
                        q: local.q || undefined,
                        status: local.status || undefined,
                        ai_employee_id: local.ai_employee_id || undefined,
                    }"
                />
            </template>
        </DataTable>
    </AdminLayout>
</template>
