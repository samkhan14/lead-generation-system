<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailProviderFormModal from '@/Components/Admin/EmailProviderFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    provider: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);

const destroyProvider = () => {
    if (!window.confirm('Delete this email provider?')) return;
    router.delete(route('admin.email.providers.destroy', props.provider.id));
};
</script>

<template>
    <Head :title="provider.name" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ provider.name }}</h1>
                    <p class="text-sm text-slate-500">{{ provider.slug }} · priority {{ provider.priority }}</p>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton v-if="can('email.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <button v-if="can('email.providers.delete')" type="button" class="rounded-md border border-red-200 px-4 py-2 text-sm text-red-600 hover:bg-red-50" @click="destroyProvider">Delete</button>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Configuration</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="capitalize text-slate-800">{{ provider.status }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Usable</dt><dd :class="provider.is_usable ? 'text-emerald-600' : 'text-red-600'">{{ provider.is_usable ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">API key</dt><dd>{{ provider.has_api_key ? 'Configured' : 'Not set' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">From email</dt><dd>{{ provider.default_from_email || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">From name</dt><dd>{{ provider.default_from_name || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Total sends</dt><dd>{{ provider.sends_count ?? 0 }}</dd></div>
                </dl>
            </div>
        </div>

        <EmailProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />
    </AdminLayout>
</template>
