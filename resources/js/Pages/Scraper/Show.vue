<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ScraperNotification from '@/Components/Admin/ScraperNotification.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    job: { type: Object, required: true },
    logs: { type: Array, default: () => [] },
});

const liveJob = ref({ ...props.job });
const notifyJob = ref(null);
let pollInterval = null;

const isTerminal = () => ['completed', 'failed'].includes(liveJob.value.status);

const poll = async () => {
    try {
        const res = await fetch(route('scraper.status', liveJob.value.uuid), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const wasRunning = !isTerminal();
        Object.assign(liveJob.value, data);

        if (wasRunning && data.is_terminal) {
            stopPolling();
            notifyJob.value = { ...liveJob.value, search_label: props.job.search_label };
            router.reload({ only: ['job', 'logs'] });
        }
    } catch {}
};

const startPolling = () => {
    if (pollInterval) return;
    pollInterval = setInterval(poll, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    if (!isTerminal()) startPolling();
});

onUnmounted(stopPolling);

watch(() => props.job, (val) => {
    Object.assign(liveJob.value, val);
    if (isTerminal()) stopPolling();
});

const statusClasses = {
    pending: 'bg-label-secondary',
    running: 'bg-label-info',
    completed: 'bg-label-success',
    failed: 'bg-label-danger',
};

const logLevelClasses = {
    info: 'text-muted',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-danger',
};

const logLevelIcons = {
    info: '○',
    success: '✓',
    warning: '⚠',
    error: '✗',
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleString() : '—';
const formatDuration = (secs) => {
    if (!secs) return '—';
    if (secs < 60) return `${secs}s`;
    return `${Math.floor(secs / 60)}m ${secs % 60}s`;
};
</script>

<template>
    <Head :title="`Scrape: ${job.keyword}`" />
    <AdminLayout>
        <template #header>
            <div class="d-flex align-items-center gap-3">
                <Link :href="route('scraper.index')" class="btn btn-icon btn-text-secondary">
                    <i class="ri-arrow-left-s-line ri-22px" />
                </Link>
                <div>
                    <h4 class="mb-0 fw-bold">{{ job.keyword }}</h4>
                    <p class="mb-0 small text-muted">{{ job.search_label }}</p>
                </div>
            </div>
        </template>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill text-capitalize" :class="statusClasses[liveJob.status] ?? 'bg-label-secondary'">
                            <span v-if="liveJob.status === 'running'" class="spinner-grow spinner-grow-sm me-1" role="status" />
                            {{ liveJob.status }}
                        </span>
                        <span v-if="liveJob.scraper_used" class="small text-muted text-capitalize">
                            via {{ liveJob.scraper_used?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 small text-muted">
                        <span v-if="job.started_at">Started: {{ formatDate(job.started_at) }}</span>
                        <span v-if="job.completed_at">Completed: {{ formatDate(job.completed_at) }}</span>
                        <span v-if="job.duration_seconds">Duration: {{ formatDuration(job.duration_seconds) }}</span>
                    </div>
                </div>

                <div v-if="job.error_message" class="alert alert-danger mt-3 mb-0">
                    {{ job.error_message }}
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h3 class="mb-1">{{ liveJob.total_found }}</h3>
                        <small class="text-muted">Total Found</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100 border-success">
                    <div class="card-body">
                        <h3 class="mb-1 text-success">{{ liveJob.created_count }}</h3>
                        <small class="text-muted">Created</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100 border-warning">
                    <div class="card-body">
                        <h3 class="mb-1 text-warning">{{ liveJob.duplicate_count }}</h3>
                        <small class="text-muted">Duplicates</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100 border-danger">
                    <div class="card-body">
                        <h3 class="mb-1 text-danger">{{ liveJob.failed_count }}</h3>
                        <small class="text-muted">Failed</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Job Configuration</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-md-4">
                        <div class="text-muted">Source</div>
                        <div class="fw-medium">{{ job.source_label ?? job.source_channel }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Keyword</div>
                        <div class="fw-medium">{{ job.keyword }}</div>
                    </div>
                    <div v-if="job.industry" class="col-md-4">
                        <div class="text-muted">Industry</div>
                        <div class="fw-medium">{{ job.industry }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Country</div>
                        <div class="fw-medium">{{ job.country }}</div>
                    </div>
                    <div v-if="job.city" class="col-md-4">
                        <div class="text-muted">City</div>
                        <div class="fw-medium">{{ job.city }}</div>
                    </div>
                    <div v-if="job.area" class="col-md-4">
                        <div class="text-muted">Area</div>
                        <div class="fw-medium">{{ job.area }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Max Results</div>
                        <div class="fw-medium">{{ job.max_results }}</div>
                    </div>
                    <div v-if="job.creator" class="col-md-4">
                        <div class="text-muted">Created by</div>
                        <div class="fw-medium">{{ job.creator.name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Queued at</div>
                        <div class="fw-medium">{{ formatDate(job.created_at) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Run Logs</h5>
                <span class="small text-muted">{{ logs.length }} entries</span>
            </div>

            <div v-if="liveJob.status === 'pending'" class="card-body text-center text-muted">
                Waiting for scraper service to pick up this job…
            </div>

            <div v-else-if="logs.length === 0 && liveJob.status === 'running'" class="card-body text-center text-muted">
                <span class="spinner-border spinner-border-sm text-primary me-2" role="status" />
                Scraping in progress…
            </div>

            <div v-else-if="logs.length === 0" class="card-body text-center text-muted">
                No log entries.
            </div>

            <ul v-else class="list-group list-group-flush font-monospace small" style="max-height: 500px; overflow-y: auto;">
                <li
                    v-for="log in logs"
                    :key="log.id"
                    class="list-group-item d-flex align-items-start gap-3"
                >
                    <span class="flex-shrink-0" :class="logLevelClasses[log.level] ?? 'text-muted'">
                        {{ logLevelIcons[log.level] ?? '·' }}
                    </span>
                    <span class="text-muted flex-shrink-0">
                        {{ new Date(log.created_at).toLocaleTimeString() }}
                    </span>
                    <span class="flex-grow-1 text-break" :class="logLevelClasses[log.level] ?? ''">
                        {{ log.message }}
                    </span>
                    <span v-if="log.context" class="text-muted flex-shrink-0">
                        {{ log.context?.status ?? '' }}
                    </span>
                </li>
            </ul>
        </div>

        <ScraperNotification :job="notifyJob" @close="notifyJob = null" />
    </AdminLayout>
</template>
