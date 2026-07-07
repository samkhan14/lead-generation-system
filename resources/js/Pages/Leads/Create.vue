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
            <h4 class="mb-0 fw-bold">Create Lead</h4>
        </template>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div
                        v-if="page.props.flash.duplicate_lead_id"
                        class="alert alert-warning mb-4"
                    >
                        A matching lead already exists.
                        <Link
                            :href="route('leads.show', page.props.flash.duplicate_lead_id)"
                            class="alert-link fw-medium"
                        >
                            View existing lead
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <InputError class="mb-3" :message="form.errors.duplicate" />

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <InputLabel for="first_name" value="First name" />
                                <TextInput id="first_name" v-model="form.first_name" class="mt-1" required />
                                <InputError class="mt-1" :message="form.errors.first_name" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="last_name" value="Last name" />
                                <TextInput id="last_name" v-model="form.last_name" class="mt-1" required />
                                <InputError class="mt-1" :message="form.errors.last_name" />
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1" required />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="phone" value="Phone" />
                                <TextInput id="phone" v-model="form.phone" class="mt-1" required />
                                <InputError class="mt-1" :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="website" value="Website" />
                            <TextInput id="website" v-model="form.website" class="mt-1" placeholder="example.com" />
                            <InputError class="mt-1" :message="form.errors.website" />
                            <div class="form-text">Used for deduplication alongside email and phone.</div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <InputLabel for="company" value="Company" />
                                <TextInput id="company" v-model="form.company" class="mt-1" />
                                <InputError class="mt-1" :message="form.errors.company" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="job_title" value="Job title" />
                                <TextInput id="job_title" v-model="form.job_title" class="mt-1" />
                                <InputError class="mt-1" :message="form.errors.job_title" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="source" value="Source" />
                            <TextInput id="source" v-model="form.source" class="mt-1" placeholder="manual, scraper, import, api" />
                            <InputError class="mt-1" :message="form.errors.source" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="notes" value="Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="form-control mt-1"
                            />
                            <InputError class="mt-1" :message="form.errors.notes" />
                        </div>

                        <p class="text-muted small mb-4">
                            Score is calculated automatically when the lead is saved.
                        </p>

                        <div class="d-flex gap-2">
                            <PrimaryButton :disabled="form.processing">Save Lead</PrimaryButton>
                            <Link :href="route('leads.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
