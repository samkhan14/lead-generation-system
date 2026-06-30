<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ServiceFormModal from '@/Components/Admin/ServiceFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
    serviceOptions: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { can } = useAuth();

const showEditModal = ref(false);
const showDeleteModal = ref(false);

const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.services.destroy', props.service.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const statusClass = (status) => {
    return {
        active: 'bg-emerald-100 text-emerald-800',
        draft: 'bg-amber-100 text-amber-800',
        archived: 'bg-slate-100 text-slate-600',
    }[status] ?? 'bg-slate-100 text-slate-600';
};

const relatedNames = (ids) => {
    return (ids || [])
        .map((id) => props.serviceOptions.find((item) => item.id === id)?.name)
        .filter(Boolean);
};
</script>

<template>
    <Head :title="service.name" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="route('admin.services.index')" class="text-sm text-slate-500 hover:text-slate-700">
                        ← Back to services
                    </Link>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ service.name }}</h1>
                    <p class="text-sm text-slate-500">{{ service.slug }} · v{{ service.version }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('services.update')" type="button" @click="showEditModal = true">
                        Edit
                    </PrimaryButton>
                    <DangerButton v-if="can('services.delete')" type="button" @click="showDeleteModal = true">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </template>

        <div
            v-if="page.props.flash.success"
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ page.props.flash.success }}
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Overview</h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-600">
                        {{ service.description || 'No description provided.' }}
                    </p>
                    <div class="mt-4">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusClass(service.status)">
                            {{ service.status }}
                        </span>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Features</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li v-for="item in service.features || []" :key="item">{{ item }}</li>
                        <li v-if="!(service.features || []).length" class="list-none pl-0 text-slate-400">None listed</li>
                    </ul>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Benefits</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li v-for="item in service.benefits || []" :key="item">{{ item }}</li>
                        <li v-if="!(service.benefits || []).length" class="list-none pl-0 text-slate-400">None listed</li>
                    </ul>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Deliverables</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li v-for="item in service.deliverables || []" :key="item">{{ item }}</li>
                        <li v-if="!(service.deliverables || []).length" class="list-none pl-0 text-slate-400">None listed</li>
                    </ul>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">FAQs</h2>
                    <div class="mt-3 space-y-4">
                        <div v-for="(faq, index) in service.faqs || []" :key="`faq-${index}`">
                            <div class="font-medium text-sm text-slate-900">{{ faq.question }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ faq.answer }}</div>
                        </div>
                        <p v-if="!(service.faqs || []).length" class="text-sm text-slate-400">No FAQs yet.</p>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Objections</h2>
                    <div class="mt-3 space-y-4">
                        <div v-for="(item, index) in service.objections || []" :key="`objection-${index}`">
                            <div class="font-medium text-sm text-slate-900">{{ item.objection }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ item.response }}</div>
                        </div>
                        <p v-if="!(service.objections || []).length" class="text-sm text-slate-400">No objections documented.</p>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Pricing notes</h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-600">
                        {{ service.pricing_notes || 'No pricing notes.' }}
                    </p>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Tags</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="tag in service.tags || []"
                            :key="tag"
                            class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600"
                        >
                            {{ tag }}
                        </span>
                        <span v-if="!(service.tags || []).length" class="text-sm text-slate-400">No tags</span>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Related services</h2>
                    <div class="mt-4 space-y-4 text-sm">
                        <div>
                            <div class="font-medium text-slate-700">Cross-sell</div>
                            <ul class="mt-1 list-disc pl-5 text-slate-600">
                                <li v-for="name in relatedNames(service.cross_sell_ids)" :key="`cross-${name}`">{{ name }}</li>
                                <li v-if="!relatedNames(service.cross_sell_ids).length" class="list-none pl-0 text-slate-400">None</li>
                            </ul>
                        </div>
                        <div>
                            <div class="font-medium text-slate-700">Upsell</div>
                            <ul class="mt-1 list-disc pl-5 text-slate-600">
                                <li v-for="name in relatedNames(service.upsell_ids)" :key="`upsell-${name}`">{{ name }}</li>
                                <li v-if="!relatedNames(service.upsell_ids).length" class="list-none pl-0 text-slate-400">None</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Audit</h2>
                    <dl class="mt-3 space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between gap-4">
                            <dt>Created by</dt>
                            <dd>{{ service.creator?.name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Updated by</dt>
                            <dd>{{ service.updater?.name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Created</dt>
                            <dd>{{ service.created_at ? new Date(service.created_at).toLocaleString() : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Updated</dt>
                            <dd>{{ service.updated_at ? new Date(service.updated_at).toLocaleString() : '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>

        <ServiceFormModal
            :show="showEditModal"
            mode="edit"
            :service="service"
            :status-options="statusOptions"
            :service-options="serviceOptions"
            @close="showEditModal = false"
        />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Delete service</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete <strong>{{ service.name }}</strong>? This action can be undone only from database backups.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                    <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">
                        Delete service
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
