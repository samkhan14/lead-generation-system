<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PromptTemplateFormModal from '@/Components/Admin/PromptTemplateFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    template: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.ai.prompts.destroy', props.template.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    draft: 'bg-label-warning',
    archived: 'bg-label-secondary',
    disabled: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head :title="template.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <Link :href="route('admin.ai.prompts.index')" class="small text-muted text-decoration-none">Back to prompts</Link>
                    <h1 class="h4 mb-1 mt-1">{{ template.name }}</h1>
                    <p class="text-muted mb-0">{{ template.slug }} / {{ template.category }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.prompts.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.prompts.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success" role="alert">{{ page.props.flash.success }}</div>

        <div class="vstack gap-4">
            <section class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h5 class="card-title mb-0">Content</h5>
                    <span class="badge text-capitalize" :class="statusClass(template.status)">{{ template.status }}</span>
                </div>
                <div class="card-body">
                    <pre class="bg-body-tertiary border rounded p-3 mb-0 font-monospace small text-body" style="white-space: pre-wrap;">{{ template.content }}</pre>
                    <div v-if="template.tags?.length" class="d-flex flex-wrap gap-2 mt-3">
                        <span v-for="tag in template.tags" :key="tag" class="badge bg-label-secondary">{{ tag }}</span>
                    </div>
                </div>
            </section>
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-2">
                    <span class="badge bg-label-primary text-capitalize">{{ template.category?.replace('_', ' ') }}</span>
                    <span class="text-muted small">Slug: {{ template.slug }}</span>
                </div>
            </div>
        </div>

        <PromptTemplateFormModal :show="showEditModal" mode="edit" :template="template" :status-options="statusOptions" :category-options="categoryOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="modal-header">
                <h5 class="modal-title">Delete prompt?</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="showDeleteModal = false" />
            </div>
            <div class="modal-body">
                <p class="mb-0">Remove {{ template.name }}.</p>
            </div>
            <div class="modal-footer">
                <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
            </div>
        </Modal>
    </AdminLayout>
</template>
