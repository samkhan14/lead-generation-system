<script setup>
const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'sm',
    },
});

const config = {
    fully_verified: { label: 'Fully Verified', classes: 'bg-label-success' },
    verified: { label: 'Verified', classes: 'bg-label-success' },
    partial: { label: 'Partial', classes: 'bg-label-warning' },
    unverified: { label: 'Unverified', classes: 'bg-label-danger' },
};

const pending = { label: 'Pending', classes: 'bg-label-secondary' };

const current = props.status ? (config[props.status] ?? pending) : pending;
</script>

<template>
    <span
        class="badge"
        :class="[
            current.classes,
            size === 'xs' ? 'badge-sm' : '',
        ]"
    >
        {{ current.label }}
    </span>
</template>
