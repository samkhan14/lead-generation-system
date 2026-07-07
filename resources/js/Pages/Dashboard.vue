<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_leads: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <h4 class="mb-0 fw-bold">Dashboard</h4>
        </template>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-body-secondary">Total leads</small>
                        <h3 class="mb-0 mt-2">{{ stats.total_leads }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-body-secondary">With scores</small>
                        <h3 class="mb-0 mt-2">{{ stats.leads_with_scores }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-body-secondary">Added today</small>
                        <h3 class="mb-0 mt-2">{{ stats.leads_today }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent leads</h5>
                <Link :href="route('leads.index')" class="link-primary small">
                    View all
                </Link>
            </div>
            <div v-if="recent_leads.length === 0" class="card-body text-center text-muted">
                No leads yet.
            </div>
            <ul v-else class="list-group list-group-flush">
                <li
                    v-for="lead in recent_leads"
                    :key="lead.id"
                    class="list-group-item d-flex align-items-center justify-content-between"
                >
                    <div>
                        <Link :href="route('leads.show', lead.id)" class="fw-medium link-primary">
                            {{ lead.full_name }}
                        </Link>
                        <div class="small text-muted">{{ lead.email || 'No email' }}</div>
                    </div>
                    <div class="text-end small text-muted">
                        <span v-if="lead.latest_score !== null">Score: {{ lead.latest_score }}</span>
                        <span v-else>No score</span>
                    </div>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>
