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
    active: 'bg-emerald-100 text-emerald-800',
    disabled: 'bg-slate-100 text-slate-600',
    degraded: 'bg-amber-100 text-amber-800',
}[status] ?? 'bg-slate-100 text-slate-600');
</script>

<template>
    <Head :title="provider.name" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.ai.providers.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to providers</Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ provider.name }}</h1>
                    <p class="text-sm text-slate-500">{{ provider.slug }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('ai.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('ai.providers.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Configuration</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                        <div><dt class="text-slate-500">Status</dt><dd class="mt-1"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(provider.status)">{{ provider.status }}</span></dd></div>
                        <div><dt class="text-slate-500">API key</dt><dd class="mt-1 font-medium" :class="provider.has_api_key ? 'text-emerald-600' : 'text-red-600'">{{ provider.has_api_key ? 'Configured' : 'Not set' }}</dd></div>
                        <div><dt class="text-slate-500">Priority</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.priority }}</dd></div>
                        <div><dt class="text-slate-500">Rate limit</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.rate_limit_rpm ?? '—' }} RPM</dd></div>
                        <div><dt class="text-slate-500">Timeout</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.timeout_seconds }}s</dd></div>
                        <div><dt class="text-slate-500">Retries</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.retry_count }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-slate-500">API base URL</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.api_base_url || 'Default' }}</dd></div>
                    </dl>
                </div>
            </section>
            <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Models</h2>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ provider.models_count ?? 0 }}</p>
                <Link v-if="can('ai.models.view')" :href="route('admin.ai.models.index', { ai_provider_id: provider.id })" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800">View models →</Link>
            </aside>
        </div>

        <AiProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete provider?</h2>
                <p class="mt-2 text-sm text-slate-600">This will remove {{ provider.name }} and all associated models.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
