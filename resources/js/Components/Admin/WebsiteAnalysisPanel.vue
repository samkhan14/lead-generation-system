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

const emit = defineEmits(['reverify']);

const revampConfig = {
    high: { label: 'High', classes: 'bg-red-50 text-red-700 ring-red-100' },
    medium: { label: 'Medium', classes: 'bg-amber-50 text-amber-700 ring-amber-100' },
    low: { label: 'Low', classes: 'bg-green-50 text-green-700 ring-green-100' },
};

const opportunityLabels = {
    booking: 'Booking System',
    live_chat: 'Live Chat',
    analytics: 'Analytics',
    crm_integration: 'CRM Integration',
    lead_capture_form: 'Lead Capture Form',
};

const qualityColor = computed(() => {
    const score = props.analysis?.quality_score ?? 0;
    if (score >= 70) return 'bg-emerald-500';
    if (score >= 40) return 'bg-amber-500';
    return 'bg-red-500';
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
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800">Website Verification</h3>

            <button
                class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-200"
                @click="$emit('reverify')"
            >
                Re-run Verification
            </button>
        </div>

        <!-- No analysis yet -->
        <div v-if="!analysis" class="text-sm text-slate-500">
            <p>No website analysis available yet.</p>
            <p v-if="verificationStatus === 'partial' || verificationStatus === 'unverified'" class="mt-1 text-xs text-slate-400">
                Verification is in progress or the website could not be discovered.
            </p>
        </div>

        <template v-else>
            <!-- Website exists / not -->
            <div v-if="!analysis.exists" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                Website did not respond during analysis.
                <span v-if="analysis.error" class="block text-xs text-red-500 mt-1">{{ analysis.error }}</span>
            </div>

            <template v-else>
                <!-- Quality score bar -->
                <div class="mb-5">
                    <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                        <span>Website Quality</span>
                        <span class="font-semibold">{{ analysis.quality_score }}/100 — {{ qualityLabel }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            :class="['h-2 rounded-full transition-all', qualityColor]"
                            :style="{ width: `${analysis.quality_score}%` }"
                        />
                    </div>
                </div>

                <!-- Key signals row -->
                <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-center">
                        <div class="text-lg" :class="analysis.is_mobile_responsive ? 'text-emerald-600' : 'text-red-500'">
                            {{ analysis.is_mobile_responsive ? '✓' : '✗' }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">Mobile Ready</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-center">
                        <div class="text-lg" :class="analysis.has_contact_info ? 'text-emerald-600' : 'text-red-500'">
                            {{ analysis.has_contact_info ? '✓' : '✗' }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">Contact Info</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-center">
                        <div class="text-lg" :class="analysis.has_analytics ? 'text-emerald-600' : 'text-slate-400'">
                            {{ analysis.has_analytics ? '✓' : '—' }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">Analytics</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-center">
                        <div class="text-xs font-medium text-slate-700">{{ analysis.copyright_year ?? '—' }}</div>
                        <div class="mt-0.5 text-xs text-slate-600">Copyright</div>
                    </div>
                </div>

                <!-- Tech stack + CMS -->
                <div v-if="analysis.tech_stack?.length" class="mb-4">
                    <p class="mb-1.5 text-xs font-medium text-slate-500 uppercase tracking-wide">Tech Stack</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="tech in analysis.tech_stack"
                            :key="tech"
                            class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700"
                        >
                            {{ tech }}
                        </span>
                    </div>
                </div>

                <!-- Revamp potential -->
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Revamp Potential</span>
                    <span
                        v-if="analysis.revamp_potential"
                        :class="[
                            'rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                            revampConfig[analysis.revamp_potential]?.classes,
                        ]"
                    >
                        {{ revampConfig[analysis.revamp_potential]?.label }}
                    </span>
                </div>

                <!-- Automation opportunities -->
                <div v-if="analysis.automation_opportunities?.length" class="mb-3">
                    <p class="mb-1.5 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        Automation Opportunities
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="opp in analysis.automation_opportunities"
                            :key="opp"
                            class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-100"
                        >
                            {{ opportunityLabels[opp] ?? opp }}
                        </span>
                    </div>
                </div>
            </template>

            <!-- Analyzed at -->
            <p v-if="analysis.analyzed_at" class="mt-3 text-xs text-slate-400">
                Analyzed {{ new Date(analysis.analyzed_at).toLocaleString() }}
                <span v-if="analysis.pages_analyzed"> · {{ analysis.pages_analyzed }} page(s)</span>
            </p>
        </template>

        <!-- Verified at footer -->
        <p v-if="formattedVerifiedAt" class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-400">
            Last verified: {{ formattedVerifiedAt }}
        </p>
    </div>
</template>
