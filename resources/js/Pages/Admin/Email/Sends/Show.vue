<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    send: { type: Object, required: true },
});
</script>

<template>
    <Head title="Email Send" />
    <AdminLayout>
        <template #header>
            <h4 class="mb-0 fw-bold">Email send #{{ send.id }}</h4>
        </template>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">To</dt>
                                <dd class="mb-0">{{ send.to_email }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Subject</dt>
                                <dd class="mb-0">{{ send.subject }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Status</dt>
                                <dd class="mb-0 text-capitalize">{{ send.status }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Provider message ID</dt>
                                <dd class="mb-0 text-truncate" style="max-width: 12rem;">{{ send.provider_message_id || '—' }}</dd>
                            </div>
                            <div v-if="send.error_message" class="col-12">
                                <div class="alert alert-danger mb-0 small">{{ send.error_message }}</div>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <dt class="text-muted mb-0">Sent at</dt>
                                <dd class="mb-0">{{ send.sent_at || '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Body preview</h5>
                        <div class="small" v-html="send.html_body" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
