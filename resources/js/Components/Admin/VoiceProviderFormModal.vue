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
    webhook_secret: '',
    api_base_url: '',
    priority: 10,
    timeout_seconds: 30,
    retry_count: 2,
    status: 'disabled',
    metadata: {},
});

const form = useForm(emptyForm());

const metadataJson = computed({
    get: () => JSON.stringify(form.metadata ?? {}, null, 2),
    set: (value) => {
        try {
            form.metadata = JSON.parse(value || '{}');
        } catch {
            form.metadata = {};
        }
    },
});

const title = computed(() => (props.mode === 'edit' ? 'Edit Voice Provider' : 'Add Voice Provider'));

const resetForm = () => {
    if (props.mode === 'edit' && props.provider) {
        form.defaults({
            slug: props.provider.slug ?? '',
            name: props.provider.name ?? '',
            api_key: '',
            webhook_secret: '',
            api_base_url: props.provider.api_base_url ?? '',
            priority: props.provider.priority ?? 10,
            timeout_seconds: props.provider.timeout_seconds ?? 30,
            retry_count: props.provider.retry_count ?? 2,
            status: props.provider.status ?? 'disabled',
            metadata: props.provider.metadata ?? {},
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
        ? route('admin.voice.providers.update', props.provider.id)
        : route('admin.voice.providers.store');
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
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <InputLabel for="voice_provider_slug" value="Provider" />
                        <select id="voice_provider_slug" v-model="form.slug" class="form-select mt-1" :disabled="mode === 'edit'" required>
                            <option value="">Select provider</option>
                            <option v-for="option in slugOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.slug" />
                    </div>
                    <div class="col-sm-6">
                        <InputLabel for="voice_provider_name" value="Display name" />
                        <TextInput id="voice_provider_name" v-model="form.name" class="mt-1" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                </div>

                <div class="mt-3">
                    <InputLabel for="voice_provider_api_key" :value="mode === 'edit' ? 'API key (leave blank to keep)' : 'API key'" />
                    <TextInput id="voice_provider_api_key" v-model="form.api_key" type="password" class="mt-1" :required="mode === 'create'" autocomplete="off" />
                    <InputError class="mt-1" :message="form.errors.api_key" />
                </div>

                <div class="mt-3">
                    <InputLabel for="voice_provider_webhook_secret" :value="mode === 'edit' ? 'Webhook secret (leave blank to keep)' : 'Webhook secret'" />
                    <TextInput id="voice_provider_webhook_secret" v-model="form.webhook_secret" type="password" class="mt-1" autocomplete="off" />
                    <InputError class="mt-1" :message="form.errors.webhook_secret" />
                </div>

                <div class="row g-3 mt-0">
                    <div class="col-sm-4">
                        <InputLabel for="voice_provider_priority" value="Priority" />
                        <TextInput id="voice_provider_priority" v-model="form.priority" type="number" min="1" class="mt-1" required />
                        <InputError class="mt-1" :message="form.errors.priority" />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="voice_provider_timeout" value="Timeout (s)" />
                        <TextInput id="voice_provider_timeout" v-model="form.timeout_seconds" type="number" min="5" class="mt-1" required />
                        <InputError class="mt-1" :message="form.errors.timeout_seconds" />
                    </div>
                    <div class="col-sm-4">
                        <InputLabel for="voice_provider_status" value="Status" />
                        <select id="voice_provider_status" v-model="form.status" class="form-select mt-1">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <InputLabel for="voice_provider_metadata" value="Metadata (JSON)" />
                    <textarea id="voice_provider_metadata" v-model="metadataJson" rows="5" class="form-control font-monospace small mt-1" placeholder='{"default_from_number":"+14155551234","agent_id":"..."}' />
                    <InputError class="mt-1" :message="form.errors.metadata" />
                </div>
            </div>

            <div class="modal-footer">
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
