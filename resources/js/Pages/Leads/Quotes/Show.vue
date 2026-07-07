<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    lead: { type: Object, required: true },
    quote: { type: Object, required: true },
});

const { can } = useAuth();

const markSent = () => {
    router.post(route('leads.quotes.send', [props.lead.id, props.quote.id]), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="quote.title" />

    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <h4 class="mb-0 fw-bold">{{ quote.title }}</h4>
                    <p class="text-muted small mb-0">{{ lead.full_name }} · {{ quote.status_label }}</p>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('leads.show', lead.id)">
                        <SecondaryButton>Back to lead</SecondaryButton>
                    </Link>
                    <PrimaryButton
                        v-if="can('crm.quotes.manage') && quote.status === 'draft'"
                        @click="markSent"
                    >
                        Mark as sent
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="col-lg-10">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Subject:</strong> {{ quote.subject }}
                </div>
                <div class="card-body">
                    <div class="border rounded p-4 bg-white" v-html="quote.html_body" />
                </div>
            </div>

            <div v-if="quote.text_body" class="card">
                <div class="card-header">
                    <h6 class="mb-0">Plain text version</h6>
                </div>
                <div class="card-body">
                    <pre class="mb-0 small" style="white-space: pre-wrap;">{{ quote.text_body }}</pre>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
