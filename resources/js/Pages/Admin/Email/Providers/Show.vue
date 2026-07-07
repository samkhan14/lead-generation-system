<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailProviderFormModal from '@/Components/Admin/EmailProviderFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    provider: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    slugOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const showEditModal = ref(false);

const destroyProvider = () => {
    if (!window.confirm('Delete this email provider?')) return;
    router.delete(route('admin.email.providers.destroy', props.provider.id));
};
</script>

<template>
    <Head :title="provider.name" />
    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">{{ provider.name }}</h4>
                    <p class="small text-muted mb-0">{{ provider.slug }} · priority {{ provider.priority }}</p>
                </div>
                <div class="d-flex gap-2">
                    <PrimaryButton v-if="can('email.providers.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('email.providers.delete')" type="button" @click="destroyProvider">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">{{ page.props.flash.success }}</div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Configuration</h5>
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Status</dt>
                                <dd class="mb-0 text-capitalize">{{ provider.status }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Usable</dt>
                                <dd class="mb-0" :class="provider.is_usable ? 'text-success' : 'text-danger'">{{ provider.is_usable ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">API key</dt>
                                <dd class="mb-0">{{ provider.has_api_key ? 'Configured' : 'Not set' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">From email</dt>
                                <dd class="mb-0">{{ provider.default_from_email || '—' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">From name</dt>
                                <dd class="mb-0">{{ provider.default_from_name || '—' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Total sends</dt>
                                <dd class="mb-0">{{ provider.sends_count ?? 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <EmailProviderFormModal :show="showEditModal" mode="edit" :provider="provider" :status-options="statusOptions" :slug-options="slugOptions" @close="showEditModal = false" />
    </AdminLayout>
</template>
