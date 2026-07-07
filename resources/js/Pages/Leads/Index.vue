<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BulkVoiceCallModal from '@/Components/Admin/BulkVoiceCallModal.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
import VerificationBadge from '@/Components/Admin/VerificationBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    leads: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({
            countries: [],
            cities: [],
            areas: [],
            keywords: [],
            sources: [],
            subreddits: [],
            lead_kinds: [],
            posted_within: [],
            pitch_types: [],
            sorts: [],
        }),
    },
    voiceCallOptions: {
        type: Object,
        default: () => ({
            can_start_voice_call: false,
            voice_call_blockers: [],
            voice_employees: [],
            default_employee_id: null,
            max_bulk_leads: 50,
        }),
    },
});

const page = usePage();
const { can } = useAuth();

const selectedIds = ref([]);
const showBulkCallModal = ref(false);

watch(() => props.leads.data, () => {
    const visibleIds = new Set(props.leads.data.map((lead) => lead.id));
    selectedIds.value = selectedIds.value.filter((id) => visibleIds.has(id));
});

const selectedLeads = computed(() =>
    props.leads.data.filter((lead) => selectedIds.value.includes(lead.id)),
);

const allOnPageSelected = computed(() =>
    props.leads.data.length > 0
    && props.leads.data.every((lead) => selectedIds.value.includes(lead.id)),
);

const someOnPageSelected = computed(() =>
    props.leads.data.some((lead) => selectedIds.value.includes(lead.id)),
);

const bulkResult = computed(() => page.props.flash?.bulk_voice_call_result ?? null);

const toggleLead = (leadId) => {
    if (selectedIds.value.includes(leadId)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== leadId);
    } else if (selectedIds.value.length < props.voiceCallOptions.max_bulk_leads) {
        selectedIds.value = [...selectedIds.value, leadId];
    }
};

const toggleAllOnPage = () => {
    if (allOnPageSelected.value) {
        const pageIds = new Set(props.leads.data.map((lead) => lead.id));
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.has(id));
    } else {
        const merged = new Set(selectedIds.value);
        for (const lead of props.leads.data) {
            if (merged.size >= props.voiceCallOptions.max_bulk_leads) {
                break;
            }
            merged.add(lead.id);
        }
        selectedIds.value = [...merged];
    }
};

const clearSelection = () => {
    selectedIds.value = [];
};

const openBulkCallModal = () => {
    if (selectedLeads.value.length === 0) {
        return;
    }
    showBulkCallModal.value = true;
};

const local = ref({
    q: props.filters.q ?? '',
    country: props.filters.country ?? '',
    city: props.filters.city ?? '',
    area: props.filters.area ?? '',
    keyword: props.filters.keyword ?? '',
    source: props.filters.source ?? '',
    pitch_type: props.filters.pitch_type ?? '',
    has_website: props.filters.has_website ?? '',
    subreddit: props.filters.subreddit ?? '',
    lead_kind: props.filters.lead_kind ?? '',
    posted_within: props.filters.posted_within ?? '',
    status: props.filters.status ?? '',
    assigned_to: props.filters.assigned_to ?? '',
    sort: props.filters.sort ?? 'created_desc',
    per_page: props.filters.per_page ?? 15,
    temperature: props.filters.temperature ?? null,
});

const hasActiveFilters = computed(() => {
    return Boolean(
        local.value.q
        || local.value.country
        || local.value.city
        || local.value.area
        || local.value.keyword
        || local.value.source
        || local.value.pitch_type
        || local.value.has_website
        || local.value.subreddit
        || local.value.lead_kind
        || local.value.posted_within
        || local.value.status
        || local.value.assigned_to
        || local.value.temperature
        || local.value.sort !== 'created_desc',
    );
});

const showRedditFilters = computed(
    () => !local.value.source || local.value.source === 'reddit',
);

