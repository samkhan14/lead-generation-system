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
        class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:px-6"
    >
        <div class="text-sm text-slate-500">
            <template v-if="paginator.total">
                Showing
                <span class="font-medium text-slate-900">{{ paginator.from ?? 0 }}</span>
                to
                <span class="font-medium text-slate-900">{{ paginator.to ?? 0 }}</span>
                of
                <span class="font-medium text-slate-900">{{ paginator.total }}</span>
                {{ itemLabel }}
            </template>
            <template v-else>
                No {{ itemLabel }}
            </template>
            <span v-if="paginator.last_page > 1" class="ml-2 text-slate-400">
                · Page {{ paginator.current_page }} of {{ paginator.last_page }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label v-if="perPage && routeName" class="flex items-center gap-2 text-sm text-slate-600">
                <span class="whitespace-nowrap">Rows per page</span>
                <select
                    :value="perPage"
                    class="rounded-md border-slate-300 py-1.5 pl-2 pr-8 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="onPerPageChange"
                >
                    <option v-for="option in perPageOptions" :key="option" :value="option">
                        {{ option }}
                    </option>
                </select>
            </label>

            <div v-if="hasPages" class="flex flex-wrap gap-1">
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="link in paginator.links"
                    :key="`${link.label}-${link.url}`"
                    :href="link.url"
                    class="rounded-md px-3 py-1.5 text-sm"
                    :class="link.active
                        ? 'bg-blue-600 text-white'
                        : link.url
                            ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'
                            : 'cursor-not-allowed bg-slate-50 text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </div>
</template>
