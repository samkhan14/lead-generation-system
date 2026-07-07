<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    sends: { type: Object, required: true },
});

const page = usePage();

const statusClass = (status) => ({
    sent: 'text-success',
    failed: 'text-danger',
}[status] ?? 'text-muted');
</script>

<template>
    <Head title="Email Sends" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">Email Sends</h4>
                    <p class="small text-muted mb-0">Delivery log for campaigns and single emails</p>
                </div>
                <Link :href="route('admin.email.sends.create')">
                    <PrimaryButton type="button">Compose email</PrimaryButton>
                </Link>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <DataTable title="Send log" :is-empty="!sends.data.length" empty-message="No emails sent yet.">
            <template #head>
                <tr>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </template>
            <tr v-for="send in sends.data" :key="send.id">
                <td>
                    <div class="fw-medium">{{ send.to_email }}</div>
                    <span v-if="send.is_test" class="badge bg-label-warning">Test</span>
                </td>
                <td class="text-muted">{{ send.subject }}</td>
                <td class="text-capitalize" :class="statusClass(send.status)">{{ send.status }}</td>
                <td class="text-end">
                    <Link :href="route('admin.email.sends.show', send.id)" class="link-primary small fw-medium">View</Link>
                </td>
            </tr>
        </DataTable>
    </AdminLayout>
</template>
