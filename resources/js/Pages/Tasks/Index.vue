<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tasks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { can } = useAuth();

const setFilter = (key, value) => {
    router.get(route('tasks.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const completeTask = (taskId) => {
    router.post(route('tasks.complete', taskId), {}, { preserveScroll: true });
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : 'No due date');
</script>

<template>
    <Head title="Tasks" />

    <AdminLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <h4 class="mb-0 fw-bold">Tasks</h4>
                <div class="d-flex gap-2">
                    <select
                        class="form-select form-select-sm"
                        style="width: auto;"
                        :value="filters.status ?? 'open'"
                        @change="setFilter('status', $event.target.value)"
                    >
                        <option value="open">Open</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select
                        class="form-select form-select-sm"
                        style="width: auto;"
                        :value="filters.assigned_to ?? ''"
                        @change="setFilter('assigned_to', $event.target.value)"
                    >
                        <option value="">All assignees</option>
                        <option value="me">Assigned to me</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="card">
            <div v-if="tasks.data.length === 0" class="card-body text-muted text-center">
                No tasks found.
            </div>
            <ul v-else class="list-group list-group-flush">
                <li
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3"
                >
                    <div>
                        <div class="fw-medium">
                            {{ task.title }}
                            <span v-if="task.is_overdue" class="badge bg-label-danger ms-2">Overdue</span>
                        </div>
                        <div class="small text-muted">
                            {{ task.status }}
                            · {{ formatDate(task.due_at) }}
                            <span v-if="task.lead">
                                ·
                                <Link :href="route('leads.show', task.lead.id)" class="link-primary">
                                    {{ task.lead.name }}
                                </Link>
                            </span>
                        </div>
                    </div>
                    <SecondaryButton
                        v-if="can('crm.tasks.manage') && task.status !== 'completed'"
                        class="btn-sm"
                        @click="completeTask(task.id)"
                    >
                        Complete
                    </SecondaryButton>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>
