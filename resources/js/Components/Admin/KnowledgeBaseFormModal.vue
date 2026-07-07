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
    entry: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    categoryOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    name: '',
    slug: '',
    category: 'company',
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

const title = computed(() => (props.mode === 'edit' ? 'Edit Knowledge Entry' : 'Create Knowledge Entry'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create entry'));

const resetForm = () => {
    if (props.mode === 'edit' && props.entry) {
        form.defaults({
            name: props.entry.name ?? '',
            slug: props.entry.slug ?? '',
            category: props.entry.category ?? 'general',
            content: props.entry.content ?? '',
            tags: props.entry.tags ?? [],
            status: props.entry.status ?? 'draft',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.entry], () => {
    if (props.show) resetForm();
}, { immediate: true });

const submit = () => {
    const routeName = props.mode === 'edit'
        ? route('admin.ai.knowledge.update', props.entry.id)
        : route('admin.ai.knowledge.store');
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
        <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" aria-label="Close" @click="close" />
        </div>

        <form @submit.prevent="submit">
            <div class="modal-body overflow-auto" style="max-height: 70vh;">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <InputLabel for="kb_name" value="Name" />
                        <TextInput id="kb_name" v-model="form.name" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="kb_slug" value="Slug" />
                        <TextInput id="kb_slug" v-model="form.slug" placeholder="auto-generated if empty" />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="kb_category" value="Category" />
                        <select id="kb_category" v-model="form.category" class="form-select">
                            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.category" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="kb_status" value="Status" />
                        <select id="kb_status" v-model="form.status" class="form-select">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="kb_content" value="Content" />
                        <textarea id="kb_content" v-model="form.content" rows="8" class="form-control" required />
                        <InputError class="mt-2" :message="form.errors.content" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="kb_tags" value="Tags (one per line)" />
                        <textarea id="kb_tags" v-model="tagLines" rows="2" class="form-control" />
                        <InputError class="mt-2" :message="form.errors.tags" />
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
