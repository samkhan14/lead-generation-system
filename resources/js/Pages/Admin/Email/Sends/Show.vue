<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    send: { type: Object, required: true },
});
</script>

<template>
    <Head title="Email Send" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900">Email send #{{ send.id }}</h1>
        </template>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">To</dt><dd>{{ send.to_email }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Subject</dt><dd>{{ send.subject }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="capitalize">{{ send.status }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Provider message ID</dt><dd class="max-w-xs truncate">{{ send.provider_message_id || '—' }}</dd></div>
                    <div v-if="send.error_message" class="rounded bg-red-50 p-3 text-red-700">{{ send.error_message }}</div>
                    <div class="flex justify-between"><dt class="text-slate-500">Sent at</dt><dd>{{ send.sent_at || '—' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Body preview</h2>
                <div class="prose prose-sm mt-4 max-w-none" v-html="send.html_body" />
            </div>
        </div>
    </AdminLayout>
</template>
