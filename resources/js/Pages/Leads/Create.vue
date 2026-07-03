<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    website: '',
    company: '',
    job_title: '',
    source: 'manual',
    notes: '',
});

const submit = () => {
    form.post(route('leads.store'));
};
</script>

<template>
    <Head title="Create Lead" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900">Create Lead</h1>
        </template>

        <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div
                v-if="page.props.flash.duplicate_lead_id"
                class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                A matching lead already exists.
                <Link
                    :href="route('leads.show', page.props.flash.duplicate_lead_id)"
                    class="font-medium text-amber-950 underline"
                >
                    View existing lead
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <InputError class="mb-4" :message="form.errors.duplicate" />

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="first_name" value="First name" />
                        <TextInput id="first_name" v-model="form.first_name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel for="last_name" value="Last name" />
                        <TextInput id="last_name" v-model="form.last_name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Phone" />
                        <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>
                </div>

                <div>
                    <InputLabel for="website" value="Website" />
                    <TextInput id="website" v-model="form.website" class="mt-1 block w-full" placeholder="example.com" />
                    <InputError class="mt-2" :message="form.errors.website" />
                    <p class="mt-1 text-xs text-slate-500">Used for deduplication alongside email and phone.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="company" value="Company" />
                        <TextInput id="company" v-model="form.company" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.company" />
                    </div>
                    <div>
                        <InputLabel for="job_title" value="Job title" />
                        <TextInput id="job_title" v-model="form.job_title" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.job_title" />
                    </div>
                </div>

                <div>
                    <InputLabel for="source" value="Source" />
                    <TextInput id="source" v-model="form.source" class="mt-1 block w-full" placeholder="manual, scraper, import, api" />
                    <InputError class="mt-2" :message="form.errors.source" />
                </div>

                <div>
                    <InputLabel for="notes" value="Notes" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.notes" />
                </div>

                <p class="text-sm text-slate-500">
                    Score is calculated automatically when the lead is saved.
                </p>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Save Lead</PrimaryButton>
                    <Link :href="route('leads.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
