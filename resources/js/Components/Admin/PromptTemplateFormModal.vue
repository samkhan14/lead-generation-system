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
    template: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    name: '',
    slug: '',
    category: 'system',
    content: '',
    tags: [],
    status: 'draft',
});

const form = useForm(emptyForm());

const tagLines = computed({
    get: () => (Array.isArray(form.tags) ? form.tags.join('\n') : ''),
    set: (value) => {
        form.tags = String(value || '').split('\n').map((line) => line.trim()).filter(Boolean);
    },
});

const title = computed(() => (props.mode === 'edit' ? 'Edit Prompt' : 'Create Prompt'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create prompt'));

const resetForm = () => {
    if (props.mode === 'edit' && props.template) {
        form.defaults({
            name: props.template.name ?? '',
            slug: props.template.slug ?? '',
            category: props.template.category ?? 'system',
            content: props.template.content ?? '',
            tags: props.template.tags ?? [],
            status: props.template.status ?? 'draft',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.template], () => {
    if (props.show) resetForm();
}, { immediate: true });

const submit = () => {
    const routeName = props.mode === 'edit'
        ? route('admin.ai.prompts.update', props.template.id)
        : route('admin.ai.prompts.store');
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
                        <InputLabel for="prompt_name" value="Name" />
                        <TextInput id="prompt_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="prompt_slug" value="Slug" />
                        <TextInput id="prompt_slug" v-model="form.slug" class="mt-1 block w-full" placeholder="auto-generated if empty" />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="prompt_category" value="Category" />
                        <select id="prompt_category" v-model="form.category" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.category" />
                    </div>
                    <div>
                        <InputLabel for="prompt_status" value="Status" />
                        <select id="prompt_status" v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                </div>
                <div>
                    <InputLabel for="prompt_content" value="Content" />
                    <textarea id="prompt_content" v-model="form.content" rows="8" class="mt-1 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                    <InputError class="mt-2" :message="form.errors.content" />
                </div>
                <div>
                    <InputLabel for="prompt_tags" value="Tags (one per line)" />
                    <textarea id="prompt_tags" v-model="tagLines" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError class="mt-2" :message="form.errors.tags" />
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
