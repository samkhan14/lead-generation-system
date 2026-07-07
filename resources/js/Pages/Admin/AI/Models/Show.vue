<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiModelFormModal from '@/Components/Admin/AiModelFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    model: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.ai.models.destroy', props.model.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    disabled: 'bg-label-secondary',
    deprecated: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head :title="model.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <Link :href="route('admin.ai.models.index')" class="small text-muted text-decoration-none">Back to models</Link>
                    <h1 class="h4 mb-1 mt-1">{{ model.name }}</h1>
                    <p class="text-muted mb-0">{{ model.slug }} / {{ model.provider?.name }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.models.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.models.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">{{ page.props.flash.success }}</div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Configuration</h5>
            </div>
            <div class="card-body">
                <dl class="row g-4 mb-0">
                    <div class="col-12 col-sm-6"><dt class="text-muted small">Status</dt><dd class="mb-0 mt-1"><span class="badge text-capitalize" :class="statusClass(model.status)">{{ model.status }}</span></dd></div>
                    <div class="col-12 col-sm-6"><dt class="text-muted small">Max tokens</dt><dd class="fw-medium mb-0 mt-1">{{ model.max_tokens?.toLocaleString() ?? 'None' }}</dd></div>
                    <div class="col-12 col-sm-6"><dt class="text-muted small">Input price / 1K</dt><dd class="fw-medium mb-0 mt-1">${{ model.input_price_per_1k ?? 'None' }}</dd></div>
                    <div class="col-12 col-sm-6"><dt class="text-muted small">Output price / 1K</dt><dd class="fw-medium mb-0 mt-1">${{ model.output_price_per_1k ?? 'None' }}</dd></div>
                </dl>
            </div>
        </div>

        <AiModelFormModal :show="showEditModal" mode="edit" :model="model" :status-options="statusOptions" :provider-options="providerOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="modal-header">
                <h5 class="modal-title">Delete model?</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="showDeleteModal = false" />
            </div>
            <div class="modal-body">
                <p class="mb-0">Remove {{ model.name }} from the catalog.</p>
            </div>
            <div class="modal-footer">
                <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
            </div>
        </Modal>
    </AdminLayout>
</template>
