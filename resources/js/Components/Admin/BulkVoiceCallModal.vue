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
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Bulk AI Voice Calls</h2>
            <p class="mt-1 text-sm text-slate-500">
                Queue outbound calls for selected leads. Each call runs asynchronously on the voice queue — Retell can handle multiple concurrent calls.
            </p>

            <div class="mt-4 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <div class="flex justify-between gap-3">
                    <span>Selected leads</span>
                    <span class="font-medium">{{ selectedLeads.length }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span>Callable (with phone)</span>
                    <span class="font-medium text-emerald-700">{{ callableLeads.length }}</span>
                </div>
                <div v-if="missingPhoneCount > 0" class="flex justify-between gap-3 text-amber-700">
                    <span>Skipped (no phone)</span>
                    <span class="font-medium">{{ missingPhoneCount }}</span>
                </div>
            </div>

            <div v-if="voiceCallOptions.voice_employees.length > 1" class="mt-4">
                <InputLabel for="bulk_ai_employee" value="AI employee" />
                <select
                    id="bulk_ai_employee"
                    v-model="form.ai_employee_id"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                >
                    <option v-for="employee in voiceCallOptions.voice_employees" :key="employee.id" :value="employee.id">
                        {{ employee.name }}
                    </option>
                </select>
            </div>

            <div v-if="form.errors.lead_ids" class="mt-4 text-sm text-red-600">{{ form.errors.lead_ids }}</div>
            <div v-if="form.errors.bulk_voice_call" class="mt-4 text-sm text-red-600">{{ form.errors.bulk_voice_call }}</div>

            <p v-if="callableLeads.length === 0" class="mt-4 text-sm text-amber-700">
                None of the selected leads have a phone number. Add phone numbers or select different leads.
            </p>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton type="button" :disabled="form.processing || callableLeads.length === 0" @click="submit">
                    Queue {{ callableLeads.length }} call{{ callableLeads.length === 1 ? '' : 's' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
