<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    selectedLeads: {
        type: Array,
        default: () => [],
    },
    voiceCallOptions: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const callableLeads = computed(() => props.selectedLeads.filter((lead) => Boolean(lead.phone)));
const missingPhoneCount = computed(() => props.selectedLeads.length - callableLeads.value.length);

const form = useForm({
    lead_ids: [],
    ai_employee_id: props.voiceCallOptions.default_employee_id ?? '',
});

const resetForm = () => {
    form.lead_ids = callableLeads.value.map((lead) => lead.id);
    form.ai_employee_id = props.voiceCallOptions.default_employee_id ?? '';
    form.clearErrors();
};

const submit = () => {
    resetForm();

    if (form.lead_ids.length === 0) {
        return;
    }

    form.post(route('leads.voice-calls.bulk'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};

const close = () => {
    form.clearErrors();
    emit('close');
};

watch(() => props.show, (visible) => {
    if (visible) {
        resetForm();
    }
});
</script>

<template>
    <Modal :show="show" max-width="lg" @close="close">
        <div class="modal-header">
            <h5 class="modal-title">Bulk AI Voice Calls</h5>
            <button type="button" class="btn-close" aria-label="Close" @click="close" />
        </div>

        <div class="modal-body">
            <p class="text-muted mb-4">
                Queue outbound calls for selected leads. Each call runs asynchronously on the voice queue.
            </p>

            <div class="card bg-label-secondary mb-4">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between gap-3 small mb-2">
                        <span>Selected leads</span>
                        <span class="fw-medium">{{ selectedLeads.length }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 small mb-2">
                        <span>Callable (with phone)</span>
                        <span class="fw-medium text-success">{{ callableLeads.length }}</span>
                    </div>
                    <div v-if="missingPhoneCount > 0" class="d-flex justify-content-between gap-3 small text-warning">
                        <span>Skipped (no phone)</span>
                        <span class="fw-medium">{{ missingPhoneCount }}</span>
                    </div>
                </div>
            </div>

            <div v-if="voiceCallOptions.voice_employees.length > 1" class="mb-4">
                <InputLabel for="bulk_ai_employee" value="AI employee" />
                <select
                    id="bulk_ai_employee"
                    v-model="form.ai_employee_id"
                    class="form-select mt-1"
                >
                    <option v-for="employee in voiceCallOptions.voice_employees" :key="employee.id" :value="employee.id">
                        {{ employee.name }}
                    </option>
                </select>
            </div>

            <div v-if="form.errors.lead_ids" class="text-danger small mb-2">{{ form.errors.lead_ids }}</div>
            <div v-if="form.errors.bulk_voice_call" class="text-danger small mb-2">{{ form.errors.bulk_voice_call }}</div>

            <div v-if="callableLeads.length === 0" class="alert alert-warning mb-0">
                None of the selected leads have a phone number.
            </div>
        </div>

        <div class="modal-footer">
            <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
            <PrimaryButton type="button" :disabled="form.processing || callableLeads.length === 0" @click="submit">
                Queue {{ callableLeads.length }} call{{ callableLeads.length === 1 ? '' : 's' }}
            </PrimaryButton>
        </div>
    </Modal>
</template>
