<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    emptyMessage: {
        type: String,
        default: 'No records found.',
    },
    isEmpty: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div
            v-if="title || $slots.header"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6"
        >
            <h3 v-if="title" class="text-base font-semibold text-slate-800">
                {{ title }}
            </h3>
            <slot name="header" />
        </div>

        <div v-if="$slots.toolbar" class="border-b border-slate-100 px-4 py-3 sm:px-6">
            <slot name="toolbar" />
        </div>

        <div v-if="isEmpty" class="px-6 py-10 text-center text-sm text-slate-400">
            {{ emptyMessage }}
        </div>

        <template v-else>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <slot name="head" />
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <slot />
                    </tbody>
                </table>
            </div>

            <slot name="footer" />
        </template>
    </div>
</template>
