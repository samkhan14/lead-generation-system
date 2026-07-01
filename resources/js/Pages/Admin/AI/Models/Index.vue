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
</script>

<template>
    <Head title="AI Models" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">AI Models</h1>
                    <p class="text-sm text-slate-500">Model catalog per provider with pricing</p>
                </div>
                <PrimaryButton v-if="can('ai.models.create')" type="button" @click="showCreateModal = true">Add model</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <DataTable title="Models" :is-empty="!models.data.length" empty-message="No models configured yet.">
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="text-xs font-medium text-slate-500">Search</label>
                        <input v-model="local.q" type="search" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @keyup.enter="reload" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Provider</label>
                        <select v-model="local.ai_provider_id" class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload">
                            <option value="">All</option>
                            <option v-for="option in providerOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                    <SecondaryButton type="button" @click="reload">Apply</SecondaryButton>
                </div>
            </template>
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Max tokens</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="model in models.data" :key="model.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ model.name }}</div>
                    <div class="text-xs text-slate-500">{{ model.slug }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ model.provider?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ model.status }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ model.max_tokens?.toLocaleString() ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.ai.models.show', model.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
            <template #footer>
                <div v-if="models.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-6">
                    <p class="text-sm text-slate-500">Showing {{ models.from ?? 0 }}–{{ models.to ?? 0 }} of {{ models.total }}</p>
                    <div class="flex flex-wrap gap-1">
                        <Link v-for="link in models.links" :key="link.label" :href="link.url || '#'" class="rounded px-3 py-1 text-sm" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" />
                    </div>
                </div>
            </template>
        </DataTable>

        <AiModelFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :provider-options="providerOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
