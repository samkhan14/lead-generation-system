<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailProviderFormModal from '@/Components/Admin/EmailProviderFormModal.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    providers: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showCreateModal = ref(false);
</script>

<template>
    <Head title="Email Providers" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Email Providers</h1>
                    <p class="text-sm text-slate-500">Resend, SMTP, and log drivers</p>
                </div>
                <PrimaryButton v-if="can('email.providers.create')" type="button" @click="showCreateModal = true">Add provider</PrimaryButton>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <DataTable title="Email providers" :is-empty="!providers.data.length" empty-message="No email providers configured yet.">
            <template #head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Usable</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Sends</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </template>
            <tr v-for="provider in providers.data" :key="provider.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ provider.name }}</div>
                    <div class="text-xs text-slate-500">{{ provider.slug }}</div>
                </td>
                <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ provider.status }}</td>
                <td class="px-4 py-3 text-sm" :class="provider.is_usable ? 'text-emerald-600' : 'text-slate-400'">{{ provider.is_usable ? 'Yes' : 'No' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ provider.sends_count ?? 0 }}</td>
                <td class="px-4 py-3 text-right">
                    <Link :href="route('admin.email.providers.show', provider.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                </td>
            </tr>
        </DataTable>

        <EmailProviderFormModal :show="showCreateModal" mode="create" :status-options="statusOptions" :slug-options="slugOptions" @close="showCreateModal = false" />
    </AdminLayout>
</template>
