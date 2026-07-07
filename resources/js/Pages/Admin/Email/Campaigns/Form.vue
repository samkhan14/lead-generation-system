<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailHtmlEditor from '@/Components/Admin/EmailHtmlEditor.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    campaign: { type: Object, default: null },
    providers: { type: Object, default: () => ({ data: [] }) },
});

const page = usePage();
const isEdit = !!props.campaign;

const form = useForm({
    name: props.campaign?.name ?? '',
    subject: props.campaign?.subject ?? '',
    html_body: props.campaign?.html_body ?? '<p>Hello,</p><p>Your message here.</p>',
    text_body: props.campaign?.text_body ?? '',
    from_name: props.campaign?.from_name ?? '',
    from_email: props.campaign?.from_email ?? '',
    reply_to: props.campaign?.reply_to ?? '',
    email_provider_id: props.campaign?.email_provider_id ?? '',
});

const enhanceForm = useForm({
    subject: '',
    html_body: '',
    text_body: '',
    tone: 'professional',
});

watch(() => page.props.flash?.enhanced_content, (content) => {
    if (!content) return;
    form.subject = content.subject ?? form.subject;
    form.html_body = content.html_body ?? form.html_body;
    form.text_body = content.text_body ?? form.text_body;
}, { immediate: true });

const submit = () => {
    if (isEdit) {
        form.put(route('admin.email.campaigns.update', props.campaign.id));
        return;
    }
    form.post(route('admin.email.campaigns.store'));
};

const enhanceWithAi = () => {
    enhanceForm.subject = form.subject;
    enhanceForm.html_body = form.html_body;
    enhanceForm.text_body = form.text_body;

    if (isEdit) {
        enhanceForm.post(route('admin.email.campaigns.enhance', props.campaign.id), {
            preserveScroll: true,
        });
        return;
    }

    enhanceForm.post(route('admin.email.enhance-content'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Campaign' : 'New Campaign'" />
    <AdminLayout>
        <template #header>
            <h4 class="mb-0 fw-bold">{{ isEdit ? 'Edit campaign' : 'New email campaign' }}</h4>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>
        <div v-if="form.errors.ai_enhance || enhanceForm.errors.ai_enhance" class="alert alert-danger mb-4" role="alert">{{ form.errors.ai_enhance || enhanceForm.errors.ai_enhance }}</div>

        <form class="mx-auto vstack gap-4" style="max-width: 56rem;" @submit.prevent="submit">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <InputLabel for="name" value="Campaign name" />
                            <TextInput id="name" v-model="form.name" class="mt-1" required />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <div class="col-12">
                            <InputLabel for="subject" value="Subject line" />
                            <TextInput id="subject" v-model="form.subject" class="mt-1" required />
                            <InputError class="mt-1" :message="form.errors.subject" />
                        </div>
                        <div class="col-sm-6">
                            <InputLabel for="from_email" value="From email (optional)" />
                            <TextInput id="from_email" v-model="form.from_email" type="email" class="mt-1" />
                        </div>
                        <div class="col-sm-6">
                            <InputLabel for="from_name" value="From name (optional)" />
                            <TextInput id="from_name" v-model="form.from_name" class="mt-1" />
                        </div>
                        <div class="col-12">
                            <InputLabel for="provider" value="Preferred provider" />
                            <select id="provider" v-model="form.email_provider_id" class="form-select mt-1">
                                <option value="">Auto (by priority)</option>
                                <option v-for="p in providers.data" :key="p.id" :value="p.id">{{ p.name }} ({{ p.slug }})</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <InputLabel value="Email body" class="mb-0" />
                        <SecondaryButton type="button" :disabled="enhanceForm.processing" @click="enhanceWithAi">
                            {{ enhanceForm.processing ? 'Enhancing…' : '✨ Enhance with AI' }}
                        </SecondaryButton>
                    </div>
                    <EmailHtmlEditor v-model="form.html_body" />
                    <InputError class="mt-2" :message="form.errors.html_body" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <SecondaryButton type="button" @click="router.visit(route('admin.email.campaigns.index'))">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">{{ isEdit ? 'Save campaign' : 'Create campaign' }}</PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
