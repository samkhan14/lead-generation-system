<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    log: { type: Object, required: true },
});

const formatCost = (value) => value != null ? `$${Number(value).toFixed(6)}` : '—';
</script>

<template>
    <Head title="AI Log Detail" />
    <AdminLayout>
        <template #header>
            <div>
                <Link :href="route('admin.ai.logs.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to logs</Link>
                <h1 class="mt-1 text-xl font-semibold text-slate-900">Log {{ log.uuid?.slice(0, 8) }}…</h1>
                <p class="text-sm text-slate-500">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '' }}</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="space-y-6 lg:col-span-2">
                <div v-if="log.error_message" class="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
                    {{ log.error_message }}
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Request payload</h2>
                    <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-4 font-mono text-xs text-slate-700">{{ JSON.stringify(log.request_payload, null, 2) || '—' }}</pre>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Response payload</h2>
                    <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-4 font-mono text-xs text-slate-700">{{ JSON.stringify(log.response_payload, null, 2) || '—' }}</pre>
                </div>
            </section>
            <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Metrics</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize text-slate-900">{{ log.status }}</dd></div>
                    <div><dt class="text-slate-500">Type</dt><dd class="font-medium capitalize text-slate-900">{{ log.request_type?.replace('_', ' ') }}</dd></div>
                    <div><dt class="text-slate-500">Employee</dt><dd class="font-medium text-slate-900">{{ log.employee?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Provider</dt><dd class="font-medium text-slate-900">{{ log.provider?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Model</dt><dd class="font-medium text-slate-900">{{ log.model?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Lead</dt><dd class="font-medium text-slate-900">{{ log.lead?.full_name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Tokens</dt><dd class="font-medium text-slate-900">{{ log.prompt_tokens }} + {{ log.completion_tokens }} = {{ log.total_tokens }}</dd></div>
                    <div><dt class="text-slate-500">Cost</dt><dd class="font-medium text-slate-900">{{ formatCost(log.cost_usd) }}</dd></div>
                    <div><dt class="text-slate-500">Latency</dt><dd class="font-medium text-slate-900">{{ log.latency_ms ?? '—' }} ms</dd></div>
                </dl>
            </aside>
        </div>
    </AdminLayout>
</template>
