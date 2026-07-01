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
    completed: 'bg-emerald-100 text-emerald-800',
    success: 'bg-emerald-100 text-emerald-800',
    in_progress: 'bg-blue-100 text-blue-800',
    ringing: 'bg-blue-100 text-blue-800',
    queued: 'bg-slate-100 text-slate-700',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-slate-100 text-slate-600',
    error: 'bg-red-100 text-red-800',
    timeout: 'bg-amber-100 text-amber-800',
    rate_limited: 'bg-amber-100 text-amber-800',
    no_answer: 'bg-amber-100 text-amber-800',
}[status] ?? 'bg-slate-100 text-slate-600');

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
        class="animate-fade-slide-up rounded-xl bg-white p-6 shadow-sm ring-1 ring-violet-200 transition-shadow hover:shadow-md"
        :style="sectionDelay"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">AI workforce</h3>
                <p class="mt-1 text-sm text-slate-500">
                    AI activities, voice calls, timeline, and spend for this lead.
                </p>
            </div>

            <div v-if="workforce.can_start_voice_call" class="flex flex-wrap items-end gap-3">
                <div v-if="workforce.voice_employees.length > 1">
                    <label class="text-xs font-medium text-slate-500">AI employee</label>
                    <select
                        v-model="form.ai_employee_id"
                        class="mt-1 block rounded-md border-slate-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
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

        <div v-if="flashMessage" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ flashMessage }}
        </div>

        <div v-if="errorMessage" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ errorMessage }}
        </div>

        <div
            v-if="!workforce.can_start_voice_call && workforce.voice_call_blockers?.length"
            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
            <p class="font-medium">Voice calling unavailable</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li v-for="blocker in workforce.voice_call_blockers" :key="blocker">{{ blocker }}</li>
            </ul>
        </div>

        <div class="mt-6 border-b border-slate-200">
            <nav class="-mb-px flex flex-wrap gap-4">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                    :class="activeTab === tab.id
                        ? 'border-violet-600 text-violet-700'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <div v-if="activeTab === 'activities'" class="mt-6">
            <div v-if="!workforce.ai_activities.length" class="text-sm text-slate-500">No AI interactions logged for this lead yet.</div>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="activity in workforce.ai_activities" :key="activity.id" class="py-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-medium text-slate-900">{{ activity.employee?.name ?? 'Unknown agent' }}</p>
                            <p class="text-sm text-slate-500 capitalize">{{ activity.request_type?.replace('_', ' ') }} · {{ activity.model?.name ?? '—' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(activity.status)">
                            {{ activity.status?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span>{{ formatDate(activity.created_at) }}</span>
                        <span>{{ activity.total_tokens ?? 0 }} tokens</span>
                        <span>{{ formatCost(activity.cost_usd) }}</span>
                    </div>
                    <p v-if="activity.error_message" class="mt-2 text-sm text-red-600">{{ activity.error_message }}</p>
                </li>
            </ul>
        </div>

        <div v-else-if="activeTab === 'voice_calls'" class="mt-6">
            <div v-if="!workforce.voice_calls.length" class="text-sm text-slate-500">No voice calls for this lead yet.</div>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="call in workforce.voice_calls" :key="call.id" class="py-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-medium text-slate-900">{{ call.employee?.name ?? 'Unknown agent' }}</p>
                            <p class="text-sm text-slate-500">{{ call.to_number ?? '—' }} · {{ call.provider?.name ?? '—' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(call.status)">
                            {{ call.status?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span>{{ formatDate(call.created_at) }}</span>
                        <span v-if="call.duration_seconds">{{ call.duration_seconds }}s</span>
                        <span>{{ formatCost(call.cost_usd) }}</span>
                    </div>
                    <p v-if="call.summary" class="mt-2 text-sm text-slate-600">{{ call.summary }}</p>
                    <p v-else-if="call.error_message" class="mt-2 text-sm text-red-600">{{ call.error_message }}</p>
                </li>
            </ul>
        </div>

        <div v-else-if="activeTab === 'timeline'" class="mt-6">
            <div v-if="!workforce.timeline.length" class="text-sm text-slate-500">No workforce events yet.</div>
            <ol v-else class="relative space-y-4 border-l border-slate-200 pl-6">
                <li v-for="event in workforce.timeline" :key="event.id" class="relative">
                    <span class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full bg-violet-500 ring-4 ring-white" />
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-medium text-slate-900">{{ event.title }}</p>
                            <p class="text-sm text-slate-500">{{ formatDate(event.occurred_at) }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(event.status)">
                            {{ event.status?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-3 text-xs text-slate-500">
                        <span class="capitalize">{{ event.type?.replace('_', ' ') }}</span>
                        <span>{{ formatCost(event.cost_usd) }}</span>
                    </div>
                    <p v-if="event.detail" class="mt-2 text-sm text-slate-600">{{ event.detail }}</p>
                </li>
            </ol>
        </div>

        <div v-else-if="activeTab === 'costs' && workforce.costs" class="mt-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">AI spend</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCost(workforce.costs.ai_total_usd) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Voice spend</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCost(workforce.costs.voice_total_usd) }}</p>
                </div>
                <div class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-violet-700">Combined</p>
                    <p class="mt-2 text-2xl font-semibold text-violet-900">{{ formatCost(workforce.costs.combined_total_usd) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div v-if="workforce.costs.ai_by_employee.length">
                    <h4 class="text-sm font-semibold text-slate-900">AI by employee</h4>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li v-for="row in workforce.costs.ai_by_employee" :key="row.employee_id" class="flex justify-between gap-3">
                            <span>{{ row.employee_name }} ({{ row.interactions }})</span>
                            <span class="font-medium text-slate-900">{{ formatCost(row.total_cost_usd) }}</span>
                        </li>
                    </ul>
                </div>
                <div v-if="workforce.costs.voice_by_provider.length">
                    <h4 class="text-sm font-semibold text-slate-900">Voice by provider</h4>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li v-for="row in workforce.costs.voice_by_provider" :key="row.provider_id" class="flex justify-between gap-3">
                            <span>{{ row.provider_name }} ({{ row.calls }})</span>
                            <span class="font-medium text-slate-900">{{ formatCost(row.total_cost_usd) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
