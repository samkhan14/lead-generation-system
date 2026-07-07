<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    lead: { type: Object, required: true },
    pipelineStages: { type: Array, required: true },
    lostReasons: { type: Array, required: true },
    users: { type: Array, required: true },
});

const form = useForm({
    first_name: props.lead.first_name,
    last_name: props.lead.last_name,
    email: props.lead.email,
    phone: props.lead.phone,
    website: props.lead.website ?? '',
    company: props.lead.company ?? '',
    job_title: props.lead.job_title ?? '',
    notes: props.lead.notes ?? '',
    status: props.lead.status,
    lost_reason: props.lead.lost_reason ?? '',
    assigned_to: props.lead.assigned_to ?? '',
});

const showLostReason = computed(() => form.status === 'lost');

const submit = () => {
    form.put(route('leads.update', props.lead.id));
};
</script>

<template>
    <Head title="Edit Lead" />

    <AdminLayout>
        <template #header>
            <div class="d-flex align-items-center justify-content-between w-100">
                <h4 class="mb-0 fw-bold">Edit lead</h4>
                <Link :href="route('leads.show', lead.id)">
                    <SecondaryButton>Cancel</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
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

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <InputLabel for="company" value="Company" />
                                <TextInput id="company" v-model="form.company" class="mt-1" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="job_title" value="Job title" />
                                <TextInput id="job_title" v-model="form.job_title" class="mt-1" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="website" value="Website" />
                            <TextInput id="website" v-model="form.website" class="mt-1" />
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <InputLabel value="Pipeline stage" />
                                <select v-model="form.status" class="form-select mt-1">
                                    <option v-for="stage in pipelineStages" :key="stage.value" :value="stage.value">
                                        {{ stage.label }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="showLostReason" class="col-md-6">
                                <InputLabel value="Lost reason" />
                                <select v-model="form.lost_reason" class="form-select mt-1">
                                    <option value="">Select reason</option>
                                    <option v-for="reason in lostReasons" :key="reason.value" :value="reason.value">
                                        {{ reason.label }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.lost_reason" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel value="Assigned to" />
                            <select v-model="form.assigned_to" class="form-select mt-1">
                                <option value="">Unassigned</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="form-control mt-1" rows="4" />
                        </div>

                        <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
