<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaign: { type: Object, required: true },
    providers: { type: Object, default: () => ({ data: [] }) },
});

const page = usePage();
const { can } = useAuth();
const showTestForm = ref(false);

const testForm = useForm({ to_email: page.props.auth?.user?.email ?? '' });
const sendForm = useForm({ lead_ids: [] });

const sendTest = () => {
    testForm.post(route('admin.email.campaigns.test', props.campaign.id), {
        preserveScroll: true,
        onSuccess: () => { showTestForm.value = false; },
    });
};

const dispatchCampaign = () => {
    if (!window.confirm('Send this campaign to all leads with email addresses?')) return;
    sendForm.post(route('admin.email.campaigns.send', props.campaign.id));
};
</script>

<template>
    <Head :title="campaign.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">{{ campaign.name }}</h4>
                    <p class="small text-muted mb-0">{{ campaign.subject }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <Link v-if="campaign.status === 'draft' && can('email.campaigns.update')" :href="route('admin.email.campaigns.edit', campaign.id)">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                    <SecondaryButton v-if="can('email.campaigns.send')" type="button" @click="showTestForm = !showTestForm">Send test</SecondaryButton>
                    <PrimaryButton v-if="can('email.campaigns.send') && campaign.status === 'draft'" type="button" :disabled="sendForm.processing" @click="dispatchCampaign">Send campaign</PrimaryButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <div v-if="showTestForm" class="card border-primary mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end" @submit.prevent="sendTest">
                    <div class="col-md flex-grow-1">
                        <label class="form-label">Test recipient email</label>
                        <TextInput v-model="testForm.to_email" type="email" class="mt-1" required />
                    </div>
                    <div class="col-md-auto">
                        <PrimaryButton :disabled="testForm.processing">Queue test email</PrimaryButton>
                    </div>
                </form>
                <p v-if="testForm.errors.to_email" class="text-danger small mt-2 mb-0">{{ testForm.errors.to_email }}</p>
                <p v-if="testForm.errors.test_email" class="text-danger small mt-2 mb-0">{{ testForm.errors.test_email }}</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Details</h5>
                        <dl class="row g-2 mb-0 small">
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Status</dt>
                                <dd class="mb-0 text-capitalize">{{ campaign.status }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">AI enhanced</dt>
                                <dd class="mb-0">{{ campaign.ai_enhanced ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Sends</dt>
                                <dd class="mb-0">{{ campaign.sends_count ?? 0 }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">From</dt>
                                <dd class="mb-0">{{ campaign.from_email || 'Provider default' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Preview</h5>
                        <div class="border rounded p-3 bg-body-secondary small" v-html="campaign.html_body" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
