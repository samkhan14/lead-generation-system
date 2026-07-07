<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ServiceFormModal from '@/Components/Admin/ServiceFormModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    service: { type: Object, required: true },
    serviceOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    complexityOptions: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();

const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(route('admin.services.destroy', props.service.id), {
        onSuccess: () => { showDeleteModal.value = false; },
    });
};

const statusClass = (status) => ({
    active: 'bg-label-success',
    draft: 'bg-label-warning',
    archived: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');

const relatedNames = (ids) => (ids || [])
    .map((id) => props.serviceOptions.find((item) => item.id === id)?.name)
    .filter(Boolean);

const listSection = (title, items) => ({ title, items: items || [] });
</script>

<template>
    <Head :title="service.name" />

    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <div>
                    <Link :href="route('admin.services.index')" class="small link-primary">← Back to services</Link>
                    <h4 class="mb-0 fw-bold mt-1">{{ service.name }}</h4>
                    <p class="mb-0 small text-muted">{{ service.slug }} · v{{ service.version }} · order {{ service.sort_order ?? 0 }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <PrimaryButton v-if="can('services.update')" type="button" @click="showEditModal = true">Edit</PrimaryButton>
                    <DangerButton v-if="can('services.delete')" type="button" @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div v-if="page.props.flash.success" class="alert alert-success mb-4" role="alert">
            {{ page.props.flash.success }}
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">{{ service.short_description || service.description || 'No summary.' }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill text-capitalize" :class="statusClass(service.status)">{{ service.status }}</span>
                            <span v-if="service.complexity_level" class="badge rounded-pill bg-label-primary text-capitalize">{{ service.complexity_level }}</span>
                            <span v-if="service.typical_timeline" class="badge bg-label-secondary">{{ service.typical_timeline }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="service.detailed_description" class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detailed description</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-pre-wrap">{{ service.detailed_description }}</p>
                    </div>
                </div>

                <div v-if="service.ideal_customer_profile" class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ideal customer profile</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-pre-wrap">{{ service.ideal_customer_profile }}</p>
                    </div>
                </div>

                <div
                    v-for="block in [
                        listSection('Target audience', service.target_audience),
                        listSection('Problems solved', service.problems_solved),
                        listSection('Features', service.features),
                        listSection('Benefits', service.benefits),
                        listSection('Deliverables', service.deliverables),
                        listSection('Discovery questions', service.discovery_questions),
                        listSection('Required before quotation', service.quotation_requirements),
                    ]"
                    :key="block.title"
                    class="card mb-4"
                >
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ block.title }}</h5>
                    </div>
                    <ul v-if="block.items.length" class="list-group list-group-flush">
                        <li v-for="item in block.items" :key="item" class="list-group-item">{{ item }}</li>
                    </ul>
                    <div v-else class="card-body text-muted">None listed</div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">FAQs</h5>
                    </div>
                    <ul v-if="(service.faqs || []).length" class="list-group list-group-flush">
                        <li v-for="(faq, index) in service.faqs" :key="`faq-${index}`" class="list-group-item">
                            <div class="fw-medium">{{ faq.question }}</div>
                            <div class="mt-1 text-muted">{{ faq.answer }}</div>
                        </li>
                    </ul>
                    <div v-else class="card-body text-muted">No FAQs yet.</div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Objections</h5>
                    </div>
                    <ul v-if="(service.objections || []).length" class="list-group list-group-flush">
                        <li v-for="(item, index) in service.objections" :key="`objection-${index}`" class="list-group-item">
                            <div class="fw-medium">{{ item.objection }}</div>
                            <div class="mt-1 text-muted">{{ item.response }}</div>
                        </li>
                    </ul>
                    <div v-else class="card-body text-muted">No objections documented.</div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pricing notes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-pre-wrap">{{ service.pricing_notes || 'No pricing notes.' }}</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Technologies</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="(service.technologies || []).length" class="d-flex flex-wrap gap-2">
                            <span v-for="tech in service.technologies" :key="tech" class="badge bg-label-primary">{{ tech }}</span>
                        </div>
                        <span v-else class="text-muted">None listed</span>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Industry tags</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="(service.tags || []).length" class="d-flex flex-wrap gap-2">
                            <span v-for="tag in service.tags" :key="tag" class="badge bg-label-secondary">{{ tag }}</span>
                        </div>
                        <span v-else class="text-muted">No tags</span>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Related services</h5>
                    </div>
                    <div class="card-body">
                        <div
                            v-for="rel in [{ label: 'Cross-sell', ids: service.cross_sell_ids }, { label: 'Upsell', ids: service.upsell_ids }, { label: 'Related', ids: service.related_service_ids }]"
                            :key="rel.label"
                            class="mb-3"
                        >
                            <div class="fw-medium">{{ rel.label }}</div>
                            <ul v-if="relatedNames(rel.ids).length" class="list-group list-group-flush mt-1">
                                <li v-for="name in relatedNames(rel.ids)" :key="`${rel.label}-${name}`" class="list-group-item px-0">{{ name }}</li>
                            </ul>
                            <div v-else class="text-muted small mt-1">None</div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Audit</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row g-3 mb-0 small">
                            <div class="col-12 d-flex justify-content-between gap-3">
                                <dt class="text-muted mb-0">Created by</dt>
                                <dd class="mb-0 text-end">{{ service.creator?.name || '—' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between gap-3">
                                <dt class="text-muted mb-0">Updated by</dt>
                                <dd class="mb-0 text-end">{{ service.updater?.name || '—' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between gap-3">
                                <dt class="text-muted mb-0">Created</dt>
                                <dd class="mb-0 text-end">{{ service.created_at ? new Date(service.created_at).toLocaleString() : '—' }}</dd>
                            </div>
                            <div class="col-12 d-flex justify-content-between gap-3">
                                <dt class="text-muted mb-0">Updated</dt>
                                <dd class="mb-0 text-end">{{ service.updated_at ? new Date(service.updated_at).toLocaleString() : '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <ServiceFormModal
            :show="showEditModal"
            mode="edit"
            :service="service"
            :status-options="statusOptions"
            :complexity-options="complexityOptions"
            :service-options="serviceOptions"
            @close="showEditModal = false"
        />

        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="modal-header">
                <h5 class="modal-title">Delete service</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="showDeleteModal = false" />
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete <strong>{{ service.name }}</strong>?</p>
            </div>
            <div class="modal-footer">
                <SecondaryButton type="button" @click="showDeleteModal = false">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="deleteForm.processing" @click="confirmDelete">Delete service</DangerButton>
            </div>
        </Modal>
    </AdminLayout>
</template>
