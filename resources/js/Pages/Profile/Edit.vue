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
            <h4 class="mb-0 fw-bold">Profile</h4>
        </template>

        <div class="mx-auto vstack gap-4" style="max-width: 48rem;">
            <div class="card">
                <div class="card-body p-4 p-md-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-6">
                    <UpdatePasswordForm />
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-6">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
