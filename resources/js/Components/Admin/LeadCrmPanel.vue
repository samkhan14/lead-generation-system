<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useAuth } from '@/composables/useAuth';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lead: { type: Object, required: true },
    crm: { type: Object, required: true },
});

const { can } = useAuth();

const stageLabel = computed(() =>
    props.crm.pipeline_stages.find((s) => s.value === props.lead.status)?.label ?? props.lead.status,
);

const activityForm = useForm({
    type: 'note',
    subject: '',
    body: '',
});

const dealForm = useForm({
    title: '',
    service_id: '',
    stage: 'qualified',
    value: '',
    expected_close_date: '',
    notes: '',
});

const taskForm = useForm({
    title: '',
    description: '',
    due_at: '',
    priority: 'medium',
});

const quoteForm = useForm({
    service_ids: [],
    notes: '',
});

const showLostReason = ref(false);
const statusForm = useForm({
    status: props.lead.status,
    lost_reason: '',
});

const submitActivity = () => {
    activityForm.post(route('leads.activities.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => activityForm.reset('subject', 'body'),
    });
};

const submitDeal = () => {
    dealForm.post(route('leads.deals.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => dealForm.reset(),
    });
};

const submitTask = () => {
    taskForm.post(route('leads.tasks.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const submitQuote = () => {
    quoteForm.post(route('leads.quotes.store', props.lead.id));
};

const updateStatus = () => {
    if (statusForm.status === 'lost' && !statusForm.lost_reason) {
        showLostReason.value = true;
        return;
    }

    statusForm.put(route('leads.update', props.lead.id), {
        preserveScroll: true,
    });
};

const completeTask = (taskId) => {
    router.post(route('tasks.complete', taskId), {}, { preserveScroll: true });
};

const toggleService = (serviceId) => {
    const ids = [...quoteForm.service_ids];
    const index = ids.indexOf(serviceId);

    if (index >= 0) {
        ids.splice(index, 1);
    } else {
        ids.push(serviceId);
    }

    quoteForm.service_ids = ids;
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : '—');
</script>

<template>
    <div class="card animate-fade-slide-up">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="card-title mb-1">CRM</h5>
                <p class="card-subtitle text-muted mb-0 small">
                    Pipeline, activities, deals, tasks, and quotes
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-label-primary">{{ stageLabel }}</span>
                <Link v-if="can('leads.update')" :href="route('leads.edit', lead.id)">
                    <SecondaryButton>Edit lead</SecondaryButton>
                </Link>
            </div>
        </div>

        <div class="card-body vstack gap-4">
            <div v-if="can('leads.update')" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <InputLabel value="Pipeline stage" />
                    <select v-model="statusForm.status" class="form-select mt-1" @change="showLostReason = statusForm.status === 'lost'">
                        <option v-for="stage in crm.pipeline_stages" :key="stage.value" :value="stage.value">
                            {{ stage.label }}
                        </option>
                    </select>
                </div>
                <div v-if="showLostReason || statusForm.status === 'lost'" class="col-md-4">
                    <InputLabel value="Lost reason" />
                    <select v-model="statusForm.lost_reason" class="form-select mt-1">
                        <option value="">Select reason</option>
                        <option v-for="reason in crm.lost_reasons" :key="reason.value" :value="reason.value">
                            {{ reason.label }}
                        </option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <PrimaryButton @click="updateStatus">Update stage</PrimaryButton>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <h6 class="fw-semibold mb-3">Activity timeline</h6>
                    <div v-if="crm.timeline.length === 0" class="text-muted small mb-3">No activities yet.</div>
                    <ul v-else class="list-group list-group-flush mb-3">
                        <li v-for="item in crm.timeline" :key="item.id" class="list-group-item px-0">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <span class="badge bg-label-secondary me-2">{{ item.type_label }}</span>
                                    <span class="fw-medium">{{ item.subject }}</span>
                                    <p v-if="item.body" class="small text-muted mb-0 mt-1">{{ item.body }}</p>
                                </div>
                                <span class="small text-muted text-nowrap">{{ formatDate(item.occurred_at) }}</span>
                            </div>
                        </li>
                    </ul>

                    <form v-if="can('leads.update')" class="border rounded p-3" @submit.prevent="submitActivity">
                        <h6 class="fw-semibold mb-3">Log activity</h6>
                        <div class="mb-3">
                            <InputLabel value="Type" />
                            <select v-model="activityForm.type" class="form-select mt-1">
                                <option v-for="type in crm.activity_types" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <InputLabel value="Subject" />
                            <TextInput v-model="activityForm.subject" class="mt-1" required />
                            <InputError :message="activityForm.errors.subject" class="mt-1" />
                        </div>
                        <div class="mb-3">
                            <InputLabel value="Details" />
                            <textarea v-model="activityForm.body" class="form-control mt-1" rows="3" />
                        </div>
                        <PrimaryButton :disabled="activityForm.processing">Log activity</PrimaryButton>
                    </form>
                </div>

                <div class="col-lg-6 vstack gap-4">
                    <div>
                        <h6 class="fw-semibold mb-3">Deals</h6>
                        <div v-if="crm.deals.length === 0" class="text-muted small mb-2">No deals yet.</div>
                        <ul v-else class="list-group mb-3">
                            <li v-for="deal in crm.deals" :key="deal.id" class="list-group-item">
                                <div class="fw-medium">{{ deal.title }}</div>
                                <div class="small text-muted">
                                    {{ deal.stage_label }}
                                    <span v-if="deal.value"> · {{ deal.currency }} {{ deal.value }}</span>
                                </div>
                            </li>
                        </ul>
                        <form v-if="can('crm.deals.manage')" class="border rounded p-3" @submit.prevent="submitDeal">
                            <div class="mb-2">
                                <TextInput v-model="dealForm.title" placeholder="Deal title" required />
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <select v-model="dealForm.service_id" class="form-select">
                                        <option value="">Service (optional)</option>
                                        <option v-for="service in crm.services" :key="service.id" :value="service.id">
                                            {{ service.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <TextInput v-model="dealForm.value" type="number" min="0" step="0.01" placeholder="Value" />
                                </div>
                            </div>
                            <PrimaryButton :disabled="dealForm.processing" class="btn-sm">Add deal</PrimaryButton>
                        </form>
                    </div>

                    <div>
                        <h6 class="fw-semibold mb-3">Tasks</h6>
                        <div v-if="crm.tasks.length === 0" class="text-muted small mb-2">No tasks yet.</div>
                        <ul v-else class="list-group mb-3">
                            <li v-for="task in crm.tasks" :key="task.id" class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium">{{ task.title }}</span>
                                    <span v-if="task.is_overdue" class="badge bg-label-danger ms-2">Overdue</span>
                                    <div class="small text-muted">{{ task.status }} · due {{ formatDate(task.due_at) }}</div>
                                </div>
                                <SecondaryButton
                                    v-if="can('crm.tasks.manage') && task.status !== 'completed'"
                                    class="btn-sm"
                                    @click="completeTask(task.id)"
                                >
                                    Done
                                </SecondaryButton>
                            </li>
                        </ul>
                        <form v-if="can('crm.tasks.manage')" class="border rounded p-3" @submit.prevent="submitTask">
                            <div class="mb-2">
                                <TextInput v-model="taskForm.title" placeholder="Task title" required />
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input v-model="taskForm.due_at" type="datetime-local" class="form-control" />
                                </div>
                                <div class="col-6">
                                    <select v-model="taskForm.priority" class="form-select">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                            <PrimaryButton :disabled="taskForm.processing" class="btn-sm">Add task</PrimaryButton>
                        </form>
                    </div>

                    <div>
                        <h6 class="fw-semibold mb-3">Quotes</h6>
                        <div v-if="crm.quotes.length === 0" class="text-muted small mb-2">No quotes yet.</div>
                        <ul v-else class="list-group mb-3">
                            <li v-for="quote in crm.quotes" :key="quote.id" class="list-group-item">
                                <Link :href="route('leads.quotes.show', [lead.id, quote.id])" class="fw-medium link-primary">
                                    {{ quote.title }}
                                </Link>
                                <div class="small text-muted">{{ quote.status_label }} · {{ formatDate(quote.created_at) }}</div>
                            </li>
                        </ul>
                        <form v-if="can('crm.quotes.manage')" class="border rounded p-3" @submit.prevent="submitQuote">
                            <InputLabel value="Services" class="mb-2" />
                            <div class="vstack gap-1 mb-3" style="max-height: 160px; overflow-y: auto;">
                                <label
                                    v-for="service in crm.services"
                                    :key="service.id"
                                    class="form-check"
                                >
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        :checked="quoteForm.service_ids.includes(service.id)"
                                        @change="toggleService(service.id)"
                                    >
                                    <span class="form-check-label small">{{ service.name }}</span>
                                </label>
                            </div>
                            <InputError :message="quoteForm.errors.service_ids" class="mb-2" />
                            <PrimaryButton :disabled="quoteForm.processing || quoteForm.service_ids.length === 0" class="btn-sm">
                                Generate quote
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
