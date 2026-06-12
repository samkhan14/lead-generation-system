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
            class="fixed bottom-6 right-6 z-50 w-80 rounded-xl shadow-2xl border"
            :class="isCompleted ? 'bg-white border-emerald-200' : 'bg-white border-red-200'"
        >
            <div
                class="flex items-start gap-3 p-4 rounded-t-xl"
                :class="isCompleted ? 'bg-emerald-50' : 'bg-red-50'"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="isCompleted ? 'bg-emerald-100' : 'bg-red-100'"
                >
                    <svg
                        v-if="isCompleted"
                        class="h-5 w-5 text-emerald-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg
                        v-else
                        class="h-5 w-5 text-red-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold" :class="isCompleted ? 'text-emerald-800' : 'text-red-800'">
                        {{ isCompleted ? 'Scrape completed!' : 'Scrape failed' }}
                    </p>
                    <p class="text-xs mt-0.5" :class="isCompleted ? 'text-emerald-600' : 'text-red-600'">
                        {{ job.search_label ?? job.keyword }}
                    </p>
                </div>

                <button
                    class="shrink-0 rounded p-0.5 hover:bg-slate-200 transition-colors"
                    @click="emit('close')"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="isCompleted" class="p-4 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-lg bg-emerald-50 p-2">
                    <div class="text-lg font-bold text-emerald-700">{{ job.created_count }}</div>
                    <div class="text-slate-500">Created</div>
                </div>
                <div class="rounded-lg bg-amber-50 p-2">
                    <div class="text-lg font-bold text-amber-700">{{ job.duplicate_count }}</div>
                    <div class="text-slate-500">Duplicates</div>
                </div>
                <div class="rounded-lg bg-red-50 p-2">
                    <div class="text-lg font-bold text-red-700">{{ job.failed_count }}</div>
                    <div class="text-slate-500">Failed</div>
                </div>
            </div>

            <div v-if="isFailed" class="p-4">
                <p class="text-xs text-slate-500 line-clamp-2">{{ job.error_message ?? 'Unknown error.' }}</p>
            </div>

            <div class="border-t border-slate-100 px-4 py-2 flex items-center justify-between text-xs text-slate-500">
                <span v-if="job.scraper_used" class="capitalize">via {{ job.scraper_used?.replace('_', ' ') }}</span>
                <span v-else>&nbsp;</span>
                <span v-if="isCompleted">{{ successRate }}% success rate</span>
            </div>
        </div>
    </Transition>
</template>
