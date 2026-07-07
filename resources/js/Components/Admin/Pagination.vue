<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
    itemLabel: {
        type: String,
        default: 'items',
    },
    perPage: {
        type: Number,
        default: null,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 15, 25, 50],
    },
    routeName: {
        type: String,
        default: null,
    },
    routeParams: {
        type: Object,
        default: () => ({}),
    },
    query: {
        type: Object,
        default: () => ({}),
    },
    only: {
        type: Array,
        default: null,
    },
});

const hasPages = computed(() => (props.paginator.links?.length ?? 0) > 3);

const onPerPageChange = (event) => {
    if (! props.routeName) {
        return;
    }

    const perPage = Number.parseInt(event.target.value, 10);

    router.get(
        route(props.routeName, props.routeParams),
        { ...props.query, per_page: perPage, page: undefined },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: props.only ?? undefined,
        },
    );
};
</script>

<template>
    <div
        v-if="hasPages || perPage"
        class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-top"
    >
        <div class="text-muted small">
            <template v-if="paginator.total">
                Showing
                <span class="fw-medium text-body">{{ paginator.from ?? 0 }}</span>
                to
                <span class="fw-medium text-body">{{ paginator.to ?? 0 }}</span>
                of
                <span class="fw-medium text-body">{{ paginator.total }}</span>
                {{ itemLabel }}
            </template>
            <template v-else>
                No {{ itemLabel }}
            </template>
            <span v-if="paginator.last_page > 1" class="ms-2 text-muted">
                · Page {{ paginator.current_page }} of {{ paginator.last_page }}
            </span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3">
            <label v-if="perPage && routeName" class="d-flex align-items-center gap-2 small text-muted mb-0">
                <span class="text-nowrap">Rows per page</span>
                <select
                    :value="perPage"
                    class="form-select form-select-sm w-auto"
                    @change="onPerPageChange"
                >
                    <option v-for="option in perPageOptions" :key="option" :value="option">
                        {{ option }}
                    </option>
                </select>
            </label>

            <nav v-if="hasPages" aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li
                        v-for="link in paginator.links"
                        :key="`${link.label}-${link.url}`"
                        class="page-item"
                        :class="{
                            active: link.active,
                            disabled: !link.url,
                        }"
                    >
                        <component
                            :is="link.url ? Link : 'span'"
                            :href="link.url"
                            class="page-link"
                            v-html="link.label"
                        />
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>
