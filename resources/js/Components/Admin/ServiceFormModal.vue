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
    service: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    complexityOptions: { type: Array, default: () => [] },
    serviceOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const emptyForm = () => ({
    name: '',
    slug: '',
    short_description: '',
    description: '',
    detailed_description: '',
    target_audience: [],
    ideal_customer_profile: '',
    problems_solved: [],
    features: [],
    benefits: [],
    deliverables: [],
    typical_timeline: '',
    complexity_level: '',
    pricing_notes: '',
    faqs: [{ question: '', answer: '' }],
    objections: [{ objection: '', response: '' }],
    discovery_questions: [],
    quotation_requirements: [],
    cross_sell_ids: [],
    upsell_ids: [],
    related_service_ids: [],
    tags: [],
    technologies: [],
    sort_order: 0,
    status: 'draft',
});

const form = useForm(emptyForm());

const title = computed(() => (props.mode === 'edit' ? 'Edit Service' : 'Create Service'));
const submitLabel = computed(() => (props.mode === 'edit' ? 'Save changes' : 'Create service'));

const linesToArray = (value) => {
    if (Array.isArray(value)) return value;
    return String(value || '').split('\n').map((line) => line.trim()).filter(Boolean);
};

const arrayToLines = (value) => (Array.isArray(value) ? value.join('\n') : '');

const bindLines = (field) => computed({
    get: () => arrayToLines(form[field]),
    set: (value) => { form[field] = linesToArray(value); },
});

const targetAudienceLines = bindLines('target_audience');
const problemsLines = bindLines('problems_solved');
const featureLines = bindLines('features');
const benefitLines = bindLines('benefits');
const deliverableLines = bindLines('deliverables');
const discoveryLines = bindLines('discovery_questions');
const quotationLines = bindLines('quotation_requirements');
const tagLines = bindLines('tags');
const technologyLines = bindLines('technologies');

const resetForm = () => {
    if (props.mode === 'edit' && props.service) {
        form.defaults({
            name: props.service.name ?? '',
            slug: props.service.slug ?? '',
            short_description: props.service.short_description ?? '',
            description: props.service.description ?? '',
            detailed_description: props.service.detailed_description ?? '',
            target_audience: props.service.target_audience ?? [],
            ideal_customer_profile: props.service.ideal_customer_profile ?? '',
            problems_solved: props.service.problems_solved ?? [],
            features: props.service.features ?? [],
            benefits: props.service.benefits ?? [],
            deliverables: props.service.deliverables ?? [],
            typical_timeline: props.service.typical_timeline ?? '',
            complexity_level: props.service.complexity_level ?? '',
            pricing_notes: props.service.pricing_notes ?? '',
            faqs: props.service.faqs?.length ? props.service.faqs : [{ question: '', answer: '' }],
            objections: props.service.objections?.length ? props.service.objections : [{ objection: '', response: '' }],
            discovery_questions: props.service.discovery_questions ?? [],
            quotation_requirements: props.service.quotation_requirements ?? [],
            cross_sell_ids: props.service.cross_sell_ids ?? [],
            upsell_ids: props.service.upsell_ids ?? [],
            related_service_ids: props.service.related_service_ids ?? [],
            tags: props.service.tags ?? [],
            technologies: props.service.technologies ?? [],
            sort_order: props.service.sort_order ?? 0,
            status: props.service.status ?? 'draft',
        });
        form.reset();
    } else {
        form.defaults(emptyForm());
        form.reset();
    }
};

watch(() => [props.show, props.mode, props.service], () => { if (props.show) resetForm(); }, { immediate: true });

const addFaq = () => form.faqs.push({ question: '', answer: '' });
const removeFaq = (index) => {
    if (form.faqs.length === 1) { form.faqs[0] = { question: '', answer: '' }; return; }
    form.faqs.splice(index, 1);
};
const addObjection = () => form.objections.push({ objection: '', response: '' });
const removeObjection = (index) => {
    if (form.objections.length === 1) { form.objections[0] = { objection: '', response: '' }; return; }
    form.objections.splice(index, 1);
};

const toggleRelation = (field, id) => {
    const current = [...form[field]];
    const pos = current.indexOf(id);
    if (pos >= 0) current.splice(pos, 1);
    else current.push(id);
    form[field] = current;
};

const isSelected = (field, id) => form[field].includes(id);

const submit = () => {
    const payload = {
        ...form.data(),
        faqs: form.faqs.filter((item) => item.question?.trim() && item.answer?.trim()),
        objections: form.objections.filter((item) => item.objection?.trim() && item.response?.trim()),
        complexity_level: form.complexity_level || null,
    };

    const options = { preserveScroll: true, onSuccess: () => emit('close') };

    if (props.mode === 'edit' && props.service) {
        form.transform(() => payload).put(route('admin.services.update', props.service.id), options);
    } else {
        form.transform(() => payload).post(route('admin.services.store'), options);
    }
};