const buildParams = (overrides = {}) => {
    const params = {
        q: local.value.q || undefined,
        country: local.value.country || undefined,
        city: local.value.city || undefined,
        area: local.value.area || undefined,
        keyword: local.value.keyword || undefined,
        source: local.value.source || undefined,
        pitch_type: local.value.pitch_type || undefined,
        has_website: local.value.has_website || undefined,
        subreddit: local.value.subreddit || undefined,
        lead_kind: local.value.lead_kind || undefined,
        posted_within: local.value.posted_within || undefined,
        status: local.value.status || undefined,
        assigned_to: local.value.assigned_to || undefined,
        sort: local.value.sort !== 'created_desc' ? local.value.sort : undefined,
        per_page: local.value.per_page,
        temperature: local.value.temperature || undefined,
        ...overrides,
    };

    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ''),
    );
};

const reload = (overrides = {}) => {
    router.get(route('leads.index'), buildParams(overrides), {
        preserveState: true,
        replace: true,
    });
};

const applyFilters = () => reload({ page: undefined });

const setTemperature = (temperature) => {
    local.value.temperature = temperature;
    reload({ page: undefined });
};

const onSelectChange = () => reload({ page: undefined });

const clearFilters = () => {
    local.value = {
        q: '',
        country: '',
        city: '',
        area: '',
        keyword: '',
        source: '',
        pitch_type: '',
        has_website: '',
        subreddit: '',
        lead_kind: '',
        posted_within: '',
        status: '',
        assigned_to: '',
        sort: 'created_desc',
        per_page: local.value.per_page,
        temperature: null,
    };
    reload({ page: undefined });
};

const isActive = (temperature) => local.value.temperature === temperature;

const locationLabel = (lead) => {
    const parts = [lead.scrape_area, lead.scrape_city, lead.scrape_country].filter(Boolean);

    if (parts.length) {
        return parts.join(', ');
    }

    return lead.address || null;
};
</script>

