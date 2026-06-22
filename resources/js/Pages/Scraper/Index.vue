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

// Notification only for jobs we saw enter "running" on this page
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

const tierBadgeClass = (tier) => tier === 'hot'
    ? 'bg-orange-100 text-orange-700'
    : 'bg-indigo-100 text-indigo-700';

const statusClasses = {
    pending: 'bg-slate-100 text-slate-600',
    running: 'bg-blue-100 text-blue-700',
    completed: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
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
            <h2 class="text-lg font-semibold text-slate-800">Lead Scraper</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Job Form -->
            <div v-if="can('scraper.run')" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-slate-800">New Scrape Job</h3>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Lead Source <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.source_channel"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <optgroup v-if="channel_groups.warm?.length" label="Warm leads (directories)">
                                    <option
                                        v-for="channel in channel_groups.warm"
                                        :key="channel.value"
                                        :value="channel.value"
                                    >
                                        {{ channel.label }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="channel_groups.hot?.length" label="Hot leads (intent / social)">
                                    <option
                                        v-for="channel in channel_groups.hot"
                                        :key="channel.value"
                                        :value="channel.value"
                                    >
                                        {{ channel.label }}
                                    </option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.source_channel" class="mt-1 text-xs text-red-600">{{ form.errors.source_channel }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                {{ activeChannel.keyword_label ?? 'Business type' }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.keyword"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option value="" disabled>
                                    {{ isReddit ? 'Select intent keyword' : 'Select business type' }}
                                </option>
                                <optgroup
                                    v-for="group in keywordGroups"
                                    :key="group.label"
                                    :label="group.label"
                                >
                                    <option
                                        v-for="opt in group.options"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.keyword" class="mt-1 text-xs text-red-600">{{ form.errors.keyword }}</p>
                            <p v-if="form.source_channel === 'reddit'" class="mt-1 text-xs text-slate-400">
                                Subreddits are chosen automatically from your selected country.
                            </p>
                            <p v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'yelp'" class="mt-1 text-xs text-amber-600">
                                Yelp blocks browser scraping — set YELP_API_KEY on the SRP service.
                            </p>
                            <p v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'openstreetmap'" class="mt-1 text-xs text-emerald-600">
                                Free OSM data — city recommended for accurate results.
                            </p>
                            <p v-else-if="activeChannel.tier === 'warm' && form.source_channel === 'bing_places'" class="mt-1 text-xs text-slate-400">
                                Playwright scrapes Bing Maps first. Set AZURE_MAPS_KEY on SRP only as fallback.
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Country
                                <span v-if="activeChannel.requires_location" class="text-red-500">*</span>
                                <span v-else class="text-slate-400 text-xs font-normal">(optional)</span>
                            </label>
                            <select
                                v-model="form.country"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                :required="activeChannel.requires_location"
                            >
                                <option value="" disabled>Select country</option>
                                <option
                                    v-for="country in countries"
                                    :key="country"
                                    :value="country"
                                >
                                    {{ country }}
                                </option>
                            </select>
                            <p v-if="form.errors.country" class="mt-1 text-xs text-red-600">{{ form.errors.country }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                City <span class="text-slate-400 text-xs font-normal">(optional)</span>
                            </label>
                            <input
                                v-model="form.city"
                                type="text"
                                placeholder="e.g. Karachi"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Area <span class="text-slate-400 text-xs font-normal">(optional)</span>
                            </label>
                            <input
                                v-model="form.area"
                                type="text"
                                placeholder="e.g. Clifton, DHA"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Max Results</label>
                            <input
                                v-model.number="form.max_results"
                                type="number"
                                min="1"
                                max="100"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 transition-colors"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ form.processing ? 'Starting...' : 'Start Scrape' }}
                        </button>
                        <p class="text-xs text-slate-400">
                            Source: {{ activeChannel.label ?? form.source_channel }}
                            <span v-if="activeChannel.tier === 'warm'"> · directory listing</span>
                            <span v-else-if="activeChannel.tier === 'hot'"> · intent / social</span>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Job History -->
            <DataTable
                title="Job History"
                :is-empty="jobs.data.length === 0"
                empty-message="No scrape jobs yet. Run your first job above."
            >
                <template #head>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Search</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Found</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Created</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Dupes</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Failed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Scraper</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6">Run at</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 sm:px-6"></th>
                    </tr>
                </template>

                <tr
                    v-for="job in jobs.data"
                    :key="job.uuid"
                    class="hover:bg-slate-50 transition-colors"
                >
                    <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                        <span
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="tierBadgeClass(channelTier(job.source_channel))"
                        >
                            {{ channelLabel(job.source_channel) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 sm:px-6">
                        <div class="text-sm font-medium text-slate-800">{{ job.keyword }}</div>
                        <div class="max-w-xs truncate text-xs text-slate-400">
                            {{ locationLabel(job) }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                            :class="statusClasses[job.status] ?? 'bg-slate-100 text-slate-600'"
                        >
                            <span
                                v-if="job.status === 'running'"
                                class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"
                            />
                            {{ job.status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-slate-600 sm:px-6">{{ job.total_found }}</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-emerald-600 sm:px-6">{{ job.created_count }}</td>
                    <td class="px-4 py-3 text-center text-sm text-amber-600 sm:px-6">{{ job.duplicate_count }}</td>
                    <td class="px-4 py-3 text-center text-sm text-red-500 sm:px-6">{{ job.failed_count }}</td>
                    <td class="px-4 py-3 text-xs capitalize text-slate-500 sm:px-6">
                        {{ job.scraper_used?.replace('_', ' ') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 sm:px-6">
                        {{ formatDate(job.created_at) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                        <Link
                            :href="route('scraper.show', job.uuid)"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800"
                        >
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
        </div>

        <!-- Completion Notification -->
        <ScraperNotification
            :job="notifyJob"
            @close="notifyJob = null"
        />
    </AdminLayout>
</template>
