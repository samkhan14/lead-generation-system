<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    campaigns: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const page = usePage();
</script>

<template>
    <Head title="Email Campaigns" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">Email Campaigns</h4>
                    <p class="small text-muted mb-0">Create, enhance, and send marketing emails</p>
                </div>
                <Link :href="route('admin.email.campaigns.create')">
                    <PrimaryButton type="button">New campaign</PrimaryButton>
                </Link>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Campaigns" :is-empty="!campaigns.data.length" empty-message="No campaigns yet.">
            <template #head>
                <tr>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Sends</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="campaign in campaigns.data" :key="campaign.id">
                <td>
                    <div class="fw-medium">{{ campaign.name }}</div>
                    <div class="small text-muted">{{ campaign.subject }}</div>
                    <span v-if="campaign.ai_enhanced" class="badge bg-label-primary mt-1">AI enhanced</span>
                </td>
                <td>
                    <span class="badge bg-label-secondary text-capitalize">{{ campaign.status }}</span>
                </td>
                <td class="text-muted">{{ campaign.sends_count ?? 0 }}</td>
                <td class="text-end">
                    <Link :href="route('admin.email.campaigns.show', campaign.id)" class="link-primary small fw-medium">View</Link>
                </td>
            </tr>
        </DataTable>
    </AdminLayout>
</template>
