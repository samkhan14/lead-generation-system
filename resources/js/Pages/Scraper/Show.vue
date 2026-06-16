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
            // Reload page for latest logs
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
    pending: 'bg-slate-100 text-slate-700',
    running: 'bg-blue-100 text-blue-700',
    completed: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
};

const logLevelClasses = {
    info: 'text-slate-500',
    success: 'text-emerald-600',
    warning: 'text-amber-600',
    error: 'text-red-600',
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
            <div class="flex items-center gap-3">
                <Link :href="route('scraper.index')" class="text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ job.keyword }}</h2>
                    <p class="text-xs text-slate-400">{{ job.search_label }}</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Status Header -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium capitalize"
                            :class="statusClasses[liveJob.status] ?? 'bg-slate-100 text-slate-700'"
                        >
                            <span
                                v-if="liveJob.status === 'running'"
                                class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"
                            />
                            {{ liveJob.status }}
                        </span>
                        <span v-if="liveJob.scraper_used" class="text-xs text-slate-400 capitalize">
                            via {{ liveJob.scraper_used?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                        <span v-if="job.started_at">Started: {{ formatDate(job.started_at) }}</span>
                        <span v-if="job.completed_at">Completed: {{ formatDate(job.completed_at) }}</span>
                        <span v-if="job.duration_seconds">Duration: {{ formatDuration(job.duration_seconds) }}</span>
                    </div>
                </div>

                <div v-if="job.error_message" class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ job.error_message }}
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-center">
                    <div class="text-2xl font-bold text-slate-800">{{ liveJob.total_found }}</div>
                    <div class="text-xs text-slate-500 mt-1">Total Found</div>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm text-center">
                    <div class="text-2xl font-bold text-emerald-700">{{ liveJob.created_count }}</div>
                    <div class="text-xs text-emerald-600 mt-1">Created</div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-5 shadow-sm text-center">
                    <div class="text-2xl font-bold text-amber-700">{{ liveJob.duplicate_count }}</div>
                    <div class="text-xs text-amber-600 mt-1">Duplicates</div>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-5 shadow-sm text-center">
                    <div class="text-2xl font-bold text-red-600">{{ liveJob.failed_count }}</div>
                    <div class="text-xs text-red-500 mt-1">Failed</div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700 uppercase tracking-wide">Job Configuration</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-slate-400">Source</dt>
                        <dd class="font-medium text-slate-800">{{ job.source_label ?? job.source_channel }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Keyword</dt>
                        <dd class="font-medium text-slate-800">{{ job.keyword }}</dd>
                    </div>
                    <div v-if="job.industry">
                        <dt class="text-slate-400">Industry</dt>
                        <dd class="font-medium text-slate-800">{{ job.industry }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Country</dt>
                        <dd class="font-medium text-slate-800">{{ job.country }}</dd>
                    </div>
                    <div v-if="job.city">
                        <dt class="text-slate-400">City</dt>
                        <dd class="font-medium text-slate-800">{{ job.city }}</dd>
                    </div>
                    <div v-if="job.area">
                        <dt class="text-slate-400">Area</dt>
                        <dd class="font-medium text-slate-800">{{ job.area }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Max Results</dt>
                        <dd class="font-medium text-slate-800">{{ job.max_results }}</dd>
                    </div>
                    <div v-if="job.creator">
                        <dt class="text-slate-400">Created by</dt>
                        <dd class="font-medium text-slate-800">{{ job.creator.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Queued at</dt>
                        <dd class="font-medium text-slate-800">{{ formatDate(job.created_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Run Logs -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Run Logs</h3>
                    <span class="text-xs text-slate-400">{{ logs.length }} entries</span>
                </div>

                <div v-if="liveJob.status === 'pending'" class="px-6 py-8 text-center text-sm text-slate-400">
                    Waiting for scraper service to pick up this job…
                </div>

                <div v-else-if="logs.length === 0 && liveJob.status === 'running'"
                    class="px-6 py-8 text-center text-sm text-slate-400">
                    <svg class="mx-auto mb-2 h-5 w-5 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Scraping in progress…
                </div>

                <div v-else-if="logs.length === 0" class="px-6 py-8 text-center text-sm text-slate-400">
                    No log entries.
                </div>

                <div v-else class="divide-y divide-slate-50 font-mono text-xs max-h-[500px] overflow-y-auto">
                    <div
                        v-for="log in logs"
                        :key="log.id"
                        class="flex items-start gap-3 px-6 py-2 hover:bg-slate-50"
                    >
                        <span class="shrink-0 w-4 text-center" :class="logLevelClasses[log.level] ?? 'text-slate-400'">
                            {{ logLevelIcons[log.level] ?? '·' }}
                        </span>
                        <span class="text-slate-300 shrink-0 whitespace-nowrap">
                            {{ new Date(log.created_at).toLocaleTimeString() }}
                        </span>
                        <span :class="logLevelClasses[log.level] ?? 'text-slate-600'" class="flex-1 break-all">
                            {{ log.message }}
                        </span>
                        <span v-if="log.context" class="text-slate-300 shrink-0">
                            {{ log.context?.status ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Completion Notification -->
        <ScraperNotification
            :job="notifyJob"
            @close="notifyJob = null"
        />
    </AdminLayout>
</template>
