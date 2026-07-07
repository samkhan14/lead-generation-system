<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
});

const initials = computed(() => {
    const name = props.user?.name?.trim() ?? '';

    if (! name) {
        return '?';
    }

    const parts = name.split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    }

    return parts[0].slice(0, 2).toUpperCase();
});
</script>

<template>
    <nav
        id="layout-navbar"
        class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    >
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0);">
                <i class="ri-menu-line ri-22px" />
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">
            <div class="navbar-nav align-items-center flex-grow-1">
                <slot name="page-header" />
            </div>

            <ul class="navbar-nav flex-row align-items-center ms-auto">
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a
                        class="nav-link dropdown-toggle hide-arrow p-0"
                        href="javascript:void(0);"
                        data-bs-toggle="dropdown"
                    >
                        <div class="avatar avatar-online">
                            <span class="avatar-initial rounded-circle bg-label-primary">{{ initials }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="dropdown-item">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-online me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">{{ initials }}</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-medium d-block small">{{ user?.name }}</span>
                                        <small class="text-muted">{{ user?.email }}</small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><div class="dropdown-divider my-1" /></li>
                        <li>
                            <Link :href="route('profile.edit')" class="dropdown-item">
                                <i class="ri-user-3-line ri-22px me-3" />
                                <span>Profile</span>
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="dropdown-item"
                            >
                                <i class="ri-logout-box-r-line ri-22px me-3" />
                                <span>Log Out</span>
                            </Link>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</template>
