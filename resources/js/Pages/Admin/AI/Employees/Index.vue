<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiEmployeeFormModal from '@/Components/Admin/AiEmployeeFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
    modelOptions: { type: Array, default: () => [] },
    knowledgeSourceOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);

const local = ref({
    q: page.props.filters?.q ?? '',
    status: page.props.filters?.status ?? '',
    role: page.props.filters?.role ?? '',
});

const reload = () => {
    router.get(route('admin.ai.employees.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
        role: local.value.role || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="AI Employees" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">AI Employees</h4>
                    <p class="small text-muted mb-0">Configured AI agents for sales, voice, and support</p>
                </div>
                <PrimaryButton v-if="can('ai.employees.create')" type="button" @click="showCreateModal = true">Add employee</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Employees" :is-empty="!employees.data.length" empty-message="No AI employees yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input v-model="local.q" type="search" class="form-control form-control-sm" @keyup.enter="reload" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select v-model="local.role" class="form-select form-select-sm" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                    </div>
                </div>
            </template>
            <template #head>
                <tr>
                    <th>Employee</th>
                    <th>Role</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="employee in employees.data" :key="employee.id">
                <td>
                    <div class="fw-medium">{{ employee.name }}</div>
                    <div class="small text-muted">{{ employee.department || '—' }}</div>
                </td>
                <td class="text-muted">{{ employee.role_label || employee.role }}</td>
                <td class="text-muted">{{ employee.model?.name ?? '—' }}</td>
                <td>
                    <span class="badge bg-label-secondary text-capitalize">{{ employee.status }}</span>
                </td>
                <td class="text-end">
                    <Link :href="route('admin.ai.employees.show', employee.id)" class="link-primary small fw-medium">View</Link>
                </td>
            </tr>
            <template #footer>
                <Pagination :paginator="employees" item-label="employees" />
            </template>
        </DataTable>

        <AiEmployeeFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :role-options="roleOptions" :provider-options="providerOptions" :model-options="modelOptions" :knowledge-source-options="knowledgeSourceOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
