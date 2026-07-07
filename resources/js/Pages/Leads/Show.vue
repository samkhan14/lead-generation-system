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
    high: 'bg-label-danger',
    medium: 'bg-label-warning',
    low: 'bg-label-secondary',
};

const sourceLabels = {
    google_maps: 'Google Maps',
    yelp: 'Yelp',
    hotfrog: 'Hotfrog',
    yellow_pages: 'Yellow Pages',
    manta: 'Manta',
    foursquare: 'Foursquare',
    the_manifest: 'The Manifest',
    goodfirms: 'GoodFirms',
    designrush: 'DesignRush',
    upcity: 'UpCity',
    openstreetmap: 'OpenStreetMap',
    bing_places: 'Bing Places',
    reddit: 'Reddit',
};

const isReddit = computed(() => props.lead.source === 'reddit');
const isYelp = computed(() => props.lead.source === 'yelp');
const isHotfrog = computed(() => props.lead.source === 'hotfrog');
const isOsm = computed(() => props.lead.source === 'openstreetmap');
const isDirectory = computed(() => [
    'google_maps', 'yelp', 'hotfrog', 'yellow_pages', 'manta', 'foursquare',
    'the_manifest', 'goodfirms', 'designrush', 'upcity', 'openstreetmap', 'bing_places',
].includes(props.lead.source));

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
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100 animate-fade-in">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h4 class="mb-0 fw-bold">{{ lead.full_name }}</h4>
                    <TemperatureBadge :temperature="lead.latest_score?.temperature" />
                    <VerificationBadge :status="verificationStatus" />
                    <span
                        v-if="lead.source"
                        class="badge rounded-pill text-capitalize"
                        :class="isReddit ? 'bg-label-warning' : 'bg-label-primary'"
                    >
                        {{ sourceLabel }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>Back to Leads</SecondaryButton>
                    </Link>
                    <DangerButton v-if="can('leads.delete')" @click="deleteLead">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="vstack gap-4 pb-4 mx-auto" style="max-width: 64rem;">
            <!-- Intelligence -->
            <div
                v-if="lead.latest_score"
                class="card animate-fade-slide-up"
                :style="sectionDelay(0)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">Lead intelligence</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Engine {{ lead.latest_score.scoring_version ?? 'current' }} — weighted final score
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="display-5 fw-bold mb-0">{{ lead.latest_score.score }}</div>
                        <div class="small fw-medium text-muted">Grade {{ lead.latest_score.score_grade }}</div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <IntelligenceScoreCard
                                title="Intent"
                                :score="lead.latest_score.intent_score ?? intelligence().intent?.score ?? 0"
                                :signals="intelligence().intent?.signals ?? []"
                                accent="indigo"
                                :delay="100"
                            />
                        </div>
                        <div class="col-lg-4">
                            <IntelligenceScoreCard
                                title="Opportunity"
                                :score="lead.latest_score.opportunity_score ?? intelligence().opportunity?.score ?? 0"
                                :signals="intelligence().opportunity?.signals ?? []"
                                accent="emerald"
                                :delay="180"
                            />
                        </div>
                        <div class="col-lg-4">
                            <IntelligenceScoreCard
                                title="Authenticity"
                                :score="lead.latest_score.authenticity_score ?? intelligence().authenticity?.score ?? 0"
                                :signals="intelligence().authenticity?.signals ?? []"
                                accent="sky"
                                :delay="260"
                            />
                        </div>
                    </div>

                    <div v-if="intelligence().final?.weights" class="alert alert-secondary small mb-0 mt-3 py-2">
                        Final = (Intent × {{ intelligence().final.weights.intent }})
                        + (Opportunity × {{ intelligence().final.weights.opportunity }})
                        + (Authenticity × {{ intelligence().final.weights.authenticity }})
                    </div>
                </div>
            </div>

            <!-- OpenStreetMap context -->
            <div
                v-if="isOsm"
                class="card animate-fade-slide-up"
                :style="sectionDelay(1)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">OpenStreetMap listing</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Community-sourced map data — free directory with address and contact tags.
                        </p>
                    </div>
                    <span class="badge bg-label-success">Warm lead</span>
                </div>

                <div class="card-body">
                    <dl class="row g-3 mb-0">
                        <div v-if="metadata.osm_id" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">OSM ID</dt>
                            <dd class="mb-0 mt-1">{{ metadata.osm_id }}</dd>
                        </div>
                        <div v-if="metadata.scrape_keyword" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Discovered via</dt>
                            <dd class="mb-0 mt-1">
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
                            class="btn btn-success btn-sm"
                        >
                            View on OpenStreetMap
                        </a>
                    </div>
                </div>
            </div>

            <!-- Yelp context -->
            <div
                v-if="isYelp"
                class="card animate-fade-slide-up"
                :style="sectionDelay(1)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">Yelp listing</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Directory profile — Yelp page is separate from the business website.
                        </p>
                    </div>
                    <span class="badge bg-label-danger">Warm lead</span>
                </div>

                <div class="card-body">
                    <dl class="row g-3 mb-0">
                        <div v-if="metadata.yelp_business_id" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Business ID</dt>
                            <dd class="mb-0 mt-1">{{ metadata.yelp_business_id }}</dd>
                        </div>
                        <div v-if="metadata.rating" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Rating</dt>
                            <dd class="mb-0 mt-1">
                                {{ metadata.rating }}
                                <span v-if="metadata.review_count" class="text-muted">
                                    ({{ metadata.review_count }} reviews)
                                </span>
                            </dd>
                        </div>
                        <div v-if="metadata.scrape_keyword" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Discovered via</dt>
                            <dd class="mb-0 mt-1">
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
                            class="btn btn-danger btn-sm"
                        >
                            View listing on Yelp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hotfrog context -->
            <div
                v-if="isHotfrog"
                class="card animate-fade-slide-up"
                :style="sectionDelay(1)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">Hotfrog listing</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Global business directory — Hotfrog profile is separate from the business website.
                        </p>
                    </div>
                    <span class="badge bg-label-warning">Warm lead</span>
                </div>

                <div class="card-body">
                    <dl class="row g-3 mb-0">
                        <div v-if="metadata.hotfrog_business_id" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Business ID</dt>
                            <dd class="mb-0 mt-1">{{ metadata.hotfrog_business_id }}</dd>
                        </div>
                        <div v-if="metadata.scrape_keyword" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Discovered via</dt>
                            <dd class="mb-0 mt-1">
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
                            class="btn btn-warning btn-sm"
                        >
                            View listing on Hotfrog
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reddit context -->
            <div
                v-if="isReddit"
                class="card animate-fade-slide-up"
                :style="sectionDelay(1)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">Reddit context</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Active intent post — reply helpfully in-thread first, then follow up.
                        </p>
                    </div>
                    <span class="badge bg-label-warning text-capitalize">{{ leadKindLabel }}</span>
                </div>

                <div class="card-body">
                    <dl class="row g-3 mb-0">
                        <div v-if="metadata.subreddit" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Subreddit</dt>
                            <dd class="mb-0 mt-1">
                                <a
                                    :href="`https://www.reddit.com/r/${metadata.subreddit}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="link-primary"
                                >
                                    r/{{ metadata.subreddit }}
                                </a>
                            </dd>
                        </div>
                        <div v-if="metadata.author" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Author</dt>
                            <dd class="mb-0 mt-1">u/{{ metadata.author }}</dd>
                        </div>
                        <div v-if="postedAt" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Posted</dt>
                            <dd class="mb-0 mt-1">{{ postedAt }}</dd>
                        </div>
                        <div v-if="metadata.upvotes !== undefined || metadata.comment_count !== undefined" class="col-sm-6 col-lg-4">
                            <dt class="small text-uppercase text-muted">Engagement</dt>
                            <dd class="mb-0 mt-1">
                                {{ metadata.upvotes ?? 0 }} upvotes · {{ metadata.comment_count ?? 0 }} comments
                            </dd>
                        </div>
                        <div v-if="metadata.intent_keywords_matched?.length" class="col-sm-12 col-lg-8">
                            <dt class="small text-uppercase text-muted">Intent signals</dt>
                            <dd class="mb-0 mt-1 d-flex flex-wrap gap-1">
                                <span
                                    v-for="keyword in metadata.intent_keywords_matched"
                                    :key="keyword"
                                    class="badge bg-label-secondary"
                                >
                                    {{ keyword }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <div v-if="metadata.post_title" class="alert alert-secondary mt-4 mb-0 py-3">
                        <div class="small fw-semibold text-uppercase text-muted">Original post</div>
                        <p class="mb-0 mt-1 fw-medium">{{ metadata.post_title }}</p>
                    </div>

                    <div v-if="metadata.post_url" class="mt-4">
                        <a
                            :href="metadata.post_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-warning btn-sm"
                        >
                            View original post on Reddit
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lead details -->
            <div
                class="card animate-fade-slide-up"
                :style="sectionDelay(2)"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Lead details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Email</dt>
                                    <dd class="mb-0 mt-1">
                                        <a
                                            v-if="lead.email"
                                            :href="`mailto:${lead.email}`"
                                            class="link-primary"
                                        >
                                            {{ lead.email }}
                                        </a>
                                        <span v-else class="text-muted">Not available</span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Phone</dt>
                                    <dd class="mb-0 mt-1">
                                        <a
                                            v-if="lead.phone"
                                            :href="`tel:${formatPhoneLink(lead.phone)}`"
                                            class="link-primary"
                                        >
                                            {{ lead.phone }}
                                        </a>
                                        <span v-else class="text-muted">Not available</span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Website</dt>
                                    <dd class="mb-0 mt-1">
                                        <a
                                            v-if="externalWebsite"
                                            :href="externalWebsite"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="link-primary text-break"
                                        >
                                            {{ lead.website }}
                                        </a>
                                        <span v-else class="text-muted">
                                            {{ isDirectory ? 'No business website listed' : 'Not available' }}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Company</dt>
                                    <dd class="mb-0 mt-1 fw-medium">{{ lead.company || '—' }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Job title</dt>
                                    <dd class="mb-0 mt-1">{{ lead.job_title || '—' }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Source</dt>
                                    <dd class="mb-0 mt-1 text-capitalize">{{ sourceLabel }}</dd>
                                </div>
                            </div>
                        </div>
                        <div v-if="metadata.address" class="col-sm-12">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Address</dt>
                                    <dd class="mb-0 mt-1">{{ metadata.address }}</dd>
                                </div>
                            </div>
                        </div>
                        <div v-if="metadata.rating" class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">{{ ratingLabel }}</dt>
                                    <dd class="mb-0 mt-1">
                                        <span class="fw-semibold">{{ metadata.rating }}</span>
                                        <span v-if="metadata.review_count" class="text-muted">
                                            · {{ metadata.review_count }} reviews
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Assigned to</dt>
                                    <dd class="mb-0 mt-1">{{ lead.assigned_to || '—' }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-label-secondary h-100">
                                <div class="card-body py-3">
                                    <dt class="small text-uppercase text-muted">Created by</dt>
                                    <dd class="mb-0 mt-1">{{ lead.created_by || '—' }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="lead.notes" class="alert alert-secondary mt-4 mb-0">
                        <dt class="small text-uppercase text-muted">Notes</dt>
                        <dd class="mb-0 mt-2" style="white-space: pre-wrap;">{{ lead.notes }}</dd>
                    </div>
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
                class="card animate-fade-slide-up"
                :style="sectionDelay(4)"
            >
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">What to pitch</h5>
                        <p class="card-subtitle text-muted mb-0 small">
                            Suggested services based on lead gaps, directory signals, and contact details.
                        </p>
                    </div>
                    <span
                        v-if="lead.latest_score?.temperature"
                        class="badge bg-label-secondary text-uppercase"
                    >
                        {{ lead.latest_score.temperature }} lead
                    </span>
                </div>

                <div class="card-body">
                    <div v-if="lead.pitch_recommendations?.length" class="vstack gap-3">
                        <div
                            v-for="(recommendation, index) in lead.pitch_recommendations"
                            :key="recommendation.service"
                            class="card animate-fade-slide-up"
                            :style="{ animationDelay: `${index * 60}ms` }"
                        >
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <h6 class="fw-semibold mb-0">{{ recommendation.service }}</h6>
                                    <span
                                        class="badge rounded-pill text-capitalize"
                                        :class="priorityClasses[recommendation.priority] ?? priorityClasses.low"
                                    >
                                        {{ recommendation.priority }} priority
                                    </span>
                                </div>
                                <p class="small text-body-secondary mt-2 mb-0">{{ recommendation.reason }}</p>
                                <div class="alert alert-primary mb-0 mt-3 py-3">
                                    <div class="small fw-semibold text-uppercase">Suggested opener</div>
                                    <p class="mb-0 mt-1 small">{{ recommendation.opener }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="alert alert-secondary mb-0">
                        No pitch recommendation available yet. Add contact, website, or directory profile data to improve suggestions.
                    </div>
                </div>
            </div>

            <!-- Website Analysis / Verification -->
            <div
                class="animate-fade-slide-up"
                :style="sectionDelay(5)"
            >
                <div v-if="reverifyMessage" class="alert alert-primary mb-3">
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
                class="card animate-fade-slide-up"
                :style="sectionDelay(6)"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Score history</h5>
                </div>
                <div v-if="lead.scores.length === 0" class="card-body text-muted small">
                    No scores recorded yet.
                </div>
                <ul v-else class="list-group list-group-flush">
                    <li
                        v-for="(score, index) in lead.scores"
                        :key="score.id"
                        class="list-group-item animate-fade-slide-up"
                        :style="{ animationDelay: `${index * 50}ms` }"
                    >
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-5 fw-semibold">
                                    {{ score.score }}
                                    <span class="fs-6 fw-normal text-muted">({{ score.score_grade }})</span>
                                </span>
                                <TemperatureBadge :temperature="score.temperature" />
                            </div>
                            <span class="small text-muted">{{ score.calculated_at }}</span>
                        </div>
                        <div v-if="score.intent_score !== undefined" class="d-flex gap-3 small text-muted mt-2">
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
