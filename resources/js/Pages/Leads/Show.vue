<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TemperatureBadge from '@/Components/Admin/TemperatureBadge.vue';
import VerificationBadge from '@/Components/Admin/VerificationBadge.vue';
import WebsiteAnalysisPanel from '@/Components/Admin/WebsiteAnalysisPanel.vue';
import LeadWorkforcePanel from '@/Components/Admin/LeadWorkforcePanel.vue';
import IntelligenceScoreCard from '@/Components/Admin/IntelligenceScoreCard.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
    workforce: {
        type: Object,
        default: () => ({}),
    },
});

const { can } = useAuth();

const deleteLead = () => {
    if (confirm('Delete this lead?')) {
        router.delete(route('leads.destroy', props.lead.id));
    }
};

const intelligence = () => props.lead.latest_score?.factors ?? {};
const metadata = computed(() => props.lead.metadata ?? {});
const websiteAnalysis = computed(() => metadata.value.website_analysis ?? null);
const verificationStatus = computed(() => metadata.value.verification?.status ?? null);

const reverifying = ref(false);
const reverifyMessage = ref(null);

const reverify = () => {
    reverifying.value = true;
    reverifyMessage.value = null;
    router.post(route('leads.reverify', props.lead.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            reverifyMessage.value = 'Verification queued. Results will appear after the job runs.';
        },
        onError: () => {
            reverifyMessage.value = 'Failed to queue verification. Please try again.';
        },
        onFinish: () => {
            reverifying.value = false;
        },
    });
};

const priorityClasses = {
    high: 'bg-red-50 text-red-700 ring-red-100',
    medium: 'bg-amber-50 text-amber-700 ring-amber-100',
    low: 'bg-slate-50 text-slate-700 ring-slate-100',
};

const sourceLabels = {
    google_maps: 'Google Maps',
    yelp: 'Yelp',
    hotfrog: 'Hotfrog',
    openstreetmap: 'OpenStreetMap',
    reddit: 'Reddit',
};

const isReddit = computed(() => props.lead.source === 'reddit');
const isYelp = computed(() => props.lead.source === 'yelp');
const isHotfrog = computed(() => props.lead.source === 'hotfrog');
const isOsm = computed(() => props.lead.source === 'openstreetmap');
const isDirectory = computed(() => ['google_maps', 'yelp', 'hotfrog', 'openstreetmap', 'bing_places'].includes(props.lead.source));

const sourceLabel = computed(
    () => sourceLabels[props.lead.source] ?? props.lead.source ?? '—',
);

const ratingLabel = computed(() => {
    if (isYelp.value) return 'Yelp rating';
    if (props.lead.source === 'google_maps') return 'Google rating';
    return 'Rating';
});

const yelpUrl = computed(() => metadata.value.yelp_url ?? null);
const hotfrogUrl = computed(() => metadata.value.hotfrog_url ?? null);
const osmUrl = computed(() => metadata.value.osm_url ?? null);

const externalWebsite = computed(() => {
    const url = props.lead.website;
    if (!url) return null;
    return url.startsWith('http') ? url : `https://${url}`;
});

const leadKindLabels = {
    service_request: 'Service request',
    problem_post: 'Problem post',
    feedback_request: 'Feedback request',
    local_recommendation: 'Local recommendation',
};

const leadKindLabel = computed(
    () => leadKindLabels[metadata.value.lead_kind] ?? metadata.value.lead_kind ?? '—',
);

const postedAt = computed(() => {
    if (!metadata.value.posted_at) {
        return null;
    }

    return new Date(metadata.value.posted_at).toLocaleString();
});

const sectionDelay = (index) => ({
    animationDelay: `${index * 80}ms`,
});

const formatPhoneLink = (phone) => phone?.replace(/[^\d+]/g, '') ?? '';
</script>

