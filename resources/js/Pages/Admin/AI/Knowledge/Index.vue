<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import KnowledgeBaseFormModal from '@/Components/Admin/KnowledgeBaseFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    entries: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);

const local = ref({ q: page.props.filters?.q ?? '', status: page.props.filters?.status ?? '' });

const reload = () => {
    router.get(route('admin.ai.knowledge.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
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
    <Head title="Knowledge Base" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 mb-1">Knowledge Base</h1>
                    <p class="text-muted mb-0">Supplementary knowledge for AI employees</p>
                </div>
                <PrimaryButton v-if="can('ai.knowledge.create')" type="button" @click="showCreateModal = true">Add entry</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Entries" :is-empty="!entries.data.length" empty-message="No knowledge entries yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label">Search</label>
                        <input v-model="local.q" type="search" class="form-control" @keyup.enter="reload" />
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
                    <th class="text-uppercase small fw-semibold text-muted">Entry</th>
                    <th class="text-uppercase small fw-semibold text-muted">Category</th>
                    <th class="text-uppercase small fw-semibold text-muted">Status</th>
                    <th class="text-end text-uppercase small fw-semibold text-muted">Actions</th>
                </tr>
            </template>
            <tr v-for="entry in entries.data" :key="entry.id">
                <td>
                    <div class="fw-medium">{{ entry.name }}</div>
                    <div class="small text-muted">{{ entry.slug }}</div>
                </td>
                <td><span class="badge bg-label-primary text-capitalize">{{ entry.category?.replace('_', ' ') }}</span></td>
                <td><span class="badge text-capitalize" :class="statusClass(entry.status)">{{ entry.status }}</span></td>
                <td class="text-end">
                    <Link :href="route('admin.ai.knowledge.show', entry.id)" class="btn btn-sm btn-outline-primary">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="entries.links?.length > 3" class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <p class="text-muted mb-0 small">Showing {{ entries.from ?? 0 }}-{{ entries.to ?? 0 }} of {{ entries.total }}</p>
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        <li v-for="link in entries.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link :href="link.url || '#'" class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </div>
            </template>
        </DataTable>

        <KnowledgeBaseFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :category-options="categoryOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
