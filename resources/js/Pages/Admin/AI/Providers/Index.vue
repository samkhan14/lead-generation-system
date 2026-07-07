<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiProviderFormModal from '@/Components/Admin/AiProviderFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    providers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);

const local = ref({
    q: page.props.filters?.q ?? '',
    status: page.props.filters?.status ?? '',
});

const reload = () => {
    router.get(route('admin.ai.providers.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
    }, { preserveState: true, replace: true });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    disabled: 'bg-label-secondary',
    degraded: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="AI Providers" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h4 mb-1">AI Providers</h1>
                    <p class="text-muted mb-0">LLM provider credentials and runtime settings</p>
                </div>
                <PrimaryButton v-if="can('ai.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">
            {{ page.props.flash.success }}
        </div>

        <DataTable title="Providers" :is-empty="!providers.data.length" empty-message="No providers configured yet.">
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md">
                        <label class="form-label">Search</label>
                        <input v-model="local.q" type="search" placeholder="Name or slug..." class="form-control" @keyup.enter="reload" />
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
                    <th class="text-uppercase small fw-semibold text-muted">Provider</th>
                    <th class="text-uppercase small fw-semibold text-muted">Status</th>
                    <th class="text-uppercase small fw-semibold text-muted">API Key</th>
                    <th class="text-uppercase small fw-semibold text-muted">Models</th>
                    <th class="text-uppercase small fw-semibold text-muted">Priority</th>
                    <th class="text-end text-uppercase small fw-semibold text-muted">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id">
                <td>
                    <div class="fw-medium">{{ provider.name }}</div>
                    <div class="small text-muted">{{ provider.slug }}</div>
                </td>
                <td>
                    <span class="badge text-capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span>
                </td>
                <td>
                    <span :class="provider.has_api_key ? 'text-success' : 'text-muted'">{{ provider.has_api_key ? 'Configured' : 'Missing' }}</span>
                </td>
                <td class="text-muted">{{ provider.models_count ?? 0 }}</td>
                <td class="text-muted">{{ provider.priority }}</td>
                <td class="text-end">
                    <Link :href="route('admin.ai.providers.show', provider.id)" class="btn btn-sm btn-outline-primary">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="providers.links?.length > 3" class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <p class="text-muted mb-0 small">Showing {{ providers.from ?? 0 }}-{{ providers.to ?? 0 }} of {{ providers.total }}</p>
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        <li v-for="link in providers.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link :href="link.url || '#'" class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </div>
            </template>
        </DataTable>

        <AiProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
