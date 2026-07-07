<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiEmployeeFormModal from '@/Components/Admin/AiEmployeeFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    employee: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
    modelOptions: { type: Array, default: () => [] },
    knowledgeSourceOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.ai.employees.destroy', props.employee.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
};
</script>

<template>
    <Head :title="employee.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <Link :href="route('admin.ai.employees.index')" class="small text-muted">← Back to employees</Link>
                    <h4 class="mt-1 mb-0 fw-bold">{{ employee.name }}</h4>
                    <p class="small text-muted mb-0">{{ employee.role_label }} · {{ employee.status }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.employees.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.employees.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <div class="row g-4">
            <div class="col-lg-8 vstack gap-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Overview</h5>
                        <p class="mb-0 text-muted" style="white-space: pre-wrap;">{{ employee.description || 'No description.' }}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">System prompt</h5>
                        <pre class="mb-0 small text-muted" style="white-space: pre-wrap;">{{ employee.system_prompt || '—' }}</pre>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Behavior prompt</h5>
                        <pre class="mb-0 small text-muted" style="white-space: pre-wrap;">{{ employee.behavior_prompt || '—' }}</pre>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 vstack gap-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Runtime</h5>
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12">
                                <dt class="text-muted mb-1">Operational</dt>
                                <dd class="mb-0 fw-medium" :class="employee.is_operational ? 'text-success' : 'text-warning'">{{ employee.is_operational ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-muted mb-1">Provider</dt>
                                <dd class="mb-0 fw-medium">{{ employee.provider?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-muted mb-1">Model</dt>
                                <dd class="mb-0 fw-medium">{{ employee.model?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-muted mb-1">Temperature</dt>
                                <dd class="mb-0 fw-medium">{{ employee.temperature }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-muted mb-1">Context window</dt>
                                <dd class="mb-0 fw-medium">{{ employee.context_window?.toLocaleString() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Knowledge sources</h5>
                        <ul class="mb-0 small text-muted ps-3">
                            <li v-for="source in employee.knowledge_sources || []" :key="source">{{ source }}</li>
                            <li v-if="!(employee.knowledge_sources || []).length" class="list-unstyled text-muted">None</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <AiEmployeeFormModal :show="showEditModal" mode="edit" :employee="employee" :status-options="statusOptions" :role-options="roleOptions" :provider-options="providerOptions" :model-options="modelOptions" :knowledge-source-options="knowledgeSourceOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-4 p-md-6">
                <h5 class="mb-2">Delete employee?</h5>
                <p class="text-muted small mb-0">Remove {{ employee.name }} from the workforce.</p>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
