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
    model: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    ai_provider_id: '',
    slug: '',
    name: '',
    max_tokens: '',
    input_price_per_1k: '',
    output_price_per_1k: '',
    status: 'active',
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit Model' : 'Add Model'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create model'));

const resetForm = () => {
    if (props.mode === 'edit' && props.model) {
        form.defaults({
            ai_provider_id: props.model.ai_provider_id ?? '',
            slug: props.model.slug ?? '',
            name: props.model.name ?? '',
            max_tokens: props.model.max_tokens ?? '',
            input_price_per_1k: props.model.input_price_per_1k ?? '',
            output_price_per_1k: props.model.output_price_per_1k ?? '',
            status: props.model.status ?? 'active',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.model], () => {
    if (props.show) resetForm();
}, { immediate: true });

const submit = () => {
    const routeName = props.mode === 'edit'
        ? route('admin.ai.models.update', props.model.id)
        : route('admin.ai.models.store');
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
    <Modal :show="show" max-width="lg" @close="close">
        <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" aria-label="Close" @click="close" />
        </div>

        <form @submit.prevent="submit">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <InputLabel for="model_provider" value="Provider" />
                        <select id="model_provider" v-model="form.ai_provider_id" class="form-select" required>
                            <option value="">Select provider</option>
                            <option v-for="option in providerOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.ai_provider_id" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="model_slug" value="Model slug" />
                        <TextInput id="model_slug" v-model="form.slug" required />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="model_name" value="Display name" />
                        <TextInput id="model_name" v-model="form.name" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="col-12 col-md-4">
                        <InputLabel for="model_max_tokens" value="Max tokens" />
                        <TextInput id="model_max_tokens" v-model="form.max_tokens" type="number" min="1" />
                        <InputError class="mt-2" :message="form.errors.max_tokens" />
                    </div>
                    <div class="col-12 col-md-4">
                        <InputLabel for="model_input_price" value="Input $/1K" />
                        <TextInput id="model_input_price" v-model="form.input_price_per_1k" type="number" step="0.000001" min="0" />
                        <InputError class="mt-2" :message="form.errors.input_price_per_1k" />
                    </div>
                    <div class="col-12 col-md-4">
                        <InputLabel for="model_output_price" value="Output $/1K" />
                        <TextInput id="model_output_price" v-model="form.output_price_per_1k" type="number" step="0.000001" min="0" />
                        <InputError class="mt-2" :message="form.errors.output_price_per_1k" />
                    </div>
                    <div class="col-12">
                        <InputLabel for="model_status" value="Status" />
                        <select id="model_status" v-model="form.status" class="form-select">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
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
