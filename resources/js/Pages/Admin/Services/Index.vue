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
        active: 'bg-emerald-100 text-emerald-800',
        draft: 'bg-amber-100 text-amber-800',
        archived: 'bg-slate-100 text-slate-600',
    }[status] ?? 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Services" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Services</h1>
                    <p class="text-sm text-slate-500">Business knowledge catalog for AI employees</p>
                </div>
                <PrimaryButton v-if="can('services.create')" type="button" @click="showCreateModal = true">
                    Add service
                </PrimaryButton>
            </div>
        </template>

        <div
            v-if="page.props.flash.success"
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ page.props.flash.success }}
        </div>

        <DataTable
            title="Service catalog"
            :is-empty="!services.data.length"
            empty-message="No services yet. Create your first offering for AI employees to use."
        >
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="text-xs font-medium text-slate-500">Search</label>
                        <input
                            v-model="local.q"
                            type="search"
                            placeholder="Name, slug, description..."
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keyup.enter="reload"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Status</label>
                        <select
                            v-model="local.status"
                            class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="reload"
                        >
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Tag</label>
                        <input
                            v-model="local.tag"
                            type="text"
                            placeholder="e.g. web"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keyup.enter="reload"
                        />
                    </div>
                    <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                </div>
            </template>

            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Version</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tags</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </template>

            <tr v-for="service in services.data" :key="service.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ service.name }}</div>
                    <div class="text-xs text-slate-500">{{ service.slug }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(service.status)">
                        {{ service.status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">v{{ service.version }}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="tag in (service.tags || []).slice(0, 3)"
                            :key="`${service.id}-${tag}`"
                            class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-500">
                    {{ service.updated_at ? new Date(service.updated_at).toLocaleDateString() : '—' }}
                </td>
                <td class="px-4 py-3 text-right">
                    <Link
                        :href="route('admin.services.show', service.id)"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        View
                    </Link>
                </td>
            </tr>

            <template #footer>
                <div v-if="services.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-6">
                    <p class="text-sm text-slate-500">
                        Showing {{ services.from ?? 0 }}–{{ services.to ?? 0 }} of {{ services.total }}
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in services.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="rounded px-3 py-1 text-sm"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </template>
        </DataTable>

        <ServiceFormModal
            :show="showCreateModal"
            mode="create"
            :status-options="statusOptions"
            :service-options="serviceOptions"
            @close="showCreateModal = false"
        />
    </AdminLayout>
</template>
