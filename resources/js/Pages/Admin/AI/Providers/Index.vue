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
    active: 'bg-emerald-100 text-emerald-800',
    disabled: 'bg-slate-100 text-slate-600',
    degraded: 'bg-amber-100 text-amber-800',
}[status] ?? 'bg-slate-100 text-slate-600');
</script>

<template>
    <Head title="AI Providers" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">AI Providers</h1>
                    <p class="text-sm text-slate-500">LLM provider credentials and runtime settings</p>
                </div>
                <PrimaryButton v-if="can('ai.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <DataTable title="Providers" :is-empty="!providers.data.length" empty-message="No providers configured yet.">
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="text-xs font-medium text-slate-500">Search</label>
                        <input v-model="local.q" type="search" placeholder="Name or slug..." class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @keyup.enter="reload" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Status</label>
                        <select v-model="local.status" class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">API Key</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Models</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Priority</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ provider.name }}</div>
                    <div class="text-xs text-slate-500">{{ provider.slug }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <span :class="provider.has_api_key ? 'text-emerald-600' : 'text-slate-400'">{{ provider.has_api_key ? 'Configured' : 'Missing' }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ provider.models_count ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ provider.priority }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.ai.providers.show', provider.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="providers.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-6">
                    <p class="text-sm text-slate-500">Showing {{ providers.from ?? 0 }}–{{ providers.to ?? 0 }} of {{ providers.total }}</p>
                    <div class="flex flex-wrap gap-1">
                        <Link v-for="link in providers.links" :key="link.label" :href="link.url || '#'" class="rounded px-3 py-1 text-sm" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" />
                    </div>
                </div>
            </template>
        </DataTable>

        <AiProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
