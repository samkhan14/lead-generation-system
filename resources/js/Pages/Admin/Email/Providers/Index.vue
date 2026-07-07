<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailProviderFormModal from '@/Components/Admin/EmailProviderFormModal.vue';
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
    <Head title="Email Providers" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">Email Providers</h4>
                    <p class="small text-muted mb-0">Resend, SMTP, and log drivers</p>
                </div>
                <PrimaryButton v-if="can('email.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Email providers" :is-empty="!providers.data.length" empty-message="No email providers configured yet.">
            <template #head>
                <tr>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Usable</th>
                    <th>Sends</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id">
                <td>
                    <div class="fw-medium">{{ provider.name }}</div>
                    <div class="small text-muted">{{ provider.slug }}</div>
                </td>
                <td>
                    <span class="badge bg-label-secondary text-capitalize">{{ provider.status }}</span>
                </td>
                <td :class="provider.is_usable ? 'text-success' : 'text-muted'">{{ provider.is_usable ? 'Yes' : 'No' }}</td>
                <td class="text-muted">{{ provider.sends_count ?? 0 }}</td>
                <td class="text-end">
                    <Link :href="route('admin.email.providers.show', provider.id)" class="link-primary small fw-medium">View</Link>
                </td>
            </tr>
        </DataTable>

        <EmailProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
