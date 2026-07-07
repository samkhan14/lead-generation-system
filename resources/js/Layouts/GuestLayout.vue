<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import useMaterio from '@/materio/composables/useMaterio';
import { Link } from '@inertiajs/vue3';

useMaterio({
    htmlClass: 'layout-wide customizer-hide',
    initMenu: false,
});
</script>

<template>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card px-sm-6 px-0">
                    <div class="card-body">
                        <div class="app-brand justify-content-center mb-6">
                            <Link href="/" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <ApplicationLogo style="height: 32px; width: auto;" />
                                </span>
                                <span class="app-brand-text demo text-heading fw-bold">Lead CRM</span>
                            </Link>
                        </div>

                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
