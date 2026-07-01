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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.ai.employees.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to employees</Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ employee.name }}</h1>
                    <p class="text-sm text-slate-500">{{ employee.role_label }} · {{ employee.status }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.employees.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.employees.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Overview</h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ employee.description || 'No description.' }}</p>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">System prompt</h2>
                    <pre class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ employee.system_prompt || '—' }}</pre>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Behavior prompt</h2>
                    <pre class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ employee.behavior_prompt || '—' }}</pre>
                </section>
            </div>
            <aside class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Runtime</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Operational</dt><dd class="font-medium" :class="employee.is_operational ? 'text-emerald-600' : 'text-amber-600'">{{ employee.is_operational ? 'Yes' : 'No' }}</dd></div>
                        <div><dt class="text-slate-500">Provider</dt><dd class="font-medium text-slate-900">{{ employee.provider?.name ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Model</dt><dd class="font-medium text-slate-900">{{ employee.model?.name ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Temperature</dt><dd class="font-medium text-slate-900">{{ employee.temperature }}</dd></div>
                        <div><dt class="text-slate-500">Context window</dt><dd class="font-medium text-slate-900">{{ employee.context_window?.toLocaleString() }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Knowledge sources</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li v-for="source in employee.knowledge_sources || []" :key="source">{{ source }}</li>
                        <li v-if="!(employee.knowledge_sources || []).length" class="list-none pl-0 text-slate-400">None</li>
                    </ul>
                </section>
            </aside>
        </div>

        <AiEmployeeFormModal :show="showEditModal" mode="edit" :employee="employee" :status-options="statusOptions" :role-options="roleOptions" :provider-options="providerOptions" :model-options="modelOptions" :knowledge-source-options="knowledgeSourceOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete employee?</h2>
                <p class="mt-2 text-sm text-slate-600">Remove {{ employee.name }} from the workforce.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
