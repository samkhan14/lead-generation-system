<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ScraperNotification from '@/Components/Admin/ScraperNotification.vue';
import { useAuth } from '@/composables/useAuth';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    jobs: { type: Object, default: () => ({ data: [], links: [] }) },
    countries: { type: Array, default: () => [] },
    channels: { type: Array, default: () => [] },
    channel_groups: {
        type: Object,
        default: () => ({ warm: [], hot: [] }),
    },
    default_channel: { type: String, default: 'google_maps' },
    scrape_options: {
        type: Object,
        default: () => ({
            business_type_groups: [],
            intent_keyword_groups: [],
        }),
    },
    filters: {
        type: Object,
        default: () => ({ per_page: 15 }),
    },
});

const { can } = useAuth();

const form = useForm({
    source_channel: props.default_channel,
    keyword: '',
    country: '',
    city: '',
    area: '',
    max_results: 20,
});

const activeChannel = computed(
    () => props.channels.find((c) => c.value === form.source_channel) ?? props.channels[0] ?? {},
);

const isReddit = computed(() => form.source_channel === 'reddit');

const keywordGroups = computed(() =>
    isReddit.value
        ? props.scrape_options.intent_keyword_groups
        : props.scrape_options.business_type_groups,
);

watch(() => form.source_channel, () => {
    form.keyword = '';
});

const channelLabel = (value) =>
    props.channels.find((c) => c.value === value)?.label ?? value;

const submit = () => {
    form.post(route('scraper.store'), { preserveScroll: true });
};

const notifyJob = ref(null);
const watchedRunning = ref(new Set());
let pollInterval = null;

const runningJobs = () => (props.jobs?.data ?? []).filter(j => j.status === 'running');

const trackRunningJobs = () => {
    for (const job of runningJobs()) {
        watchedRunning.value.add(job.uuid);
    }
};

