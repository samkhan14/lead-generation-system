<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <GuestLayout>
        <Head title="Welcome" />

        <div class="text-center mb-6">
            <h4 class="mb-2">SalesIntel</h4>
            <p class="text-muted mb-0">AI-powered lead management and outreach</p>
        </div>

        <div v-if="canLogin" class="d-grid gap-3">
            <Link v-if="$page.props.auth?.user" :href="route('dashboard')">
                <PrimaryButton type="button" class="w-100">Go to dashboard</PrimaryButton>
            </Link>
            <template v-else>
                <Link :href="route('login')">
                    <PrimaryButton type="button" class="w-100">Log in</PrimaryButton>
                </Link>
                <Link v-if="canRegister" :href="route('register')">
                    <SecondaryButton type="button" class="w-100">Register</SecondaryButton>
                </Link>
            </template>
        </div>

        <p class="text-center text-muted small mt-6 mb-0">
            Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
        </p>
    </GuestLayout>
</template>
