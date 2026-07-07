<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import ServiceFormModal from '@/Components/Admin/ServiceFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    services: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    complexityOptions: {
        type: Array,
        default: () => [],
    },
    serviceOptions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { can } = useAuth();

const showCreateModal = ref(false);

const local = ref({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    tag: props.filters.tag ?? '',
    per_page: props.filters.per_page ?? 15,
});

const reload = () => {
    router.get(route('admin.services.index'), {
        q: local.value.q || undefined,
        status: local.value.status || undefined,
        tag: local.value.tag || undefined,
        per_page: local.value.per_page,
    }, {
        preserveState: true,
        replace: true,
    });
};

const statusClass = (status) => {
    return {
        active: 'bg-label-success',
        draft: 'bg-label-warning',
        archived: 'bg-label-secondary',
    }[status] ?? 'bg-label-secondary';
};
</script>

<template>
    <Head title="Services" />

    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">Services</h4>
                    <p class="mb-0 small text-muted">Business knowledge catalog for AI employees</p>
                </div>
                <PrimaryButton v-if="can('services.create')" type="button" @click="showCreateModal = true">
                    Add service
                </PrimaryButton>
            </div>
        </template>

        <div
            v-if="page.props.flash.success"
            class="alert alert-success mb-4"
            role="alert"
        >
            {{ page.props.flash.success }}
        </div>

        <DataTable
            title="Service catalog"
            :is-empty="!services.data.length"
            empty-message="No services yet. Create your first offering for AI employees to use."
        >
            <template #toolbar>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Search</label>
                        <input
                            v-model="local.q"
                            type="search"
                            placeholder="Name, slug, description..."
                            class="form-control form-control-sm"
                            @keyup.enter="reload"
                        />
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label class="form-label">Status</label>
                        <select
                            v-model="local.status"
                            class="form-select form-select-sm"
                            @change="reload"
                        >
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label class="form-label">Tag</label>
                        <input
                            v-model="local.tag"
                            type="text"
                            placeholder="e.g. web"
                            class="form-control form-control-sm"
                            @keyup.enter="reload"
                        />
                    </div>
                    <div class="col-auto">
                        <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                    </div>
                </div>
            </template>

            <template #head>
                <tr>
                    <th>Service</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Version</th>
                    <th>Tags</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>

            <tr v-for="service in services.data" :key="service.id">
                <td>
                    <div class="fw-medium">{{ service.name }}</div>
                    <div class="small text-muted">{{ service.short_description || service.slug }}</div>
                </td>
                <td>{{ service.sort_order ?? 0 }}</td>
                <td>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(service.status)">
                        {{ service.status }}
                    </span>
                </td>
                <td class="text-muted">v{{ service.version }}</td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <span
                            v-for="tag in (service.tags || []).slice(0, 3)"
                            :key="`${service.id}-${tag}`"
                            class="badge bg-label-secondary"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </td>
                <td class="small text-muted text-nowrap">
                    {{ service.updated_at ? new Date(service.updated_at).toLocaleDateString() : '—' }}
                </td>
                <td class="text-end">
                    <Link
                        :href="route('admin.services.show', service.id)"
                        class="btn btn-sm btn-text-primary"
                    >
                        View
                    </Link>
                </td>
            </tr>

            <template #footer>
                <div
                    v-if="services.links?.length > 3"
                    class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-top"
                >
                    <p class="small text-muted mb-0">
                        Showing {{ services.from ?? 0 }}–{{ services.to ?? 0 }} of {{ services.total }}
                    </p>
                    <nav aria-label="Services pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li
                                v-for="link in services.links"
                                :key="link.label"
                                class="page-item"
                                :class="{
                                    active: link.active,
                                    disabled: !link.url,
                                }"
                            >
                                <component
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="page-link"
                                    v-html="link.label"
                                />
                            </li>
                        </ul>
                    </nav>
                </div>
            </template>
        </DataTable>

        <ServiceFormModal
            :show="showCreateModal"
            mode="create"
            :status-options="statusOptions"
            :complexity-options="complexityOptions"
            :service-options="serviceOptions"
            @close="showCreateModal = false"
        />
    </AdminLayout>
</template>
