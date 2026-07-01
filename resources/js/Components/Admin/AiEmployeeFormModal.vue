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
        <div class="max-h-[85vh] overflow-y-auto p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="employee_name" value="Name" />
                        <TextInput id="employee_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="employee_role" value="Role" />
                        <select id="employee_role" v-model="form.role" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="employee_department" value="Department" />
                        <TextInput id="employee_department" v-model="form.department" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.department" />
                    </div>
                    <div>
                        <InputLabel for="employee_status" value="Status" />
                        <select id="employee_status" v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                </div>
                <div>
                    <InputLabel for="employee_description" value="Description" />
                    <textarea id="employee_description" v-model="form.description" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>
                <div>
                    <InputLabel for="employee_system_prompt" value="System prompt" />
                    <textarea id="employee_system_prompt" v-model="form.system_prompt" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError class="mt-2" :message="form.errors.system_prompt" />
                </div>
                <div>
                    <InputLabel for="employee_behavior_prompt" value="Behavior prompt" />
                    <textarea id="employee_behavior_prompt" v-model="form.behavior_prompt" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError class="mt-2" :message="form.errors.behavior_prompt" />
                </div>
                <div>
                    <InputLabel value="Knowledge sources" />
                    <div class="mt-2 flex flex-wrap gap-3">
                        <label v-for="option in knowledgeSourceOptions" :key="option.value" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" :checked="form.knowledge_sources.includes(option.value)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @change="toggleSource(option.value)" />
                            {{ option.label }}
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.knowledge_sources" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="employee_provider" value="Primary provider" />
                        <select id="employee_provider" v-model="form.ai_provider_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">None</option>
                            <option v-for="option in providerOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.ai_provider_id" />
                    </div>
                    <div>
                        <InputLabel for="employee_model" value="Primary model" />
                        <select id="employee_model" v-model="form.ai_model_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">None</option>
                            <option v-for="option in filteredModels(form.ai_provider_id)" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.ai_model_id" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="employee_context" value="Context window" />
                        <TextInput id="employee_context" v-model="form.context_window" type="number" min="1024" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.context_window" />
                    </div>
                    <div>
                        <InputLabel for="employee_temperature" value="Temperature" />
                        <TextInput id="employee_temperature" v-model="form.temperature" type="number" step="0.1" min="0" max="2" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.temperature" />
                    </div>
                    <div>
                        <InputLabel for="employee_language" value="Language" />
                        <TextInput id="employee_language" v-model="form.language" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.language" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.memory_enabled" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    Enable conversation memory
                </label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
