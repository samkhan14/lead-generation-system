<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiProviderFormModal from '@/Components/Admin/AiProviderFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    provider: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.ai.providers.destroy', props.provider.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    disabled: 'bg-label-secondary',
    degraded: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head :title="provider.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <Link :href="route('admin.ai.providers.index')" class="small text-muted text-decoration-none">Back to providers</Link>
                    <h1 class="h4 mb-1 mt-1">{{ provider.name }}</h1>
                    <p class="text-muted mb-0">{{ provider.slug }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.providers.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">
            {{ page.props.flash.success }}
        </div>

        <div class="row g-4">
            <section class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Configuration</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row g-4 mb-0">
                            <div class="col-12 col-sm-6"><dt class="text-muted small">Status</dt><dd class="mb-0 mt-1"><span class="badge text-capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span></dd></div>
                            <div class="col-12 col-sm-6"><dt class="text-muted small">API key</dt><dd class="fw-medium mb-0 mt-1" :class="provider.has_api_key ? 'text-success' : 'text-danger'">{{ provider.has_api_key ? 'Configured' : 'Not set' }}</dd></div>
                            <div class="col-12 col-sm-6"><dt class="text-muted small">Priority</dt><dd class="fw-medium mb-0 mt-1">{{ provider.priority }}</dd></div>
                            <div class="col-12 col-sm-6"><dt class="text-muted small">Rate limit</dt><dd class="fw-medium mb-0 mt-1">{{ provider.rate_limit_rpm ?? 'None' }} RPM</dd></div>
                            <div class="col-12 col-sm-6"><dt class="text-muted small">Timeout</dt><dd class="fw-medium mb-0 mt-1">{{ provider.timeout_seconds }}s</dd></div>
                            <div class="col-12 col-sm-6"><dt class="text-muted small">Retries</dt><dd class="fw-medium mb-0 mt-1">{{ provider.retry_count }}</dd></div>
                            <div class="col-12"><dt class="text-muted small">API base URL</dt><dd class="fw-medium mb-0 mt-1">{{ provider.api_base_url || 'Default' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </section>
            <aside class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Models</h5>
                    </div>
                    <div class="card-body">
                        <p class="display-6 fw-semibold mb-3">{{ provider.models_count ?? 0 }}</p>
                        <Link v-if="can('ai.models.view')" :href="route('admin.ai.models.index', { ai_provider_id: provider.id })" class="btn btn-outline-primary">View models</Link>
                    </div>
                </div>
            </aside>
        </div>

        <AiProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="modal-header">
                <h5 class="modal-title">Delete provider?</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="showDeleteModal = false" />
            </div>
            <div class="modal-body">
                <p class="mb-0">This will remove {{ provider.name }} and all associated models.</p>
            </div>
            <div class="modal-footer">
                <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
            </div>
        </Modal>
    </AdminLayout>
</template>
