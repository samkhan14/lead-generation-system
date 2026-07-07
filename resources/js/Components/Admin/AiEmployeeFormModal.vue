<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'create' },
    employee: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
    modelOptions: { type: Array, default: () => [] },
    knowledgeSourceOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    name: '',
    role: 'general',
    department: '',
    description: '',
    system_prompt: '',
    behavior_prompt: '',
    knowledge_sources: [],
    memory_enabled: true,
    context_window: 8192,
    temperature: 0.7,
    ai_provider_id: '',
    ai_model_id: '',
    fallback_provider_id: '',
    fallback_model_id: '',
    voice_id: '',
    language: 'en',
    status: 'training',
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit AI Employee' : 'Create AI Employee'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create employee'));

const filteredModels = (providerId) => {
    if (!providerId) return props.modelOptions;
    return props.modelOptions.filter((m) => m.ai_provider_id === Number(providerId));
};

const toggleSource = (value) => {
    const current = [...form.knowledge_sources];
    const pos = current.indexOf(value);
    if (pos >= 0) current.splice(pos, 1);
    else current.push(value);
    form.knowledge_sources = current;
};

const resetForm = () => {
    if (props.mode === 'edit' && props.employee) {
        form.defaults({
            name: props.employee.name ?? '',
            role: props.employee.role ?? 'general',
            department: props.employee.department ?? '',
            description: props.employee.description ?? '',
            system_prompt: props.employee.system_prompt ?? '',
            behavior_prompt: props.employee.behavior_prompt ?? '',
            knowledge_sources: props.employee.knowledge_sources ?? [],
            memory_enabled: props.employee.memory_enabled ?? true,
            context_window: props.employee.context_window ?? 8192,
            temperature: props.employee.temperature ?? 0.7,
            ai_provider_id: props.employee.ai_provider_id ?? '',
            ai_model_id: props.employee.ai_model_id ?? '',
            fallback_provider_id: props.employee.fallback_provider_id ?? '',
            fallback_model_id: props.employee.fallback_model_id ?? '',
            voice_id: props.employee.voice_id ?? '',
            language: props.employee.language ?? 'en',
            status: props.employee.status ?? 'training',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.employee], () => {
    if (props.show) resetForm();
}, { immediate: true });

const submit = () => {
    const routeName = props.mode === 'edit'
        ? route('admin.ai.employees.update', props.employee.id)
        : route('admin.ai.employees.store');
    const method = props.mode === 'edit' ? 'put' : 'post';

    form[method](routeName, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};

const close = () => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="p-4 p-md-6" style="max-height: 85vh; overflow-y: auto;">
            <h5 class="mb-0">{{ title }}</h5>
            <form class="mt-4" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <InputLabel for="employee_name" value="Name" />
                        <TextInput id="employee_name" v-model="form.name" class="mt-1" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="employee_role" value="Role" />
                        <select id="employee_role" v-model="form.role" class="form-select mt-1">
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="employee_department" value="Department" />
                        <TextInput id="employee_department" v-model="form.department" class="mt-1" />
                        <InputError class="mt-2" :message="form.errors.department" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="employee_status" value="Status" />
                        <select id="employee_status" v-model="form.status" class="form-select mt-1">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="employee_description" value="Description" />
                        <textarea id="employee_description" v-model="form.description" rows="2" class="form-control mt-1" />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="employee_system_prompt" value="System prompt" />
                        <textarea id="employee_system_prompt" v-model="form.system_prompt" rows="3" class="form-control mt-1" />
                        <InputError class="mt-2" :message="form.errors.system_prompt" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="employee_behavior_prompt" value="Behavior prompt" />
                        <textarea id="employee_behavior_prompt" v-model="form.behavior_prompt" rows="3" class="form-control mt-1" />
                        <InputError class="mt-2" :message="form.errors.behavior_prompt" />
                    </div>
                    <div class="col-12">
                        <InputLabel value="Knowledge sources" />
                        <div class="d-flex flex-wrap gap-3 mt-2">
                            <div v-for="option in knowledgeSourceOptions" :key="option.value" class="form-check">
                                <input
                                    :id="`knowledge_${option.value}`"
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="form.knowledge_sources.includes(option.value)"
                                    @change="toggleSource(option.value)"
                                />
                                <label class="form-check-label small" :for="`knowledge_${option.value}`">{{ option.label }}</label>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.knowledge_sources" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="employee_provider" value="Primary provider" />
                        <select id="employee_provider" v-model="form.ai_provider_id" class="form-select mt-1">
                            <option value="">None</option>
                            <option v-for="option in providerOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.ai_provider_id" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="employee_model" value="Primary model" />
                        <select id="employee_model" v-model="form.ai_model_id" class="form-select mt-1">
                            <option value="">None</option>
                            <option v-for="option in filteredModels(form.ai_provider_id)" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.ai_model_id" />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="employee_context" value="Context window" />
                        <TextInput id="employee_context" v-model="form.context_window" type="number" min="1024" class="mt-1" required />
                        <InputError class="mt-2" :message="form.errors.context_window" />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="employee_temperature" value="Temperature" />
                        <TextInput id="employee_temperature" v-model="form.temperature" type="number" step="0.1" min="0" max="2" class="mt-1" required />
                        <InputError class="mt-2" :message="form.errors.temperature" />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="employee_language" value="Language" />
                        <TextInput id="employee_language" v-model="form.language" class="mt-1" required />
                        <InputError class="mt-2" :message="form.errors.language" />
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input id="employee_memory" v-model="form.memory_enabled" type="checkbox" class="form-check-input" />
                            <label class="form-check-label small" for="employee_memory">Enable conversation memory</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 border-top pt-4 mt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
