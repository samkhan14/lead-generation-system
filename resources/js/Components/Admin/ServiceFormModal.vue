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
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: 'create',
    },
    service: {
        type: Object,
        default: null,
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    serviceOptions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    name: '',
    slug: '',
    description: '',
    features: [],
    benefits: [],
    deliverables: [],
    pricing_notes: '',
    faqs: [{ question: '', answer: '' }],
    objections: [{ objection: '', response: '' }],
    cross_sell_ids: [],
    upsell_ids: [],
    tags: [],
    status: 'draft',
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit Service' : 'Create Service'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create service'));

const linesToArray = (value) => {
    if (Array.isArray(value)) {
        return value;
    }

    return String(value || '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
};

const arrayToLines = (value) => (Array.isArray(value) ? value.join('\n') : '');

const featureLines = computed({
    get: () => arrayToLines(form.features),
    set: (value) => {
        form.features = linesToArray(value);
    },
});

const benefitLines = computed({
    get: () => arrayToLines(form.benefits),
    set: (value) => {
        form.benefits = linesToArray(value);
    },
});

const deliverableLines = computed({
    get: () => arrayToLines(form.deliverables),
    set: (value) => {
        form.deliverables = linesToArray(value);
    },
});

const tagLines = computed({
    get: () => arrayToLines(form.tags),
    set: (value) => {
        form.tags = linesToArray(value);
    },
});

const resetForm = () => {
    if (props.mode === 'edit' && props.service) {
        form.defaults({
            name: props.service.name ?? '',
            slug: props.service.slug ?? '',
            description: props.service.description ?? '',
            features: props.service.features ?? [],
            benefits: props.service.benefits ?? [],
            deliverables: props.service.deliverables ?? [],
            pricing_notes: props.service.pricing_notes ?? '',
            faqs: props.service.faqs?.length ? props.service.faqs : [{ question: '', answer: '' }],
            objections: props.service.objections?.length ? props.service.objections : [{ objection: '', response: '' }],
            cross_sell_ids: props.service.cross_sell_ids ?? [],
            upsell_ids: props.service.upsell_ids ?? [],
            tags: props.service.tags ?? [],
            status: props.service.status ?? 'draft',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(
    () => [props.show, props.mode, props.service],
    () => {
        if (props.show) {
            resetForm();
        }
    },
    { immediate: true },
);

const addFaq = () => {
    form.faqs.push({ question: '', answer: '' });
};

const removeFaq = (index) => {
    if (form.faqs.length === 1) {
        form.faqs[0] = { question: '', answer: '' };

        return;
    }

    form.faqs.splice(index, 1);
};

const addObjection = () => {
    form.objections.push({ objection: '', response: '' });
};

const removeObjection = (index) => {
    if (form.objections.length === 1) {
        form.objections[0] = { objection: '', response: '' };

        return;
    }

    form.objections.splice(index, 1);
};

const toggleRelation = (field, id) => {
    const current = [...form[field]];
    const pos = current.indexOf(id);

    if (pos >= 0) {
        current.splice(pos, 1);
    } else {
        current.push(id);
    }

    form[field] = current;
};

const isSelected = (field, id) => form[field].includes(id);

const submit = () => {
    const payload = {
        ...form.data(),
        faqs: form.faqs.filter((item) => item.question?.trim() && item.answer?.trim()),
        objections: form.objections.filter((item) => item.objection?.trim() && item.response?.trim()),
    };

    if (props.mode === 'edit' && props.service) {
        form.transform(() => payload).put(route('admin.services.update', props.service.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.transform(() => payload).post(route('admin.services.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
};

const close = () => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="max-h-[85vh] overflow-y-auto p-6">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Define what the company sells so AI employees can load this knowledge dynamically.
                    </p>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="service_name" value="Service name" />
                        <TextInput id="service_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="service_slug" value="Slug" />
                        <TextInput id="service_slug" v-model="form.slug" class="mt-1 block w-full" placeholder="auto-generated if empty" />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                </div>

                <div>
                    <InputLabel for="service_description" value="Description" />
                    <textarea
                        id="service_description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="service_features" value="Features (one per line)" />
                        <textarea
                            id="service_features"
                            v-model="featureLines"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="form.errors.features" />
                    </div>
                    <div>
                        <InputLabel for="service_benefits" value="Benefits (one per line)" />
                        <textarea
                            id="service_benefits"
                            v-model="benefitLines"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="form.errors.benefits" />
                    </div>
                </div>

                <div>
                    <InputLabel for="service_deliverables" value="Deliverables (one per line)" />
                    <textarea
                        id="service_deliverables"
                        v-model="deliverableLines"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.deliverables" />
                </div>

                <div>
                    <InputLabel for="service_pricing_notes" value="Pricing notes" />
                    <textarea
                        id="service_pricing_notes"
                        v-model="form.pricing_notes"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.pricing_notes" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <InputLabel value="FAQs" />
                        <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addFaq">
                            + Add FAQ
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="(faq, index) in form.faqs"
                            :key="`faq-${index}`"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <TextInput v-model="faq.question" class="block w-full" placeholder="Question" />
                            <textarea
                                v-model="faq.answer"
                                rows="2"
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Answer"
                            />
                            <button
                                type="button"
                                class="mt-2 text-xs text-slate-500 hover:text-red-600"
                                @click="removeFaq(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.faqs" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <InputLabel value="Objections" />
                        <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addObjection">
                            + Add objection
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in form.objections"
                            :key="`objection-${index}`"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <TextInput v-model="item.objection" class="block w-full" placeholder="Objection" />
                            <textarea
                                v-model="item.response"
                                rows="2"
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Response"
                            />
                            <button
                                type="button"
                                class="mt-2 text-xs text-slate-500 hover:text-red-600"
                                @click="removeObjection(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.objections" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Cross-sell services" />
                        <div class="mt-2 max-h-36 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                            <label
                                v-for="option in serviceOptions.filter((item) => item.id !== service?.id)"
                                :key="`cross-${option.id}`"
                                class="flex items-center gap-2 text-sm text-slate-700"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected('cross_sell_ids', option.id)"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    @change="toggleRelation('cross_sell_ids', option.id)"
                                />
                                <span>{{ option.name }}</span>
                            </label>
                            <p v-if="!serviceOptions.length" class="text-sm text-slate-400">No other services yet.</p>
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Upsell services" />
                        <div class="mt-2 max-h-36 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                            <label
                                v-for="option in serviceOptions.filter((item) => item.id !== service?.id)"
                                :key="`upsell-${option.id}`"
                                class="flex items-center gap-2 text-sm text-slate-700"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected('upsell_ids', option.id)"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    @change="toggleRelation('upsell_ids', option.id)"
                                />
                                <span>{{ option.name }}</span>
                            </label>
                            <p v-if="!serviceOptions.length" class="text-sm text-slate-400">No other services yet.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="service_tags" value="Tags (one per line)" />
                        <textarea
                            id="service_tags"
                            v-model="tagLines"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="form.errors.tags" />
                    </div>
                    <div>
                        <InputLabel for="service_status" value="Status" />
                        <select
                            id="service_status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        {{ submitLabel }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
