<script setup>
import AdminFooter from '@/Components/Admin/AdminFooter.vue';
import AdminNavbar from '@/Components/Admin/AdminNavbar.vue';
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import { useAuth } from '@/composables/useAuth';
import useMaterio from '@/materio/composables/useMaterio';

const { user, can } = useAuth();

useMaterio({
    htmlClass: 'layout-menu-fixed layout-compact',
    initMenu: true,
    collapseMenuOnDesktop: false,
});
</script>

<template>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <AdminSidebar :can="can" />

            <div class="layout-page">
                <AdminNavbar :user="user" />

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div
                            v-if="$slots.header"
                            class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"
                        >
                            <slot name="header" />
                        </div>

                        <slot />
                    </div>

                    <AdminFooter />

                    <div class="content-backdrop fade" />
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle" />
    </div>
</template>
