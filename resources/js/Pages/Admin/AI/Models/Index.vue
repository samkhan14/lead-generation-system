<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiModelFormModal from '@/Components/Admin/AiModelFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    models: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);

const local = ref({
    q: page.props.filters?.q ?? '',
    status: page.props.filters?.status ?? '',
    ai_provider_id: page.props.filters?.ai_provider_id ?? '',
});

const reload = () => {
    router.get(route('admin.ai.models.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
        ai_provider_id: local.value.ai_provider_id || undefined,
    }, { preserveState: true, replace: true });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    disabled: 'bg-label-secondary',
    deprecated: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="AI Models" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 mb-1">AI Models</h1>
                    <p class="text-muted mb-0">Model catalog per provider with pricing</p>
                </div>
                <PrimaryButton v-if="can('ai.models.create')" type="button" @click="showCreateModal = true">Add model</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Models" :is-empty="!models.data.length" empty-message="No models configured yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label">Search</label>
                        <input v-model="local.q" type="search" class="form-control" @keyup.enter="reload" />
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Provider</label>
                        <select v-model="local.ai_provider_id" class="form-select" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in providerOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                    </div>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="text-uppercase small fw-semibold text-muted">Model</th>
                    <th class="text-uppercase small fw-semibold text-muted">Provider</th>
                    <th class="text-uppercase small fw-semibold text-muted">Status</th>
                    <th class="text-uppercase small fw-semibold text-muted">Max tokens</th>
                    <th class="text-end text-uppercase small fw-semibold text-muted">Actions</th>
                </tr>
            </template>
            <tr v-for="model in models.data" :key="model.id">
                <td>
                    <div class="fw-medium">{{ model.name }}</div>
                    <div class="small text-muted">{{ model.slug }}</div>
                </td>
                <td class="text-muted">{{ model.provider?.name ?? 'None' }}</td>
                <td><span class="badge text-capitalize" :class="statusClass(model.status)">{{ model.status }}</span></td>
                <td class="text-muted">{{ model.max_tokens?.toLocaleString() ?? 'None' }}</td>
                <td class="text-end">
                    <Link :href="route('admin.ai.models.show', model.id)" class="btn btn-sm btn-outline-primary">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="models.links?.length > 3" class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <p class="text-muted mb-0 small">Showing {{ models.from ?? 0 }}-{{ models.to ?? 0 }} of {{ models.total }}</p>
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        <li v-for="link in models.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link :href="link.url || '#'" class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </div>
            </template>
        </DataTable>

        <AiModelFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :provider-options="providerOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
