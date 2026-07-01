<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiEmployeeFormModal from '@/Components/Admin/AiEmployeeFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">AI Employees</h1>
                    <p class="text-sm text-slate-500">Configured AI agents for sales, voice, and support</p>
                </div>
                <PrimaryButton v-if="can('ai.employees.create')" type="button" @click="showCreateModal = true">Add employee</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <DataTable title="Employees" :is-empty="!employees.data.length" empty-message="No AI employees yet.">
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="text-xs font-medium text-slate-500">Search</label>
                        <input v-model="local.q" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @keyup.enter="reload" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Role</label>
                        <select v-model="local.role" class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="employee in employees.data" :key="employee.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ employee.name }}</div>
                    <div class="text-xs text-slate-500">{{ employee.department || '—' }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ employee.role_label || employee.role }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ employee.model?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ employee.status }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.ai.employees.show', employee.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="employees.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-6">
                    <p class="text-sm text-slate-500">Showing {{ employees.from ?? 0 }}–{{ employees.to ?? 0 }} of {{ employees.total }}</p>
                    <div class="flex flex-wrap gap-1">
                        <Link v-for="link in employees.links" :key="link.label" :href="link.url || '#'" class="rounded px-3 py-1 text-sm" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" />
                    </div>
                </div>
            </template>
        </DataTable>

        <AiEmployeeFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :role-options="roleOptions" :provider-options="providerOptions" :model-options="modelOptions" :knowledge-source-options="knowledgeSourceOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
