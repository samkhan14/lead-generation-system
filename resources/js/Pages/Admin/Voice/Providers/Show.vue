<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VoiceProviderFormModal from '@/Components/Admin/VoiceProviderFormModal.vue';
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
    deleteForm.delete(route('admin.voice.providers.destroy', props.provider.id), {
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
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <Link :href="route('admin.voice.providers.index')" class="small text-muted text-decoration-none d-inline-block mb-1">← Back to voice providers</Link>
                    <h4 class="mb-0 fw-bold">{{ provider.name }}</h4>
                    <p class="mb-0 small text-muted">Webhook URL: {{ route('api.voice.webhooks', provider.slug) }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('voice.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('voice.providers.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Configuration</h5>
            </div>
            <div class="card-body">
                <dl class="row g-3 mb-0">
                    <div class="col-sm-6">
                        <dt class="small text-uppercase text-muted">Status</dt>
                        <dd class="mb-0 mt-1">
                            <span class="badge rounded-pill text-capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span>
                        </dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="small text-uppercase text-muted">Priority</dt>
                        <dd class="mb-0 mt-1 fw-medium">{{ provider.priority }}</dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="small text-uppercase text-muted">API key</dt>
                        <dd class="mb-0 mt-1">
                            <span class="badge" :class="provider.has_api_key ? 'bg-label-success' : 'bg-label-danger'">
                                {{ provider.has_api_key ? 'Configured' : 'Not set' }}
                            </span>
                        </dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="small text-uppercase text-muted">Webhook secret</dt>
                        <dd class="mb-0 mt-1">
                            <span class="badge" :class="provider.has_webhook_secret ? 'bg-label-success' : 'bg-label-secondary'">
                                {{ provider.has_webhook_secret ? 'Configured' : 'Not set' }}
                            </span>
                        </dd>
                    </div>
                </dl>
                <pre class="card bg-lighter mt-4 mb-0 p-3 small">{{ JSON.stringify(provider.metadata ?? {}, null, 2) }}</pre>
            </div>
        </div>

        <VoiceProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="modal-header">
                <h5 class="modal-title">Delete voice provider?</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="showDeleteModal = false" />
            </div>
            <div class="modal-footer">
                <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
            </div>
        </Modal>
    </AdminLayout>
</template>
