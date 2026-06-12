<script setup>
const props = defineProps({
    temperature: {
        type: String,
        default: null,
    },
});

const labels = {
    hot: 'HOT',
    warm: 'WARM',
    cold: 'COLD',
};

const classes = {
    hot: 'bg-red-100 text-red-800 ring-red-200',
    warm: 'bg-amber-100 text-amber-800 ring-amber-200',
    cold: 'bg-slate-100 text-slate-600 ring-slate-200',
};
</script>

<template>
    <span
        v-if="temperature"
        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
        :class="classes[temperature] ?? classes.cold"
    >
        {{ labels[temperature] ?? temperature.toUpperCase() }}
    </span>
</template>
