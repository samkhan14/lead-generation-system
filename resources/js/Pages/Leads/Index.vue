<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
            pitch_types: [],
            sorts: [],
        }),
    },
});

const { can } = useAuth();

const local = ref({
    q: props.filters.q ?? '',
    country: props.filters.country ?? '',
    city: props.filters.city ?? '',
    area: props.filters.area ?? '',
    keyword: props.filters.keyword ?? '',
    source: props.filters.source ?? '',
    pitch_type: props.filters.pitch_type ?? '',
    has_website: props.filters.has_website ?? '',
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
        || local.value.temperature
        || local.value.sort !== 'created_desc',
    );
});

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
            <div class="flex w-full items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-slate-900">Leads</h1>
                <Link v-if="can('leads.create')" :href="route('leads.create')">
                    <PrimaryButton>New Lead</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="mb-4 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="!local.temperature ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    @click="setTemperature(null)"
                >
                    All
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="isActive('hot') ? 'bg-red-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    @click="setTemperature('hot')"
                >
                    HOT
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="isActive('warm') ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    @click="setTemperature('warm')"
                >
                    WARM
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="isActive('cold') ? 'bg-sky-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    @click="setTemperature('cold')"
                >
                    COLD
                </button>
            </div>

            <form
                class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                        <input
                            v-model="local.q"
                            type="search"
                            placeholder="Name, email, phone..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Country</label>
                        <select
                            v-model="local.country"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option value="">All countries</option>
                            <option v-for="country in filterOptions.countries" :key="country" :value="country">
                                {{ country }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">City</label>
                        <input
                            v-model="local.city"
                            type="search"
                            list="lead-cities"
                            placeholder="Search city..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                        <datalist id="lead-cities">
                            <option v-for="city in filterOptions.cities" :key="city" :value="city" />
                        </datalist>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Area</label>
                        <input
                            v-model="local.area"
                            type="search"
                            list="lead-areas"
                            placeholder="Search area..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                        <datalist id="lead-areas">
                            <option v-for="area in filterOptions.areas" :key="area" :value="area" />
                        </datalist>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Scrape keyword</label>
                        <select
                            v-model="local.keyword"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option value="">All keywords</option>
                            <option v-for="keyword in filterOptions.keywords" :key="keyword" :value="keyword">
                                {{ keyword }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Source</label>
                        <select
                            v-model="local.source"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option value="">All sources</option>
                            <option v-for="source in filterOptions.sources" :key="source.value" :value="source.value">
                                {{ source.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Pitch type</label>
                        <select
                            v-model="local.pitch_type"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option value="">All pitch types</option>
                            <option v-for="pitch in filterOptions.pitch_types" :key="pitch.value" :value="pitch.value">
                                {{ pitch.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Website</label>
                        <select
                            v-model="local.has_website"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option value="">Any</option>
                            <option value="yes">Has website</option>
                            <option value="no">No website</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Sort</label>
                        <select
                            v-model="local.sort"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option v-for="sort in filterOptions.sorts" :key="sort.value" :value="sort.value">
                                {{ sort.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Per page</label>
                        <select
                            v-model.number="local.per_page"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            @change="onSelectChange"
                        >
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        Apply filters
                    </button>
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
                        @click="clearFilters"
                    >
                        Clear all
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div class="text-sm text-slate-600">
                    Showing
                    <span class="font-medium text-slate-900">{{ leads.from ?? 0 }}</span>
                    to
                    <span class="font-medium text-slate-900">{{ leads.to ?? 0 }}</span>
                    of
                    <span class="font-medium text-slate-900">{{ leads.total }}</span>
                    leads
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Lead</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Website</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Google</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pitch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Temp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm">
                                <Link :href="route('leads.show', lead.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ lead.full_name }}
                                </Link>
                                <div v-if="lead.scrape_keyword" class="mt-0.5 text-xs text-slate-400">
                                    {{ lead.scrape_keyword }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div v-if="locationLabel(lead)" class="max-w-xs truncate">
                                    {{ locationLabel(lead) }}
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div>{{ lead.email || '—' }}</div>
                                <div v-if="lead.phone" class="text-xs text-slate-500">{{ lead.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <a
                                    v-if="lead.website"
                                    :href="lead.website"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-indigo-600 hover:text-indigo-800"
                                >
                                    Visit
                                </a>
                                <span v-else class="rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700">
                                    No website
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div v-if="lead.rating">
                                    {{ lead.rating }} ★
                                    <span v-if="lead.review_count" class="text-xs text-slate-500">
                                        ({{ lead.review_count }})
                                    </span>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div v-if="lead.pitch_summary" class="max-w-56">
                                    <div class="font-medium text-slate-800">{{ lead.pitch_summary.service }}</div>
                                    <div class="mt-0.5 text-xs capitalize text-slate-500">
                                        {{ lead.pitch_summary.priority }} priority
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lead.source || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div v-if="lead.latest_score">
                                    <div>{{ lead.latest_score.score }} ({{ lead.latest_score.score_grade }})</div>
                                    <div class="text-xs text-slate-500">
                                        I{{ lead.latest_score.intent_score ?? '—' }}
                                        O{{ lead.latest_score.opportunity_score ?? '—' }}
                                        A{{ lead.latest_score.authenticity_score ?? '—' }}
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                            </td>
                        </tr>
                        <tr v-if="leads.data.length === 0">
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">
                                No leads match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="leads.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                <div class="text-sm text-slate-500">
                    Page {{ leads.current_page }} of {{ leads.last_page }}
                </div>
                <div class="flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="link in leads.links"
                        :key="link.label"
                        :href="link.url"
                        class="rounded-md px-3 py-1.5 text-sm"
                        :class="link.active
                            ? 'bg-indigo-600 text-white'
                            : link.url
                                ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'
                                : 'cursor-not-allowed bg-slate-50 text-slate-300'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
