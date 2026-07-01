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
</script>

<template>
    <Head :title="model.name" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.ai.models.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to models</Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ model.name }}</h1>
                    <p class="text-sm text-slate-500">{{ model.slug }} · {{ model.provider?.name }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.models.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.models.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ model.status }}</dd></div>
                <div><dt class="text-slate-500">Max tokens</dt><dd class="mt-1 font-medium text-slate-900">{{ model.max_tokens?.toLocaleString() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Input price / 1K</dt><dd class="mt-1 font-medium text-slate-900">${{ model.input_price_per_1k ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Output price / 1K</dt><dd class="mt-1 font-medium text-slate-900">${{ model.output_price_per_1k ?? '—' }}</dd></div>
            </dl>
        </div>

        <AiModelFormModal :show="showEditModal" mode="edit" :model="model" :status-options="statusOptions" :provider-options="providerOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete model?</h2>
                <p class="mt-2 text-sm text-slate-600">Remove {{ model.name }} from the catalog.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
