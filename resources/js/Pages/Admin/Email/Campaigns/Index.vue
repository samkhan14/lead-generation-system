<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Email Campaigns</h1>
                    <p class="text-sm text-slate-500">Create, enhance, and send marketing emails</p>
                </div>
                <Link :href="route('admin.email.campaigns.create')">
                    <PrimaryButton type="button">New campaign</PrimaryButton>
                </Link>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Campaign</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Sends</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="campaign in campaigns.data" :key="campaign.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ campaign.name }}</div>
                            <div class="text-xs text-slate-500">{{ campaign.subject }}</div>
                            <span v-if="campaign.ai_enhanced" class="mt-1 inline-block rounded bg-violet-100 px-2 py-0.5 text-xs text-violet-700">AI enhanced</span>
                        </td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ campaign.status }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ campaign.sends_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.email.campaigns.show', campaign.id)" class="font-medium text-indigo-600 hover:text-indigo-800">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!campaigns.data.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">No campaigns yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
