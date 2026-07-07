<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    emptyMessage: {
        type: String,
        default: 'No records found.',
    },
    isEmpty: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="card">
        <div
            v-if="title || $slots.header"
            class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"
        >
            <h5 v-if="title" class="card-title mb-0">
                {{ title }}
            </h5>
            <slot name="header" />
        </div>

        <div v-if="$slots.toolbar" class="card-body border-bottom pb-3 pt-3">
            <slot name="toolbar" />
        </div>

        <div v-if="isEmpty" class="card-body text-center text-muted py-6">
            {{ emptyMessage }}
        </div>

        <template v-else>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <slot name="head" />
                    </thead>
                    <tbody>
                        <slot />
                    </tbody>
                </table>
            </div>

            <slot name="footer" />
        </template>
    </div>
</template>
