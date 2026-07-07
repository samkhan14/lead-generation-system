<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    call: { type: Object, required: true },
});

const statusClass = (status) => ({
    completed: 'bg-label-success',
    in_progress: 'bg-label-info',
    ringing: 'bg-label-info',
    queued: 'bg-label-secondary',
    pending: 'bg-label-primary',
    failed: 'bg-label-danger',
    cancelled: 'bg-label-secondary',
    no_answer: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');
</script>

<template>
    <Head title="Voice Call Detail" />
    <AdminLayout>
        <template #header>
            <div>
                <Link :href="route('admin.voice.calls.index')" class="small text-muted text-decoration-none d-inline-block mb-1">← Back to voice calls</Link>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h4 class="mb-0 fw-bold">Call {{ call.uuid?.slice(0, 8) }}…</h4>
                    <span class="badge rounded-pill text-capitalize" :class="statusClass(call.status)">{{ call.status?.replace('_', ' ') }}</span>
                </div>
                <p class="mb-0 small text-muted">{{ call.provider?.name }}</p>
            </div>
        </template>

        <div class="row g-4">
            <div class="col-lg-8">
                <div v-if="call.error_message" class="alert alert-danger">{{ call.error_message }}</div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Transcript</h5>
                    </div>
                    <div class="card-body">
                        <pre class="card bg-lighter mb-0 p-3 small text-break" style="white-space: pre-wrap;">{{ call.transcript || 'No transcript yet.' }}</pre>
                    </div>
                </div>

                <div v-if="call.summary" class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ call.summary }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Lead</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ call.lead?.full_name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Employee</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ call.employee?.name ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">From</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ call.from_number ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">To</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ call.to_number ?? '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">Duration</dt>
                                <dd class="mb-0 mt-1 fw-medium">{{ call.duration_seconds ? `${call.duration_seconds}s` : '—' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="text-uppercase text-muted">External ID</dt>
                                <dd class="mb-0 mt-1 fw-medium text-break">{{ call.external_call_id ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
