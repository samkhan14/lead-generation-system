<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stages: { type: Array, required: true },
    columns: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    lostReasons: { type: Array, default: () => [] },
});

const { can } = useAuth();

const stageForm = useForm({
    status: '',
    lost_reason: '',
});

const pendingLeadId = ref(null);

const moveLead = (leadId, status) => {
    if (status === 'lost') {
        pendingLeadId.value = leadId;
        stageForm.status = 'lost';
        stageForm.lost_reason = '';
        return;
    }

    router.put(route('leads.update', leadId), { status }, { preserveScroll: true });
};

const confirmLost = () => {
    if (! pendingLeadId.value || ! stageForm.lost_reason) {
        return;
    }

    router.put(route('leads.update', pendingLeadId.value), {
        status: 'lost',
        lost_reason: stageForm.lost_reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            pendingLeadId.value = null;
            stageForm.reset();
        },
    });
};

const assignedFilter = (value) => {
    router.get(route('leads.pipeline'), { assigned_to: value || undefined }, { preserveState: true });
};
</script>

<template>
    <Head title="Pipeline" />

    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <h4 class="mb-0 fw-bold">Lead pipeline</h4>
                <div class="d-flex gap-2">
                    <Link :href="route('leads.index')">
                        <SecondaryButton>List view</SecondaryButton>
                    </Link>
                    <select
                        class="form-select form-select-sm"
                        style="width: auto;"
                        :value="filters.assigned_to ?? ''"
                        @change="assignedFilter($event.target.value || null)"
                    >
                        <option value="">All leads</option>
                        <option value="me">Assigned to me</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
            </div>
        </template>

        <div v-if="pendingLeadId" class="alert alert-warning d-flex flex-wrap align-items-end gap-3 mb-4">
            <div>
                <strong>Mark as lost</strong>
                <select v-model="stageForm.lost_reason" class="form-select form-select-sm mt-2">
                    <option value="">Select reason</option>
                    <option v-for="reason in lostReasons" :key="reason.value" :value="reason.value">
                        {{ reason.label }}
                    </option>
                </select>
            </div>
            <PrimaryButton class="btn-sm" :disabled="!stageForm.lost_reason" @click="confirmLost">
                Confirm lost
            </PrimaryButton>
            <SecondaryButton class="btn-sm" @click="pendingLeadId = null">Cancel</SecondaryButton>
        </div>

        <div class="row g-3 flex-nowrap overflow-auto pb-3">
            <div v-for="stage in stages" :key="stage.value" class="col" style="min-width: 260px;">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small">{{ stage.label }}</span>
                            <span class="badge bg-label-secondary">{{ (columns[stage.value] ?? []).length }}</span>
                        </div>
                    </div>
                    <div class="card-body p-2 vstack gap-2" style="min-height: 200px;">
                        <div
                            v-for="lead in columns[stage.value] ?? []"
                            :key="lead.id"
                            class="card shadow-none border"
                        >
                            <div class="card-body p-3">
                                <Link :href="route('leads.show', lead.id)" class="fw-medium link-primary d-block mb-1">
                                    {{ lead.full_name }}
                                </Link>
                                <div class="small text-muted mb-2">{{ lead.email || lead.phone || 'No contact' }}</div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span v-if="lead.latest_score?.temperature" class="badge bg-label-primary text-capitalize">
                                        {{ lead.latest_score.temperature }}
                                    </span>
                                    <span v-if="lead.assigned_to" class="badge bg-label-secondary">{{ lead.assigned_to }}</span>
                                </div>
                                <div v-if="can('leads.update') && !stage.terminal" class="dropdown">
                                    <button
                                        class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                    >
                                        Move
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li v-for="target in stages.filter((s) => s.value !== stage.value && !s.terminal)" :key="target.value">
                                            <button
                                                type="button"
                                                class="dropdown-item"
                                                @click="moveLead(lead.id, target.value)"
                                            >
                                                {{ target.label }}
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" @click="moveLead(lead.id, 'lost')">
                                                Mark lost
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item text-success" @click="moveLead(lead.id, 'won')">
                                                Mark won
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
