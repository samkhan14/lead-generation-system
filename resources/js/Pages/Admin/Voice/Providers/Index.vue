<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoiceProviderFormModal from '@/Components/Admin/VoiceProviderFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    providers: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);
</script>

<template>
    <Head title="Voice Providers" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Voice Providers</h1>
                    <p class="text-sm text-slate-500">Retell, LiveKit, and Vapi credentials</p>
                </div>
                <PrimaryButton v-if="can('voice.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <DataTable title="Voice providers" :is-empty="!providers.data.length" empty-message="No voice providers configured yet.">
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">API Key</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Calls</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ provider.name }}</div>
                    <div class="text-xs text-slate-500">{{ provider.slug }}</div>
                </td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ provider.status }}</td>
                <td class="px-4 py-3 text-sm" :class="provider.has_api_key ? 'text-emerald-600' : 'text-slate-400'">{{ provider.has_api_key ? 'Configured' : 'Missing' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ provider.calls_count ?? 0 }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.voice.providers.show', provider.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
        </DataTable>

        <VoiceProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
