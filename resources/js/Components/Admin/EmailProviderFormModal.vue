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
    timeout_seconds: 30,
    retry_count: 2,
    status: 'disabled',
    metadata: {
        default_from_email: '',
        default_from_name: '',
        reply_to: '',
    },
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit Email Provider' : 'Add Email Provider'));
const needsApiKey = computed(() => !['log', 'smtp'].includes(form.slug));

const resetForm = () => {
    if (props.mode === 'edit' && props.provider) {
        form.defaults({
            slug: props.provider.slug ?? '',
            name: props.provider.name ?? '',
            api_key: '',
            api_base_url: props.provider.api_base_url ?? '',
            priority: props.provider.priority ?? 10,
            timeout_seconds: props.provider.timeout_seconds ?? 30,
            retry_count: props.provider.retry_count ?? 2,
            status: props.provider.status ?? 'disabled',
            metadata: {
                default_from_email: props.provider.metadata?.default_from_email ?? props.provider.default_from_email ?? '',
                default_from_name: props.provider.metadata?.default_from_name ?? props.provider.default_from_name ?? '',
                reply_to: props.provider.metadata?.reply_to ?? props.provider.reply_to ?? '',
            },
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
        ? route('admin.email.providers.update', props.provider.id)
        : route('admin.email.providers.store');
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
        <div class="p-4 p-md-6" style="max-height: 85vh; overflow-y: auto;">
            <h5 class="mb-0">{{ title }}</h5>
            <form class="mt-4" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <InputLabel for="email_provider_slug" value="Provider" />
                        <select id="email_provider_slug" v-model="form.slug" class="form-select mt-1" :disabled="mode === 'edit'" required>
                            <option value="" disabled>Select provider</option>
                            <option v-for="opt in slugOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.slug" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="email_provider_name" value="Display name" />
                        <TextInput id="email_provider_name" v-model="form.name" class="mt-1" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div v-if="needsApiKey" class="col-12">
                        <InputLabel for="email_provider_api_key" :value="mode === 'edit' ? 'API key (leave blank to keep)' : 'API key'" />
                        <TextInput id="email_provider_api_key" v-model="form.api_key" type="password" class="mt-1" :required="mode === 'create'" />
                        <InputError class="mt-1" :message="form.errors.api_key" />
                    </div>

                    <div class="col-12">
                        <InputLabel for="email_provider_api_base_url" value="API base URL" />
                        <TextInput id="email_provider_api_base_url" v-model="form.api_base_url" class="mt-1" />
                        <InputError class="mt-1" :message="form.errors.api_base_url" />
                    </div>

                    <div class="col-sm-6">
                        <InputLabel for="from_email" value="Default from email" />
                        <TextInput id="from_email" v-model="form.metadata.default_from_email" type="email" class="mt-1" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="from_name" value="Default from name" />
                        <TextInput id="from_name" v-model="form.metadata.default_from_name" class="mt-1" />
                    </div>

                    <div class="col-sm-4">
                        <InputLabel for="priority" value="Priority" />
                        <TextInput id="priority" v-model="form.priority" type="number" min="1" class="mt-1" required />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="timeout" value="Timeout (s)" />
                        <TextInput id="timeout" v-model="form.timeout_seconds" type="number" min="5" class="mt-1" required />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="retries" value="Retries" />
                        <TextInput id="retries" v-model="form.retry_count" type="number" min="0" class="mt-1" required />
                    </div>

                    <div class="col-12">
                        <InputLabel for="status" value="Status" />
                        <select id="status" v-model="form.status" class="form-select mt-1" required>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-4 mt-2">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ mode === 'edit' ? 'Save changes' : 'Create provider' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