const startPolling = () => {
    if (pollInterval) return;
    pollInterval = setInterval(() => {
        if (watchedRunning.value.size === 0 && runningJobs().length === 0) {
            stopPolling();
            return;
        }
        router.reload({ only: ['jobs'], preserveScroll: true, onSuccess: () => {
            for (const job of props.jobs?.data ?? []) {
                if (
                    watchedRunning.value.has(job.uuid)
                    && (job.status === 'completed' || job.status === 'failed')
                ) {
                    notifyJob.value = job;
                    watchedRunning.value.delete(job.uuid);
                }
            }
            trackRunningJobs();
            if (watchedRunning.value.size === 0 && runningJobs().length === 0) {
                stopPolling();
            }
        }});
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    trackRunningJobs();
    if (watchedRunning.value.size > 0 || runningJobs().length > 0) startPolling();
});

onUnmounted(stopPolling);

watch(() => props.jobs, () => {
    trackRunningJobs();
    if (watchedRunning.value.size > 0 || runningJobs().length > 0) startPolling();
    else stopPolling();
}, { deep: true });

const tierBadgeClass = (tier) => tier === 'hot' ? 'bg-label-warning' : 'bg-label-primary';

const statusClasses = {
    pending: 'bg-label-secondary',
    running: 'bg-label-info',
    completed: 'bg-label-success',
    failed: 'bg-label-danger',
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleString() : '—';

const locationLabel = (job) => [job.city, job.area, job.country].filter(Boolean).join(', ') || '—';

const channelTier = (sourceChannel) =>
    props.channels.find((c) => c.value === sourceChannel)?.tier ?? 'warm';
</script>

<template>
    <Head title="Scraper" />
    <AdminLayout>
        <template #header>
            <h4 class="mb-0 fw-bold">Lead Scraper</h4>
        </template>

        <div v-if="can('scraper.run')" class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">New Scrape Job</h5>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                Lead Source <span class="text-danger">*</span>
                            </label>
                            <select v-model="form.source_channel" class="form-select">
                                <optgroup v-if="channel_groups.warm?.length" label="Warm leads (directories)">
                                    <option v-for="channel in channel_groups.warm" :key="channel.value" :value="channel.value">
                                        {{ channel.label }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="channel_groups.hot?.length" label="Hot leads (intent / social)">
                                    <option v-for="channel in channel_groups.hot" :key="channel.value" :value="channel.value">
                                        {{ channel.label }}
                                    </option>
                                </optgroup>
                            </select>
                            <div v-if="form.errors.source_channel" class="text-danger small mt-1">{{ form.errors.source_channel }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                {{ activeChannel.keyword_label ?? 'Business type' }} <span class="text-danger">*</span>
                            </label>
                            <select v-model="form.keyword" class="form-select" required>
                                <option value="" disabled>
                                    {{ isReddit ? 'Select intent keyword' : 'Select business type' }}
                                </option>
                                <optgroup v-for="group in keywordGroups" :key="group.label" :label="group.label">
                                    <option v-for="opt in group.options" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </optgroup>
                            </select>
                            <div v-if="form.errors.keyword" class="text-danger small mt-1">{{ form.errors.keyword }}</div>
                            <div v-if="form.source_channel === 'reddit'" class="form-text">Subreddits are chosen automatically from your selected country.</div>
                            <div v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'yelp'" class="form-text text-warning">Yelp blocks browser scraping — set YELP_API_KEY on the SRP service.</div>
                            <div v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'openstreetmap'" class="form-text text-success">Free OSM data — city recommended for accurate results.</div>
                            <div v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'bing_places'" class="form-text">Playwright scrapes Bing Maps first. Set AZURE_MAPS_KEY on SRP only as fallback.</div>
                            <div v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'hotfrog'" class="form-text">Global business directory — city recommended for accurate local results.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Country
                                <span v-if="activeChannel.requires_location" class="text-danger">*</span>
                                <span v-else class="text-muted small">(optional)</span>
                            </label>
                            <select v-model="form.country" class="form-select" :required="activeChannel.requires_location">
                                <option value="" disabled>Select country</option>
                                <option v-for="country in countries" :key="country" :value="country">{{ country }}</option>
                            </select>
                            <div v-if="form.errors.country" class="text-danger small mt-1">{{ form.errors.country }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City <span class="text-muted small">(optional)</span></label>
                            <input v-model="form.city" type="text" placeholder="e.g. Karachi" class="form-control" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Area <span class="text-muted small">(optional)</span></label>
                            <input v-model="form.area" type="text" placeholder="e.g. Clifton, DHA" class="form-control" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Max Results</label>
                            <input v-model.number="form.max_results" type="number" min="1" max="100" class="form-control" />
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                            <i v-else class="ri-search-line me-1" />
                            {{ form.processing ? 'Starting...' : 'Start Scrape' }}
                        </button>
                        <span class="small text-muted">
                            Source: {{ activeChannel.label ?? form.source_channel }}
                            <span v-if="activeChannel.tier === 'warm'"> · directory listing</span>
                            <span v-else-if="activeChannel.tier === 'hot'"> · intent / social</span>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <DataTable
            title="Job History"
            :is-empty="jobs.data.length === 0"
            empty-message="No scrape jobs yet. Run your first job above."
        >
            <template #head>
                <tr>
                    <th>Source</th>
                    <th>Search</th>
                    <th>Status</th>
                    <th class="text-center">Found</th>
                    <th class="text-center">Created</th>
                    <th class="text-center">Dupes</th>
                    <th class="text-center">Failed</th>
                    <th>Scraper</th>
                    <th>Run at</th>
                    <th></th>
                </tr>
            </template>

            <tr v-for="job in jobs.data" :key="job.uuid">
                <td>
                    <span class="badge rounded-pill" :class="tierBadgeClass(channelTier(job.source_channel))">
                        {{ channelLabel(job.source_channel) }}
                    </span>
                </td>
                <td>
                    <div class="fw-medium">{{ job.keyword }}</div>
                    <div class="small text-muted text-truncate" style="max-width: 12rem;">{{ locationLabel(job) }}</div>
                </td>
                <td>
                    <span class="badge rounded-pill text-capitalize" :class="statusClasses[job.status] ?? 'bg-label-secondary'">
                        <span v-if="job.status === 'running'" class="spinner-grow spinner-grow-sm me-1" role="status" />
                        {{ job.status }}
                    </span>
                </td>
                <td class="text-center">{{ job.total_found }}</td>
                <td class="text-center text-success fw-medium">{{ job.created_count }}</td>
                <td class="text-center text-warning">{{ job.duplicate_count }}</td>
                <td class="text-center text-danger">{{ job.failed_count }}</td>
                <td class="small text-capitalize text-muted">{{ job.scraper_used?.replace('_', ' ') ?? '—' }}</td>
                <td class="small text-muted text-nowrap">{{ formatDate(job.created_at) }}</td>
                <td>
                    <Link :href="route('scraper.show', job.uuid)" class="btn btn-sm btn-text-primary">
                        Details →
                    </Link>
                </td>
            </tr>

            <template #footer>
                <Pagination
                    :paginator="jobs"
                    item-label="jobs"
                    :per-page="filters.per_page"
                    route-name="scraper.index"
                    :query="{ per_page: filters.per_page }"
                    :only="['jobs', 'filters']"
                />
            </template>
        </DataTable>

        <ScraperNotification :job="notifyJob" @close="notifyJob = null" />
    </AdminLayout>
</template>
