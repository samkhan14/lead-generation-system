<script setup>
import { computed } from 'vue';

const props = defineProps({
    job: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isCompleted = computed(() => props.job?.status === 'completed');
const isFailed = computed(() => props.job?.status === 'failed');

const successRate = computed(() => {
    const total = props.job?.total_found ?? 0;
    if (total === 0) return 0;
    return Math.round(((props.job?.created_count ?? 0) / total) * 100);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="job && job.status !== 'pending' && job.status !== 'running'"
            class="toast toast-placement-ex show position-fixed bottom-0 end-0 m-4"
            style="width: 320px; z-index: 1090;"
        >
            <div
                class="toast-header"
                :class="isCompleted ? 'bg-label-success' : 'bg-label-danger'"
            >
                <i
                    class="me-2"
                    :class="isCompleted ? 'ri-checkbox-circle-line text-success' : 'ri-error-warning-line text-danger'"
                />
                <div class="me-auto">
                    <strong class="me-auto">{{ isCompleted ? 'Scrape completed!' : 'Scrape failed' }}</strong>
                    <div class="small text-muted">{{ job.search_label ?? job.keyword }}</div>
                </div>
                <button type="button" class="btn-close" aria-label="Close" @click="emit('close')" />
            </div>

            <div v-if="isCompleted" class="toast-body">
                <div class="row g-2 text-center small">
                    <div class="col-4">
                        <div class="fw-bold text-success">{{ job.created_count }}</div>
                        <div class="text-muted">Created</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-warning">{{ job.duplicate_count }}</div>
                        <div class="text-muted">Duplicates</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-danger">{{ job.failed_count }}</div>
                        <div class="text-muted">Failed</div>
                    </div>
                </div>
            </div>

            <div v-if="isFailed" class="toast-body">
                <p class="small text-muted mb-0">{{ job.error_message ?? 'Unknown error.' }}</p>
            </div>

            <div class="border-top px-3 py-2 d-flex justify-content-between small text-muted">
                <span v-if="job.scraper_used" class="text-capitalize">via {{ job.scraper_used?.replace('_', ' ') }}</span>
                <span v-else>&nbsp;</span>
                <span v-if="isCompleted">{{ successRate }}% success rate</span>
            </div>
        </div>
    </Transition>
</template>
