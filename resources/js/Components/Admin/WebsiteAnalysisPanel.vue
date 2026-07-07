<script setup>
import { computed } from 'vue';

const props = defineProps({
    analysis: {
        type: Object,
        default: null,
    },
    verificationStatus: {
        type: String,
        default: null,
    },
    verifiedAt: {
        type: String,
        default: null,
    },
    leadId: {
        type: Number,
        required: true,
    },
});

defineEmits(['reverify']);

const revampConfig = {
    high: { label: 'High', classes: 'bg-label-danger' },
    medium: { label: 'Medium', classes: 'bg-label-warning' },
    low: { label: 'Low', classes: 'bg-label-success' },
};

const opportunityLabels = {
    booking: 'Booking System',
    live_chat: 'Live Chat',
    analytics: 'Analytics',
    crm_integration: 'CRM Integration',
    lead_capture_form: 'Lead Capture Form',
};

const qualityBarClass = computed(() => {
    const score = props.analysis?.quality_score ?? 0;
    if (score >= 70) return 'bg-success';
    if (score >= 40) return 'bg-warning';
    return 'bg-danger';
});

const qualityLabel = computed(() => {
    const score = props.analysis?.quality_score ?? 0;
    if (score >= 70) return 'Good';
    if (score >= 40) return 'Fair';
    return 'Poor';
});

const formattedVerifiedAt = computed(() => {
    if (!props.verifiedAt) return null;
    return new Date(props.verifiedAt).toLocaleString();
});
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Website Verification</h5>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="$emit('reverify')"
            >
                Re-run Verification
            </button>
        </div>

        <div class="card-body">
            <div v-if="!analysis" class="text-muted">
                <p class="mb-0">No website analysis available yet.</p>
                <p v-if="verificationStatus === 'partial' || verificationStatus === 'unverified'" class="mb-0 mt-1 small">
                    Verification is in progress or the website could not be discovered.
                </p>
            </div>

            <template v-else>
                <div v-if="!analysis.exists" class="alert alert-danger mb-4">
                    Website did not respond during analysis.
                    <span v-if="analysis.error" class="d-block small mt-1">{{ analysis.error }}</span>
                </div>

                <template v-else>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small text-body-secondary mb-1">
                            <span>Website Quality</span>
                            <span class="fw-semibold">{{ analysis.quality_score }}/100 — {{ qualityLabel }}</span>
                        </div>
                        <div class="progress">
                            <div
                                class="progress-bar"
                                :class="qualityBarClass"
                                role="progressbar"
                                :style="{ width: `${analysis.quality_score}%` }"
                            />
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="card bg-label-secondary text-center py-2">
                                <div class="fs-5" :class="analysis.is_mobile_responsive ? 'text-success' : 'text-danger'">
                                    {{ analysis.is_mobile_responsive ? '✓' : '✗' }}
                                </div>
                                <div class="small text-muted">Mobile Ready</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-label-secondary text-center py-2">
                                <div class="fs-5" :class="analysis.has_contact_info ? 'text-success' : 'text-danger'">
                                    {{ analysis.has_contact_info ? '✓' : '✗' }}
                                </div>
                                <div class="small text-muted">Contact Info</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-label-secondary text-center py-2">
                                <div class="fs-5" :class="analysis.has_analytics ? 'text-success' : 'text-muted'">
                                    {{ analysis.has_analytics ? '✓' : '—' }}
                                </div>
                                <div class="small text-muted">Analytics</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-label-secondary text-center py-2">
                                <div class="small fw-medium">{{ analysis.copyright_year ?? '—' }}</div>
                                <div class="small text-muted">Copyright</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="analysis.tech_stack?.length" class="mb-4">
                        <p class="small text-uppercase text-muted mb-2">Tech Stack</p>
                        <div class="d-flex flex-wrap gap-1">
                            <span
                                v-for="tech in analysis.tech_stack"
                                :key="tech"
                                class="badge bg-label-secondary"
                            >
                                {{ tech }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center justify-content-between">
                        <span class="small text-uppercase text-muted">Revamp Potential</span>
                        <span
                            v-if="analysis.revamp_potential"
                            class="badge"
                            :class="revampConfig[analysis.revamp_potential]?.classes"
                        >
                            {{ revampConfig[analysis.revamp_potential]?.label }}
                        </span>
                    </div>

                    <div v-if="analysis.automation_opportunities?.length">
                        <p class="small text-uppercase text-muted mb-2">Automation Opportunities</p>
                        <div class="d-flex flex-wrap gap-1">
                            <span
                                v-for="opp in analysis.automation_opportunities"
                                :key="opp"
                                class="badge bg-label-primary"
                            >
                                {{ opportunityLabels[opp] ?? opp }}
                            </span>
                        </div>
                    </div>
                </template>

                <p v-if="analysis.analyzed_at" class="mb-0 mt-3 small text-muted">
                    Analyzed {{ new Date(analysis.analyzed_at).toLocaleString() }}
                    <span v-if="analysis.pages_analyzed"> · {{ analysis.pages_analyzed }} page(s)</span>
                </p>
            </template>

            <p v-if="formattedVerifiedAt" class="mb-0 mt-3 pt-3 border-top small text-muted">
                Last verified: {{ formattedVerifiedAt }}
            </p>
        </div>
    </div>
</template>
