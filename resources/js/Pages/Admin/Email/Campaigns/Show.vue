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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ campaign.name }}</h1>
                    <p class="text-sm text-slate-500">{{ campaign.subject }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="campaign.status === 'draft' && can('email.campaigns.update')" :href="route('admin.email.campaigns.edit', campaign.id)">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                    <SecondaryButton v-if="can('email.campaigns.send')" type="button" @click="showTestForm = !showTestForm">Send test</SecondaryButton>
                    <PrimaryButton v-if="can('email.campaigns.send') && campaign.status === 'draft'" type="button" :disabled="sendForm.processing" @click="dispatchCampaign">Send campaign</PrimaryButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</div>

        <div v-if="showTestForm" class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="sendTest">
                <div class="min-w-[240px] flex-1">
                    <label class="text-sm font-medium text-slate-700">Test recipient email</label>
                    <TextInput v-model="testForm.to_email" type="email" class="mt-1 block w-full" required />
                </div>
                <PrimaryButton :disabled="testForm.processing">Queue test email</PrimaryButton>
            </form>
            <p v-if="testForm.errors.to_email" class="mt-2 text-sm text-red-600">{{ testForm.errors.to_email }}</p>
            <p v-if="testForm.errors.test_email" class="mt-2 text-sm text-red-600">{{ testForm.errors.test_email }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-1">
                <h2 class="font-semibold text-slate-900">Details</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="capitalize">{{ campaign.status }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">AI enhanced</dt><dd>{{ campaign.ai_enhanced ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Sends</dt><dd>{{ campaign.sends_count ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">From</dt><dd>{{ campaign.from_email || 'Provider default' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <h2 class="font-semibold text-slate-900">Preview</h2>
                <div class="prose prose-sm mt-4 max-w-none rounded-lg border border-slate-200 bg-slate-50 p-4" v-html="campaign.html_body" />
            </div>
        </div>
    </AdminLayout>
</template>
