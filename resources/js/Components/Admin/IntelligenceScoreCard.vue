<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    score: {
        type: Number,
        required: true,
    },
    signals: {
        type: Array,
        default: () => [],
    },
    accent: {
        type: String,
        default: 'indigo',
    },
});

const accentClasses = {
    indigo: 'ring-indigo-100 bg-indigo-50 text-indigo-700',
    emerald: 'ring-emerald-100 bg-emerald-50 text-emerald-700',
    sky: 'ring-sky-100 bg-sky-50 text-sky-700',
};
</script>

<template>
    <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-slate-900">{{ title }}</h4>
            <span
                class="rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1 ring-inset"
                :class="accentClasses[accent] ?? accentClasses.indigo"
            >
                {{ score }}
            </span>
        </div>
        <ul v-if="signals.length" class="mt-3 space-y-1">
            <li v-for="signal in signals" :key="signal" class="text-xs text-slate-500">
                • {{ signal }}
            </li>
        </ul>
        <p v-else class="mt-3 text-xs text-slate-400">No signals detected.</p>
    </div>
</template>
