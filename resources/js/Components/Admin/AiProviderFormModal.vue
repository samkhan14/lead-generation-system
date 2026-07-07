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
    provider: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    slug: '',
    name: '',
    api_key: '',
    api_base_url: '',
    priority: 10,
    rate_limit_rpm: '',
    timeout_seconds: 30,
    retry_count: 2,
    status: 'disabled',
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit Provider' : 'Add Provider'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create provider'));

const resetForm = () => {
    if (props.mode === 'edit' && props.provider) {
        form.defaults({
            slug: props.provider.slug ?? '',
            name: props.provider.name ?? '',
            api_key: '',
            api_base_url: props.provider.api_base_url ?? '',
            priority: props.provider.priority ?? 10,
            rate_limit_rpm: props.provider.rate_limit_rpm ?? '',
            timeout_seconds: props.provider.timeout_seconds ?? 30,
            retry_count: props.provider.retry_count ?? 2,
            status: props.provider.status ?? 'disabled',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.provider], () => {
    if (props.show) resetForm();
}, { immediate: true });

const submit = () => {
    const routeName = props.mode === 'edit'
        ? route('admin.ai.providers.update', props.provider.id)
        : route('admin.ai.providers.store');
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
            <div>
                <h5 class="modal-title">{{ title }}</h5>
                <p class="text-muted small mb-0">Configure LLM provider credentials and runtime settings.</p>
            </div>
            <button type="button" class="btn-close" aria-label="Close" @click="close" />
        </div>

        <form @submit.prevent="submit">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <InputLabel for="provider_slug" value="Provider" />
                        <select
                            id="provider_slug"
                            v-model="form.slug"
                            class="form-select"
                            :disabled="mode === 'edit'"
                            required
                        >
                            <option value="">Select provider</option>
                            <option v-for="option in slugOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="provider_name" value="Display name" />
                        <TextInput id="provider_name" v-model="form.name" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="col-12">
                        <InputLabel for="provider_api_key" :value="mode === 'edit' ? 'API key (leave blank to keep current)' : 'API key'" />
                        <TextInput id="provider_api_key" v-model="form.api_key" type="password" :required="mode === 'create'" autocomplete="off" />
                        <InputError class="mt-2" :message="form.errors.api_key" />
                    </div>

                    <div class="col-12">
                        <InputLabel for="provider_api_base_url" value="API base URL (optional)" />
                        <TextInput id="provider_api_base_url" v-model="form.api_base_url" placeholder="https://..." />
                        <InputError class="mt-2" :message="form.errors.api_base_url" />
                    </div>

                    <div class="col-12 col-md-6">
                        <InputLabel for="provider_priority" value="Priority" />
                        <TextInput id="provider_priority" v-model="form.priority" type="number" min="1" required />
                        <InputError class="mt-2" :message="form.errors.priority" />
                    </div>
                    <div class="col-12 col-md-6">
                        <InputLabel for="provider_status" value="Status" />
                        <select id="provider_status" v-model="form.status" class="form-select">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>

                    <div class="col-12 col-md-4">
                        <InputLabel for="provider_rpm" value="Rate limit (RPM)" />
                        <TextInput id="provider_rpm" v-model="form.rate_limit_rpm" type="number" min="1" />
                        <InputError class="mt-2" :message="form.errors.rate_limit_rpm" />
                    </div>
                    <div class="col-12 col-md-4">
                        <InputLabel for="provider_timeout" value="Timeout (s)" />
                        <TextInput id="provider_timeout" v-model="form.timeout_seconds" type="number" min="5" required />
                        <InputError class="mt-2" :message="form.errors.timeout_seconds" />
                    </div>
                    <div class="col-12 col-md-4">
                        <InputLabel for="provider_retry" value="Retries" />
                        <TextInput id="provider_retry" v-model="form.retry_count" type="number" min="0" required />
                        <InputError class="mt-2" :message="form.errors.retry_count" />
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