<template>
    <Head :title="lead.full_name" />

    <AdminLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-4 animate-fade-in">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-semibold text-slate-900">{{ lead.full_name }}</h1>
                    <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                    <VerificationBadge :status="verificationStatus" />
                    <span
                        v-if="lead.source"
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1"
                        :class="isReddit
                            ? 'bg-orange-50 text-orange-700 ring-orange-100'
                            : 'bg-indigo-50 text-indigo-700 ring-indigo-100'"
                    >
                        {{ sourceLabel }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>Back to Leads</SecondaryButton>
                    </Link>
                    <DangerButton v-if="can('leads.delete')" @click="deleteLead">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 pb-8">
            <!-- Intelligence -->
            <div
                v-if="lead.latest_score"
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                :style="sectionDelay(0)"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Lead intelligence</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Engine {{ lead.latest_score.scoring_version ?? 'current' }} — weighted final score
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold tracking-tight text-slate-900 transition-transform duration-300 hover:scale-105">
                            {{ lead.latest_score.score }}
                        </div>
                        <div class="text-sm font-medium text-slate-500">Grade {{ lead.latest_score.score_grade }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <IntelligenceScoreCard
                        title="Intent"
                        :score="lead.latest_score.intent_score ?? intelligence().intent?.score ?? 0"
                        :signals="intelligence().intent?.signals ?? []"
                        accent="indigo"
                        :delay="100"
                    />
                    <IntelligenceScoreCard
                        title="Opportunity"
                        :score="lead.latest_score.opportunity_score ?? intelligence().opportunity?.score ?? 0"
                        :signals="intelligence().opportunity?.signals ?? []"
                        accent="emerald"
                        :delay="180"
                    />
                    <IntelligenceScoreCard
                        title="Authenticity"
                        :score="lead.latest_score.authenticity_score ?? intelligence().authenticity?.score ?? 0"
                        :signals="intelligence().authenticity?.signals ?? []"
                        accent="sky"
                        :delay="260"
                    />
                </div>

                <div v-if="intelligence().final?.weights" class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                    Final = (Intent × {{ intelligence().final.weights.intent }})
                    + (Opportunity × {{ intelligence().final.weights.opportunity }})
                    + (Authenticity × {{ intelligence().final.weights.authenticity }})
                </div>
            </div>

            <!-- OpenStreetMap context -->
            <div
                v-if="isOsm"
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-emerald-100 transition-shadow hover:shadow-md"
                :style="sectionDelay(1)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">OpenStreetMap listing</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Community-sourced map data — free directory with address and contact tags.
                        </p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100">
                        Warm lead
                    </span>
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-if="metadata.osm_id">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">OSM ID</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ metadata.osm_id }}</dd>
                    </div>
                    <div v-if="metadata.scrape_keyword">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Discovered via</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ metadata.scrape_keyword }}
                            <span v-if="metadata.scrape_city"> in {{ metadata.scrape_city }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="osmUrl" class="mt-4">
                    <a
                        :href="osmUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
                    >
                        View on OpenStreetMap
                    </a>
                </div>
            </div>

            <!-- Yelp context -->
            <div
                v-if="isYelp"
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-red-100 transition-shadow hover:shadow-md"
                :style="sectionDelay(1)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Yelp listing</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Directory profile — Yelp page is separate from the business website.
                        </p>
                    </div>
                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 ring-1 ring-red-100">
                        Warm lead
                    </span>
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-if="metadata.yelp_business_id">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Business ID</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ metadata.yelp_business_id }}</dd>
                    </div>
                    <div v-if="metadata.rating">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Rating</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ metadata.rating }}
                            <span v-if="metadata.review_count" class="text-slate-500">
                                ({{ metadata.review_count }} reviews)
                            </span>
                        </dd>
                    </div>
                    <div v-if="metadata.scrape_keyword">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Discovered via</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ metadata.scrape_keyword }}
                            <span v-if="metadata.scrape_city"> in {{ metadata.scrape_city }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="yelpUrl" class="mt-4">
                    <a
                        :href="yelpUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    >
                        View listing on Yelp
                    </a>
                </div>
            </div>

            <!-- Hotfrog context -->
            <div
                v-if="isHotfrog"
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-orange-100 transition-shadow hover:shadow-md"
                :style="sectionDelay(1)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Hotfrog listing</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Global business directory — Hotfrog profile is separate from the business website.
                        </p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700 ring-1 ring-orange-100">
                        Warm lead
                    </span>
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-if="metadata.hotfrog_business_id">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Business ID</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ metadata.hotfrog_business_id }}</dd>
                    </div>
                    <div v-if="metadata.scrape_keyword">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Discovered via</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ metadata.scrape_keyword }}
                            <span v-if="metadata.scrape_city"> in {{ metadata.scrape_city }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="hotfrogUrl" class="mt-4">
                    <a
                        :href="hotfrogUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700"
                    >
                        View listing on Hotfrog
                    </a>
                </div>
            </div>

            <!-- Reddit context -->
            <div
                v-if="isReddit"
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-orange-100 transition-shadow hover:shadow-md"
                :style="sectionDelay(1)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Reddit context</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Active intent post — reply helpfully in-thread first, then follow up.
                        </p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-medium capitalize text-orange-700 ring-1 ring-orange-100">
                        {{ leadKindLabel }}
                    </span>
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-if="metadata.subreddit">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Subreddit</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a
                                :href="`https://www.reddit.com/r/${metadata.subreddit}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-indigo-600 hover:text-indigo-800"
                            >
                                r/{{ metadata.subreddit }}
                            </a>
                        </dd>
                    </div>
                    <div v-if="metadata.author">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Author</dt>
                        <dd class="mt-1 text-sm text-slate-900">u/{{ metadata.author }}</dd>
                    </div>
                    <div v-if="postedAt">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Posted</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ postedAt }}</dd>
                    </div>
                    <div v-if="metadata.upvotes !== undefined || metadata.comment_count !== undefined">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Engagement</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ metadata.upvotes ?? 0 }} upvotes · {{ metadata.comment_count ?? 0 }} comments
                        </dd>
                    </div>
                    <div v-if="metadata.intent_keywords_matched?.length" class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Intent signals</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="keyword in metadata.intent_keywords_matched"
                                :key="keyword"
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700"
                            >
                                {{ keyword }}
                            </span>
                        </dd>
                    </div>
                </dl>

                <div v-if="metadata.post_title" class="mt-4 rounded-lg bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Original post</div>
                    <p class="mt-1 text-sm font-medium text-slate-800">{{ metadata.post_title }}</p>
                </div>

                <div v-if="metadata.post_url" class="mt-4">
                    <a
                        :href="metadata.post_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700"
                    >
                        View original post on Reddit
                    </a>
                </div>
            </div>

            <!-- Lead details -->
            <div
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                :style="sectionDelay(2)"
            >
                <h3 class="text-lg font-semibold text-slate-900">Lead details</h3>
                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">
                            <a
                                v-if="lead.email"
                                :href="`mailto:${lead.email}`"
                                class="text-indigo-600 hover:text-indigo-800"
                            >
                                {{ lead.email }}
                            </a>
                            <span v-else class="text-slate-400">Not available</span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Phone</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">
                            <a
                                v-if="lead.phone"
                                :href="`tel:${formatPhoneLink(lead.phone)}`"
                                class="text-indigo-600 hover:text-indigo-800"
                            >
                                {{ lead.phone }}
                            </a>
                            <span v-else class="text-slate-400">Not available</span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Website</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">
                            <a
                                v-if="externalWebsite"
                                :href="externalWebsite"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="break-all text-indigo-600 hover:text-indigo-800"
                            >
                                {{ lead.website }}
                            </a>
                            <span v-else class="text-slate-400">
                                {{ isDirectory ? 'No business website listed' : 'Not available' }}
                            </span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Company</dt>
                        <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ lead.company || '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Job title</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ lead.job_title || '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Source</dt>
                        <dd class="mt-1.5 text-sm capitalize text-slate-900">{{ sourceLabel }}</dd>
                    </div>
                    <div
                        v-if="metadata.address"
                        class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200 sm:col-span-2"
                    >
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Address</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ metadata.address }}</dd>
                    </div>
                    <div
                        v-if="metadata.rating"
                        class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200"
                    >
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ ratingLabel }}</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">
                            <span class="font-semibold">{{ metadata.rating }}</span>
                            <span v-if="metadata.review_count" class="text-slate-500">
                                · {{ metadata.review_count }} reviews
                            </span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Assigned to</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ lead.assigned_to || '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Created by</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ lead.created_by || '—' }}</dd>
                    </div>
                </dl>
                <div v-if="lead.notes" class="mt-5 rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Notes</dt>
                    <dd class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ lead.notes }}</dd>
                </div>
            </div>

            <!-- AI Workforce -->
            <LeadWorkforcePanel
                :workforce="workforce"
                :lead-id="lead.id"
                :section-delay="sectionDelay(3)"
            />

            <!-- Pitch recommendations -->
            <div
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                :style="sectionDelay(4)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">What to pitch</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Suggested services based on lead gaps, directory signals, and contact details.
                        </p>
                    </div>
                    <span
                        v-if="lead.latest_score?.temperature"
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase text-slate-600"
                    >
                        {{ lead.latest_score.temperature }} lead
                    </span>
                </div>

                <div v-if="lead.pitch_recommendations?.length" class="mt-5 grid gap-4">
                    <div
                        v-for="(recommendation, index) in lead.pitch_recommendations"
                        :key="recommendation.service"
                        class="animate-fade-slide-up rounded-xl border border-slate-200 p-5 transition-all duration-300 hover:border-indigo-200 hover:shadow-md"
                        :style="{ animationDelay: `${index * 60}ms` }"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-semibold text-slate-900">{{ recommendation.service }}</h4>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1"
                                :class="priorityClasses[recommendation.priority] ?? priorityClasses.low"
                            >
                                {{ recommendation.priority }} priority
                            </span>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ recommendation.reason }}</p>
                        <div class="mt-3 rounded-lg bg-indigo-50/60 p-3 ring-1 ring-indigo-100/80">
                            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-500">
                                Suggested opener
                            </div>
                            <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ recommendation.opener }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No pitch recommendation available yet. Add contact, website, or directory profile data to improve suggestions.
                </div>
            </div>

            <!-- Website Analysis / Verification -->
            <div
                class="animate-fade-slide-up"
                :style="sectionDelay(5)"
            >
                <div v-if="reverifyMessage" class="mb-3 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-700 ring-1 ring-indigo-100">
                    {{ reverifyMessage }}
                </div>
                <WebsiteAnalysisPanel
                    :analysis="websiteAnalysis"
                    :verification-status="verificationStatus"
                    :verified-at="lead.verified_at"
                    :lead-id="lead.id"
                    @reverify="reverify"
                />
            </div>

            <!-- Score history -->
            <div
                class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                :style="sectionDelay(6)"
            >
                <h3 class="text-lg font-semibold text-slate-900">Score history</h3>
                <div v-if="lead.scores.length === 0" class="mt-4 text-sm text-slate-500">
                    No scores recorded yet.
                </div>
                <ul v-else class="mt-4 divide-y divide-slate-100">
                    <li
                        v-for="(score, index) in lead.scores"
                        :key="score.id"
                        class="animate-fade-slide-up py-4 transition-colors hover:bg-slate-50/80"
                        :style="{ animationDelay: `${index * 50}ms` }"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2 px-2">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-semibold text-slate-900">
                                    {{ score.score }}
                                    <span class="text-sm font-normal text-slate-500">({{ score.score_grade }})</span>
                                </span>
                                <TemperatureBadge :temperature="score.temperature" />
                            </div>
                            <span class="text-sm text-slate-500">{{ score.calculated_at }}</span>
                        </div>
                        <div v-if="score.intent_score !== undefined" class="mt-2 flex gap-4 px-2 text-xs text-slate-500">
                            <span>Intent {{ score.intent_score }}</span>
                            <span>Opportunity {{ score.opportunity_score }}</span>
                            <span>Authenticity {{ score.authenticity_score }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
