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
</script>

<template>
    <Head :title="provider.name" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.voice.providers.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to voice providers</Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ provider.name }}</h1>
                    <p class="text-sm text-slate-500">Webhook URL: {{ route('api.voice.webhooks', provider.slug) }}</p>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton v-if="can('voice.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('voice.providers.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-medium capitalize text-slate-900">{{ provider.status }}</dd></div>
                <div><dt class="text-slate-500">Priority</dt><dd class="mt-1 font-medium text-slate-900">{{ provider.priority }}</dd></div>
                <div><dt class="text-slate-500">API key</dt><dd class="mt-1 font-medium" :class="provider.has_api_key ? 'text-emerald-600' : 'text-red-600'">{{ provider.has_api_key ? 'Configured' : 'Not set' }}</dd></div>
                <div><dt class="text-slate-500">Webhook secret</dt><dd class="mt-1 font-medium" :class="provider.has_webhook_secret ? 'text-emerald-600' : 'text-slate-400'">{{ provider.has_webhook_secret ? 'Configured' : 'Not set' }}</dd></div>
            </dl>
            <pre class="mt-6 overflow-auto rounded-lg bg-slate-50 p-4 text-xs text-slate-700">{{ JSON.stringify(provider.metadata ?? {}, null, 2) }}</pre>
        </div>

        <VoiceProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete voice provider?</h2>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
