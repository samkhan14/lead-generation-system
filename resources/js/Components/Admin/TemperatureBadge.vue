<script setup>
defineProps({
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
    hot: 'bg-label-danger',
    warm: 'bg-label-warning',
    cold: 'bg-label-secondary',
};
</script>

<template>
    <span
        v-if="temperature"
        class="badge rounded-pill"
        :class="classes[temperature] ?? classes.cold"
    >
        {{ labels[temperature] ?? temperature.toUpperCase() }}
    </span>
</template>
