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
</script>

<template>
    <Head :title="template.name" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.ai.prompts.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to prompts</Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ template.name }}</h1>
                    <p class="text-sm text-slate-500">{{ template.slug }} · {{ template.category }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.prompts.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.prompts.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Content</h2>
            <pre class="mt-4 whitespace-pre-wrap rounded-lg bg-slate-50 p-4 font-mono text-sm text-slate-700">{{ template.content }}</pre>
            <div v-if="template.tags?.length" class="mt-4 flex flex-wrap gap-2">
                <span v-for="tag in template.tags" :key="tag" class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ tag }}</span>
            </div>
        </section>

        <PromptTemplateFormModal :show="showEditModal" mode="edit" :template="template" :status-options="statusOptions" :category-options="categoryOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete prompt?</h2>
                <p class="mt-2 text-sm text-slate-600">Remove {{ template.name }}.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
