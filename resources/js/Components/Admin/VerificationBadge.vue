<script setup>
const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'sm', // 'xs' | 'sm'
    },
});

const config = {
    fully_verified: {
        label: 'Fully Verified',
        classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        dot: 'bg-emerald-500',
    },
    verified: {
        label: 'Verified',
        classes: 'bg-green-50 text-green-700 ring-green-200',
        dot: 'bg-green-400',
    },
    partial: {
        label: 'Partial',
        classes: 'bg-amber-50 text-amber-700 ring-amber-200',
        dot: 'bg-amber-400',
    },
    unverified: {
        label: 'Unverified',
        classes: 'bg-red-50 text-red-700 ring-red-100',
        dot: 'bg-red-400',
    },
};

const pending = {
    label: 'Pending',
    classes: 'bg-slate-50 text-slate-500 ring-slate-200',
    dot: 'bg-slate-400',
};

const current = props.status ? (config[props.status] ?? pending) : pending;
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 font-medium ring-1 ring-inset',
            size === 'xs' ? 'rounded px-1.5 py-0.5 text-xs' : 'rounded-md px-2 py-1 text-xs',
            current.classes,
        ]"
    >
        <span :class="['h-1.5 w-1.5 rounded-full', current.dot]" />
        {{ current.label }}
    </span>
</template>