<template>
    <Head title="Leads" />

    <AdminLayout>
        <template #header>
            <div class="d-flex w-100 align-items-center justify-content-between gap-3">
                <h4 class="mb-0 fw-bold">Leads</h4>
                <div class="d-flex gap-2">
                    <Link :href="route('leads.pipeline')">
                        <SecondaryButton type="button">Pipeline</SecondaryButton>
                    </Link>
                    <Link v-if="can('leads.create')" :href="route('leads.create')">
                        <PrimaryButton type="button">New Lead</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="mb-4">
            <div class="btn-group mb-4" role="group" aria-label="Temperature filters">
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="!local.temperature ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setTemperature(null)"
                >
                    All
                </button>
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="isActive('hot') ? 'btn-danger' : 'btn-outline-secondary'"
                    @click="setTemperature('hot')"
                >
                    HOT
                </button>
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="isActive('warm') ? 'btn-warning' : 'btn-outline-secondary'"
                    @click="setTemperature('warm')"
                >
                    WARM
                </button>
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="isActive('cold') ? 'btn-info' : 'btn-outline-secondary'"
                    @click="setTemperature('cold')"
                >
                    COLD
                </button>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form @submit.prevent="applyFilters">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input
                                    v-model="local.q"
                                    type="search"
                                    placeholder="Name, email, phone..."
                                    class="form-control form-control-sm"
                                />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <select
                                    v-model="local.country"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">All countries</option>
                                    <option v-for="country in filterOptions.countries" :key="country" :value="country">
                                        {{ country }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input
                                    v-model="local.city"
                                    type="search"
                                    list="lead-cities"
                                    placeholder="Search city..."
                                    class="form-control form-control-sm"
                                />
                                <datalist id="lead-cities">
                                    <option v-for="city in filterOptions.cities" :key="city" :value="city" />
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Area</label>
                                <input
                                    v-model="local.area"
                                    type="search"
                                    list="lead-areas"
                                    placeholder="Search area..."
                                    class="form-control form-control-sm"
                                />
                                <datalist id="lead-areas">
                                    <option v-for="area in filterOptions.areas" :key="area" :value="area" />
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Scrape keyword</label>
                                <select
                                    v-model="local.keyword"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">All keywords</option>
                                    <option v-for="keyword in filterOptions.keywords" :key="keyword" :value="keyword">
                                        {{ keyword }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Source</label>
                                <select
                                    v-model="local.source"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">All sources</option>
                                    <option v-for="source in filterOptions.sources" :key="source.value" :value="source.value">
                                        {{ source.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Pitch type</label>
                                <select
                                    v-model="local.pitch_type"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">All pitch types</option>
                                    <option v-for="pitch in filterOptions.pitch_types" :key="pitch.value" :value="pitch.value">
                                        {{ pitch.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Pipeline stage</label>
                                <select
                                    v-model="local.status"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">All stages</option>
                                    <option
                                        v-for="stage in filterOptions.pipeline_stages ?? []"
                                        :key="stage.value"
                                        :value="stage.value"
                                    >
                                        {{ stage.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Assignment</label>
                                <select
                                    v-model="local.assigned_to"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">Anyone</option>
                                    <option value="me">Assigned to me</option>
                                    <option value="unassigned">Unassigned</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Website</label>
                                <select
                                    v-model="local.has_website"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option value="">Any</option>
                                    <option value="yes">Has website</option>
                                    <option value="no">No website</option>
                                </select>
                            </div>

                            <template v-if="showRedditFilters">
                                <div class="col-md-4">
                                    <label class="form-label">Subreddit</label>
                                    <select
                                        v-model="local.subreddit"
                                        class="form-select form-select-sm"
                                        @change="onSelectChange"
                                    >
                                        <option value="">All subreddits</option>
                                        <option v-for="subreddit in filterOptions.subreddits" :key="subreddit" :value="subreddit">
                                            r/{{ subreddit }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Reddit intent</label>
                                    <select
                                        v-model="local.lead_kind"
                                        class="form-select form-select-sm"
                                        @change="onSelectChange"
                                    >
                                        <option value="">All intents</option>
                                        <option v-for="kind in filterOptions.lead_kinds" :key="kind.value" :value="kind.value">
                                            {{ kind.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Posted</label>
                                    <select
                                        v-model="local.posted_within"
                                        class="form-select form-select-sm"
                                        @change="onSelectChange"
                                    >
                                        <option value="">Any time</option>
                                        <option v-for="window in filterOptions.posted_within" :key="window.value" :value="window.value">
                                            {{ window.label }}
                                        </option>
                                    </select>
                                </div>
                            </template>

                            <div class="col-md-4">
                                <label class="form-label">Sort</label>
                                <select
                                    v-model="local.sort"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option v-for="sort in filterOptions.sorts" :key="sort.value" :value="sort.value">
                                        {{ sort.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Per page</label>
                                <select
                                    v-model.number="local.per_page"
                                    class="form-select form-select-sm"
                                    @change="onSelectChange"
                                >
                                    <option :value="10">10</option>
                                    <option :value="15">15</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Apply filters
                            </button>
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                @click="clearFilters"
                            >
                                Clear all
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="bulkResult" class="alert alert-success mb-4" role="alert">
            Queued <strong>{{ bulkResult.queued }}</strong> voice call{{ bulkResult.queued === 1 ? '' : 's' }}.
            <span v-if="bulkResult.skipped > 0">
                Skipped {{ bulkResult.skipped }} (duplicate active call, missing phone, or limit reached).
            </span>
        </div>

        <div
            v-if="page.props.errors?.bulk_voice_call"
            class="alert alert-danger mb-4"
            role="alert"
        >
            {{ page.props.errors.bulk_voice_call }}
        </div>

        <div
            v-if="voiceCallOptions.can_start_voice_call && selectedIds.length > 0"
            class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"
            role="alert"
        >
            <div class="small mb-0">
                <span class="fw-medium">{{ selectedIds.length }}</span> lead{{ selectedIds.length === 1 ? '' : 's' }} selected
                <span class="text-muted">(max {{ voiceCallOptions.max_bulk_leads }} per batch)</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <SecondaryButton type="button" @click="clearSelection">Clear</SecondaryButton>
                <PrimaryButton type="button" @click="openBulkCallModal">
                    Start bulk AI voice calls
                </PrimaryButton>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="small text-muted mb-0">
                    Showing
                    <span class="fw-medium text-body">{{ leads.from ?? 0 }}</span>
                    to
                    <span class="fw-medium text-body">{{ leads.to ?? 0 }}</span>
                    of
                    <span class="fw-medium text-body">{{ leads.total }}</span>
                    leads
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th v-if="voiceCallOptions.can_start_voice_call">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="allOnPageSelected"
                                    :indeterminate="someOnPageSelected && !allOnPageSelected"
                                    @change="toggleAllOnPage"
                                />
                            </th>
                            <th>Lead</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Website</th>
                            <th>Google</th>
                            <th>Pitch</th>
                            <th>Source</th>
                            <th>Score</th>
                            <th>Verified</th>
                            <th>Temp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in leads.data" :key="lead.id">
                            <td v-if="voiceCallOptions.can_start_voice_call">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="selectedIds.includes(lead.id)"
                                    :disabled="!lead.phone && !selectedIds.includes(lead.id)"
                                    @change="toggleLead(lead.id)"
                                />
                            </td>
                            <td>
                                <Link :href="route('leads.show', lead.id)" class="fw-medium link-primary">
                                    {{ lead.full_name }}
                                </Link>
                                <div v-if="lead.scrape_keyword" class="small text-muted">
                                    {{ lead.scrape_keyword }}
                                </div>
                            </td>
                            <td>
                                <div v-if="locationLabel(lead)" class="text-truncate" style="max-width: 12rem;">
                                    {{ locationLabel(lead) }}
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td>
                                <div>{{ lead.email || '—' }}</div>
                                <div v-if="lead.phone" class="small text-muted">{{ lead.phone }}</div>
                            </td>
                            <td>
                                <a
                                    v-if="lead.website"
                                    :href="lead.website"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="link-primary"
                                >
                                    Visit
                                </a>
                                <span v-else class="badge bg-label-danger">
                                    No website
                                </span>
                            </td>
                            <td>
                                <div v-if="lead.rating">
                                    {{ lead.rating }} ★
                                    <span v-if="lead.review_count" class="small text-muted">
                                        ({{ lead.review_count }})
                                    </span>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td>
                                <div v-if="lead.pitch_summary" style="max-width: 14rem;">
                                    <div class="fw-medium">{{ lead.pitch_summary.service }}</div>
                                    <div class="small text-muted text-capitalize">
                                        {{ lead.pitch_summary.priority }} priority
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td>{{ lead.source || '—' }}</td>
                            <td>
                                <div v-if="lead.latest_score">
                                    <div>{{ lead.latest_score.score }} ({{ lead.latest_score.score_grade }})</div>
                                    <div class="small text-muted">
                                        I{{ lead.latest_score.intent_score ?? '—' }}
                                        O{{ lead.latest_score.opportunity_score ?? '—' }}
                                        A{{ lead.latest_score.authenticity_score ?? '—' }}
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td>
                                <VerificationBadge :status="lead.verification_status" size="xs" />
                            </td>
                            <td>
                                <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                            </td>
                        </tr>
                        <tr v-if="leads.data.length === 0">
                            <td :colspan="voiceCallOptions.can_start_voice_call ? 11 : 10" class="text-center text-muted py-5">
                                No leads match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="leads.links?.length > 3"
                class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-top"
            >
                <div class="small text-muted">
                    Page {{ leads.current_page }} of {{ leads.last_page }}
                </div>
                <nav aria-label="Leads pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            v-for="link in leads.links"
                            :key="`${link.label}-${link.url}`"
                            class="page-item"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                        >
                            <component
                                :is="link.url ? Link : 'span'"
                                :href="link.url"
                                class="page-link"
                                v-html="link.label"
                            />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <BulkVoiceCallModal
            :show="showBulkCallModal"
            :selected-leads="selectedLeads"
            :voice-call-options="voiceCallOptions"
            @close="showBulkCallModal = false"
        />
    </AdminLayout>
</template>
