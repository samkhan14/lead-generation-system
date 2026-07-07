<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    workforce: {
        type: Object,
        required: true,
    },
    leadId: {
        type: Number,
        required: true,
    },
    sectionDelay: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const activeTab = ref('activities');

const tabs = computed(() => {
    const items = [];

    if (props.workforce.can_view_ai) {
        items.push({ id: 'activities', label: 'AI Activities' });
    }

    if (props.workforce.can_view_voice) {
        items.push({ id: 'voice_calls', label: 'Voice Calls' });
    }

    if (props.workforce.can_view_ai || props.workforce.can_view_voice) {
        items.push({ id: 'timeline', label: 'Timeline' });
        items.push({ id: 'costs', label: 'Costs' });
    }

    return items;
});

const form = useForm({
    ai_employee_id: props.workforce.default_employee_id ?? '',
});

const startingCall = ref(false);
const flashMessage = computed(() => {
    if (page.props.flash?.voice_call_started) {
        return 'Voice call started. Track progress in the Voice Calls tab.';
    }

    return null;
});

const errorMessage = computed(() => page.props.errors?.voice_call ?? null);

const formatCost = (value) => {
    if (value == null) {
        return '—';
    }

    return `$${Number(value).toFixed(4)}`;
};

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '—');

const statusClass = (status) => ({
    completed: 'bg-label-success',
    success: 'bg-label-success',
    in_progress: 'bg-label-info',
    ringing: 'bg-label-info',
    queued: 'bg-label-secondary',
    pending: 'bg-label-primary',
    failed: 'bg-label-danger',
    cancelled: 'bg-label-secondary',
    error: 'bg-label-danger',
    timeout: 'bg-label-warning',
    rate_limited: 'bg-label-warning',
    no_answer: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');

const startVoiceCall = () => {
    startingCall.value = true;

    form.post(route('leads.voice-calls.store', props.leadId), {
        preserveScroll: true,
        onFinish: () => {
            startingCall.value = false;
        },
        onSuccess: () => {
            activeTab.value = 'voice_calls';
        },
    });
};

if (tabs.value.length && !tabs.value.find((tab) => tab.id === activeTab.value)) {
    activeTab.value = tabs.value[0].id;
}
</script>

<template>
    <div
        v-if="tabs.length"
        class="card animate-fade-slide-up"
        :style="sectionDelay"
    >
        <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h5 class="card-title mb-1">AI workforce</h5>
                <p class="card-subtitle text-muted mb-0 small">
                    AI activities, voice calls, timeline, and spend for this lead.
                </p>
            </div>

            <div v-if="workforce.can_start_voice_call" class="d-flex flex-wrap align-items-end gap-3">
                <div v-if="workforce.voice_employees.length > 1">
                    <label class="form-label small text-muted mb-1">AI employee</label>
                    <select
                        v-model="form.ai_employee_id"
                        class="form-select form-select-sm"
                    >
                        <option v-for="employee in workforce.voice_employees" :key="employee.id" :value="employee.id">
                            {{ employee.name }}
                        </option>
                    </select>
                </div>
                <PrimaryButton type="button" :disabled="startingCall || form.processing" @click="startVoiceCall">
                    Start AI Voice Call
                </PrimaryButton>
            </div>
        </div>

        <div class="card-body">
            <div v-if="flashMessage" class="alert alert-success mb-4">
                {{ flashMessage }}
            </div>

            <div v-if="errorMessage" class="alert alert-danger mb-4">
                {{ errorMessage }}
            </div>

            <div
                v-if="!workforce.can_start_voice_call && workforce.voice_call_blockers?.length"
                class="alert alert-warning mb-4"
            >
                <p class="fw-medium mb-2">Voice calling unavailable</p>
                <ul class="mb-0 ps-3">
                    <li v-for="blocker in workforce.voice_call_blockers" :key="blocker">{{ blocker }}</li>
                </ul>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li v-for="tab in tabs" :key="tab.id" class="nav-item" role="presentation">
                    <button
                        type="button"
                        class="nav-link"
                        :class="{ active: activeTab === tab.id }"
                        role="tab"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </li>
            </ul>

            <div v-if="activeTab === 'activities'">
                <div v-if="!workforce.ai_activities.length" class="text-muted small">No AI interactions logged for this lead yet.</div>
                <ul v-else class="list-group list-group-flush">
                    <li v-for="activity in workforce.ai_activities" :key="activity.id" class="list-group-item px-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <p class="fw-medium mb-0">{{ activity.employee?.name ?? 'Unknown agent' }}</p>
                                <p class="small text-muted mb-0 text-capitalize">{{ activity.request_type?.replace('_', ' ') }} · {{ activity.model?.name ?? '—' }}</p>
                            </div>
                            <span class="badge rounded-pill text-capitalize" :class="statusClass(activity.status)">
                                {{ activity.status?.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
                            <span>{{ formatDate(activity.created_at) }}</span>
                            <span>{{ activity.total_tokens ?? 0 }} tokens</span>
                            <span>{{ formatCost(activity.cost_usd) }}</span>
                        </div>
                        <p v-if="activity.error_message" class="small text-danger mb-0 mt-2">{{ activity.error_message }}</p>
                    </li>
                </ul>
            </div>

            <div v-else-if="activeTab === 'voice_calls'">
                <div v-if="!workforce.voice_calls.length" class="text-muted small">No voice calls for this lead yet.</div>
                <ul v-else class="list-group list-group-flush">
                    <li v-for="call in workforce.voice_calls" :key="call.id" class="list-group-item px-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <p class="fw-medium mb-0">{{ call.employee?.name ?? 'Unknown agent' }}</p>
                                <p class="small text-muted mb-0">{{ call.to_number ?? '—' }} · {{ call.provider?.name ?? '—' }}</p>
                            </div>
                            <span class="badge rounded-pill text-capitalize" :class="statusClass(call.status)">
                                {{ call.status?.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
                            <span>{{ formatDate(call.created_at) }}</span>
                            <span v-if="call.duration_seconds">{{ call.duration_seconds }}s</span>
                            <span>{{ formatCost(call.cost_usd) }}</span>
                        </div>
                        <p v-if="call.summary" class="small text-body-secondary mb-0 mt-2">{{ call.summary }}</p>
                        <p v-else-if="call.error_message" class="small text-danger mb-0 mt-2">{{ call.error_message }}</p>
                    </li>
                </ul>
            </div>

            <div v-else-if="activeTab === 'timeline'">
                <div v-if="!workforce.timeline.length" class="text-muted small">No workforce events yet.</div>
                <ul v-else class="list-group list-group-flush border-start border-2 ms-2 ps-3">
                    <li v-for="event in workforce.timeline" :key="event.id" class="list-group-item px-0 border-0 position-relative pb-4">
                        <span class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary" style="width: 0.625rem; height: 0.625rem; margin-left: -1.6rem;" />
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div>
                                <p class="fw-medium mb-0">{{ event.title }}</p>
                                <p class="small text-muted mb-0">{{ formatDate(event.occurred_at) }}</p>
                            </div>
                            <span class="badge rounded-pill text-capitalize" :class="statusClass(event.status)">
                                {{ event.status?.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                            <span class="text-capitalize">{{ event.type?.replace('_', ' ') }}</span>
                            <span>{{ formatCost(event.cost_usd) }}</span>
                        </div>
                        <p v-if="event.detail" class="small text-body-secondary mb-0 mt-2">{{ event.detail }}</p>
                    </li>
                </ul>
            </div>

            <div v-else-if="activeTab === 'costs' && workforce.costs">
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <p class="small text-uppercase text-muted mb-2">AI spend</p>
                                <p class="fs-4 fw-semibold mb-0">{{ formatCost(workforce.costs.ai_total_usd) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <p class="small text-uppercase text-muted mb-2">Voice spend</p>
                                <p class="fs-4 fw-semibold mb-0">{{ formatCost(workforce.costs.voice_total_usd) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-label-primary h-100">
                            <div class="card-body">
                                <p class="small text-uppercase mb-2">Combined</p>
                                <p class="fs-4 fw-semibold mb-0">{{ formatCost(workforce.costs.combined_total_usd) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div v-if="workforce.costs.ai_by_employee.length" class="col-lg-6">
                        <h6 class="fw-semibold mb-3">AI by employee</h6>
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="row in workforce.costs.ai_by_employee"
                                :key="row.employee_id"
                                class="list-group-item d-flex justify-content-between align-items-center px-0"
                            >
                                <span class="small text-body-secondary">{{ row.employee_name }} ({{ row.interactions }})</span>
                                <span class="fw-medium">{{ formatCost(row.total_cost_usd) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="workforce.costs.voice_by_provider.length" class="col-lg-6">
                        <h6 class="fw-semibold mb-3">Voice by provider</h6>
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="row in workforce.costs.voice_by_provider"
                                :key="row.provider_id"
                                class="list-group-item d-flex justify-content-between align-items-center px-0"
                            >
                                <span class="small text-body-secondary">{{ row.provider_name }} ({{ row.calls }})</span>
                                <span class="fw-medium">{{ formatCost(row.total_cost_usd) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
