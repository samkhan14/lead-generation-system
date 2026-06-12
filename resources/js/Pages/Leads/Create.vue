<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    company: '',
    job_title: '',
    source: '',
    notes: '',
    score: '',
});

const submit = () => {
    form.post(route('leads.store'));
};
</script>

<template>
    <Head title="Create Lead" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Lead
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="first_name" value="First name" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.first_name" />
                            </div>

                            <div>
                                <InputLabel for="last_name" value="Last name" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.last_name" />
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Phone" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="company" value="Company" />
                                <TextInput
                                    id="company"
                                    v-model="form.company"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.company" />
                            </div>

                            <div>
                                <InputLabel for="job_title" value="Job title" />
                                <TextInput
                                    id="job_title"
                                    v-model="form.job_title"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.job_title" />
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="source" value="Source" />
                                <TextInput
                                    id="source"
                                    v-model="form.source"
                                    class="mt-1 block w-full"
                                    placeholder="scraper, import, manual"
                                />
                                <InputError class="mt-2" :message="form.errors.source" />
                            </div>

                            <div>
                                <InputLabel for="score" value="Initial score (0–100)" />
                                <TextInput
                                    id="score"
                                    v-model="form.score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.score" />
                            </div>
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

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Save Lead
                            </PrimaryButton>
                            <Link :href="route('leads.index')">
                                <SecondaryButton type="button">
                                    Cancel
                                </SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
