<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900">Profile</h1>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
        </div>
    </AdminLayout>
</template>
