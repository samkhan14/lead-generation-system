<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PromptTemplateFormModal from '@/Components/Admin/PromptTemplateFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    templates: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);

const local = ref({
    q: page.props.filters?.q ?? '',
    status: page.props.filters?.status ?? '',
    category: page.props.filters?.category ?? '',
});

const reload = () => {
    router.get(route('admin.ai.prompts.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
        category: local.value.category || undefined,
    }, { preserveState: true, replace: true });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    draft: 'bg-label-warning',
    archived: 'bg-label-secondary',
    disabled: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="Prompt Templates" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 mb-1">Prompt Templates</h1>
                    <p class="text-muted mb-0">Reusable prompts for AI employees</p>
                </div>
                <PrimaryButton v-if="can('ai.prompts.create')" type="button" @click="showCreateModal = true">Add prompt</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Prompts" :is-empty="!templates.data.length" empty-message="No prompt templates yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label">Search</label>
                        <input v-model="local.q" type="search" class="form-control" @keyup.enter="reload" />
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Category</label>
                        <select v-model="local.category" class="form-select" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select v-model="local.status" class="form-select" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                    </div>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="text-uppercase small fw-semibold text-muted">Prompt</th>
                    <th class="text-uppercase small fw-semibold text-muted">Category</th>
                    <th class="text-uppercase small fw-semibold text-muted">Status</th>
                    <th class="text-end text-uppercase small fw-semibold text-muted">Actions</th>
                </tr>
            </template>
            <tr v-for="template in templates.data" :key="template.id">
                <td>
                    <div class="fw-medium">{{ template.name }}</div>
                    <div class="small text-muted">{{ template.slug }}</div>
                </td>
                <td><span class="badge bg-label-primary text-capitalize">{{ template.category?.replace('_', ' ') }}</span></td>
                <td><span class="badge text-capitalize" :class="statusClass(template.status)">{{ template.status }}</span></td>
                <td class="text-end">
                    <Link :href="route('admin.ai.prompts.show', template.id)" class="btn btn-sm btn-outline-primary">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="templates.links?.length > 3" class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <p class="text-muted mb-0 small">Showing {{ templates.from ?? 0 }}-{{ templates.to ?? 0 }} of {{ templates.total }}</p>
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        <li v-for="link in templates.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link :href="link.url || '#'" class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </div>
            </template>
        </DataTable>

        <PromptTemplateFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :category-options="categoryOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
