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
        <div class="max-h-[85vh] overflow-y-auto p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="voice_provider_slug" value="Provider" />
                        <select id="voice_provider_slug" v-model="form.slug" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="mode === 'edit'" required>
                            <option value="">Select provider</option>
                            <option v-for="option in slugOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                    <div>
                        <InputLabel for="voice_provider_name" value="Display name" />
                        <TextInput id="voice_provider_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                </div>
                <div>
                    <InputLabel for="voice_provider_api_key" :value="mode === 'edit' ? 'API key (leave blank to keep)' : 'API key'" />
                    <TextInput id="voice_provider_api_key" v-model="form.api_key" type="password" class="mt-1 block w-full" :required="mode === 'create'" autocomplete="off" />
                    <InputError class="mt-2" :message="form.errors.api_key" />
                </div>
                <div>
                    <InputLabel for="voice_provider_webhook_secret" :value="mode === 'edit' ? 'Webhook secret (leave blank to keep)' : 'Webhook secret'" />
                    <TextInput id="voice_provider_webhook_secret" v-model="form.webhook_secret" type="password" class="mt-1 block w-full" autocomplete="off" />
                    <InputError class="mt-2" :message="form.errors.webhook_secret" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="voice_provider_priority" value="Priority" />
                        <TextInput id="voice_provider_priority" v-model="form.priority" type="number" min="1" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.priority" />
                    </div>
                    <div>
                        <InputLabel for="voice_provider_timeout" value="Timeout (s)" />
                        <TextInput id="voice_provider_timeout" v-model="form.timeout_seconds" type="number" min="5" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.timeout_seconds" />
                    </div>
                    <div>
                        <InputLabel for="voice_provider_status" value="Status" />
                        <select id="voice_provider_status" v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel for="voice_provider_metadata" value="Metadata (JSON)" />
                    <textarea id="voice_provider_metadata" v-model="metadataJson" rows="5" class="mt-1 block w-full rounded-md border-slate-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder='{"default_from_number":"+14155551234","agent_id":"..."}' />
                    <InputError class="mt-2" :message="form.errors.metadata" />
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
