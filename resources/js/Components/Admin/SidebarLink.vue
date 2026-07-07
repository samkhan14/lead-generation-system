<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <li class="menu-item" :class="{ active }">
        <Link :href="href" class="menu-link">
            <i v-if="icon" class="menu-icon tf-icons" :class="icon" />
            <div><slot /></div>
        </Link>
    </li>
</template>