const close = () => { form.clearErrors(); emit('close'); };
</script>

<template>
    <Modal :show="show" max-width="3xl" @close="close">
        <div class="max-h-[90vh] overflow-y-auto p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Business knowledge for AI voice, email, and proposal agents.
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Identity</h3>
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
                        <InputLabel for="service_short_description" value="Short description (cards, voice intro)" />
                        <textarea id="service_short_description" v-model="form.short_description" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.short_description" />
                    </div>
                    <div>
                        <InputLabel for="service_description" value="Summary description (legacy / search)" />
                        <textarea id="service_description" v-model="form.description" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div>
                        <InputLabel for="service_detailed_description" value="Detailed description (AI sales context)" />
                        <textarea id="service_detailed_description" v-model="form.detailed_description" rows="5" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.detailed_description" />
                    </div>
                </section>

                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Audience & problems</h3>
                    <div>
                        <InputLabel for="service_target_audience" value="Target audience (one per line)" />
                        <textarea id="service_target_audience" v-model="targetAudienceLines" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <InputLabel for="service_icp" value="Ideal customer profile" />
                        <textarea id="service_icp" v-model="form.ideal_customer_profile" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <InputLabel for="service_problems" value="Business problems solved (one per line)" />
                        <textarea id="service_problems" v-model="problemsLines" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </section>

                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Offering</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="service_features" value="Features (one per line)" />
                            <textarea id="service_features" v-model="featureLines" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <InputLabel for="service_benefits" value="Benefits (one per line)" />
                            <textarea id="service_benefits" v-model="benefitLines" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="service_deliverables" value="Deliverables (one per line)" />
                        <textarea id="service_deliverables" v-model="deliverableLines" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="service_timeline" value="Typical timeline" />
                            <TextInput id="service_timeline" v-model="form.typical_timeline" class="mt-1 block w-full" placeholder="e.g. 6–10 weeks" />
                        </div>
                        <div>
                            <InputLabel for="service_complexity" value="Complexity" />
                            <select id="service_complexity" v-model="form.complexity_level" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Not set</option>
                                <option v-for="option in complexityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="service_sort_order" value="Sort order" />
                            <TextInput id="service_sort_order" v-model.number="form.sort_order" type="number" min="0" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="service_pricing_notes" value="Pricing notes (no fixed prices)" />
                        <textarea id="service_pricing_notes" v-model="form.pricing_notes" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </section>

                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sales playbook</h3>
                    <div>
                        <InputLabel for="service_discovery" value="Discovery questions (one per line)" />
                        <textarea id="service_discovery" v-model="discoveryLines" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <InputLabel for="service_quotation" value="Required before quotation (one per line)" />
                        <textarea id="service_quotation" v-model="quotationLines" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <InputLabel value="FAQs" />
                            <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addFaq">+ Add FAQ</button>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(faq, index) in form.faqs" :key="`faq-${index}`" class="rounded-lg border border-slate-200 p-3">
                                <TextInput v-model="faq.question" class="block w-full" placeholder="Question" />
                                <textarea v-model="faq.answer" rows="2" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Answer" />
                                <button type="button" class="mt-2 text-xs text-slate-500 hover:text-red-600" @click="removeFaq(index)">Remove</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <InputLabel value="Objections & handling" />
                            <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" @click="addObjection">+ Add objection</button>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(item, index) in form.objections" :key="`objection-${index}`" class="rounded-lg border border-slate-200 p-3">
                                <TextInput v-model="item.objection" class="block w-full" placeholder="Objection" />
                                <textarea v-model="item.response" rows="2" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Approved response" />
                                <button type="button" class="mt-2 text-xs text-slate-500 hover:text-red-600" @click="removeObjection(index)">Remove</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Metadata & relations</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="service_technologies" value="Technologies (one per line)" />
                            <textarea id="service_technologies" v-model="technologyLines" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <InputLabel for="service_tags" value="Industry tags (one per line)" />
                            <textarea id="service_tags" v-model="tagLines" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div v-for="relation in [{ field: 'cross_sell_ids', label: 'Cross-sell' }, { field: 'upsell_ids', label: 'Upsell' }, { field: 'related_service_ids', label: 'Related' }]" :key="relation.field">
                            <InputLabel :value="relation.label" />
                            <div class="mt-2 max-h-36 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                                <label v-for="option in serviceOptions.filter((item) => item.id !== service?.id)" :key="`${relation.field}-${option.id}`" class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" :checked="isSelected(relation.field, option.id)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @change="toggleRelation(relation.field, option.id)" />
                                    <span>{{ option.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="service_status" value="Status" />
                        <select id="service_status" v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-48">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </section>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
