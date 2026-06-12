<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    props.active
        ? 'flex items-center gap-3 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white'
        : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot name="icon" />
        <span><slot /></span>
    </Link>
</template>
