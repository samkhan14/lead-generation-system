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

const statusClass = (status) => ({
    active: 'bg-label-success',
    disabled: 'bg-label-secondary',
    degraded: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="Voice Providers" />
    <AdminLayout>
        <template #header>
            <div class="d-flex w-100 align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-0 fw-bold">Voice Providers</h4>
                    <p class="mb-0 small text-muted">Retell, LiveKit, and Vapi credentials</p>
                </div>
                <PrimaryButton v-if="can('voice.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <DataTable title="Voice providers" :is-empty="!providers.data.length" empty-message="No voice providers configured yet.">
            <template #head>
                <tr>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>API Key</th>
                    <th>Calls</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id">
                <td>
                    <div class="fw-medium">{{ provider.name }}</div>
                    <div class="small text-muted">{{ provider.slug }}</div>
                </td>
                <td>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span>
                </td>
                <td>
                    <span class="badge" :class="provider.has_api_key ? 'bg-label-success' : 'bg-label-secondary'">
                        {{ provider.has_api_key ? 'Configured' : 'Missing' }}
                    </span>
                </td>
                <td>{{ provider.calls_count ?? 0 }}</td>
                <td class="text-end">
                    <Link :href="route('admin.voice.providers.show', provider.id)" class="link-primary">View</Link>
                </td>
            </tr>
        </DataTable>

        <VoiceProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
