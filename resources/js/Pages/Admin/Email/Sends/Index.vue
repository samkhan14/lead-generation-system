<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    sends: { type: Object, required: true },
});

const page = usePage();
</script>

<template>
    <Head title="Email Sends" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Email Sends</h1>
                    <p class="text-sm text-slate-500">Delivery log for campaigns and single emails</p>
                </div>
                <Link :href="route('admin.email.sends.create')">
                    <PrimaryButton type="button">Compose email</PrimaryButton>
                </Link>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Recipient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="send in sends.data" :key="send.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ send.to_email }}</div>
                            <span v-if="send.is_test" class="text-xs text-amber-600">Test</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ send.subject }}</td>
                        <td class="px-4 py-3 capitalize" :class="send.status === 'sent' ? 'text-emerald-600' : send.status === 'failed' ? 'text-red-600' : 'text-slate-600'">{{ send.status }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.email.sends.show', send.id)" class="font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!sends.data.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">No emails sent yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
