<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    log: { type: Object, required: true },
});

const formatCost = (value) => value != null ? `$${Number(value).toFixed(6)}` : '—';

const statusClass = (status) => ({
    success: 'bg-label-success',
    error: 'bg-label-danger',
    timeout: 'bg-label-warning',
    rate_limited: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="AI Log Detail" />
    <AdminLayout>
        <template #header>
            <div>
                <Link :href="route('admin.ai.logs.index')" class="small text-muted text-decoration-none d-inline-block mb-1">← Back to logs</Link>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h4 class="mb-0 fw-bold">Log {{ log.uuid?.slice(0, 8) }}…</h4>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(log.status)">{{ log.status?.replace('_', ' ') }}</span>
                </div>
                <p class="mb-0 small text-muted">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '' }}</p>
            </div>
        </template>

        <div class="row g-4">
            <div class="col-lg-8">
                <div v-if="log.error_message" class="alert alert-danger">
                    {{ log.error_message }}
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Request payload</h5>
                    </div>
                    <div class="card-body">
                        <pre class="card bg-lighter mb-0 p-3 small text-break" style="max-height: 24rem; overflow: auto; white-space: pre-wrap;">{{ JSON.stringify(log.request_payload, null, 2) || '—' }}</pre>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Response payload</h5>
                    </div>
                    <div class="card-body">
                        <pre class="card bg-lighter mb-0 p-3 small text-break" style="max-height: 24rem; overflow: auto; white-space: pre-wrap;">{{ JSON.stringify(log.response_payload, null, 2) || '—' }}</pre>
                    </div>
                </div>
            </div>

            <aside class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Metrics</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Status</dt>
                                <dd class="mb-0 mt-1">
                                    <span class="badge rounded-pill text-capitalize" :class="statusClass(log.status)">{{ log.status?.replace('_', ' ') }}</span>
                                </dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Type</dt>
                                <dd class="mb-0 mt-1 fw-medium text-capitalize">{{ log.request_type?.replace('_', ' ') }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Employee</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.employee?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Provider</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.provider?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Model</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.model?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Lead</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.lead?.full_name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Tokens</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.prompt_tokens }} + {{ log.completion_tokens }} = {{ log.total_tokens }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Cost</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ formatCost(log.cost_usd) }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Latency</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ log.latency_ms ?? '—' }} ms</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>
