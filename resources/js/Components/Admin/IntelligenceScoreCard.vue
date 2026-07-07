<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    score: {
        type: Number,
        required: true,
    },
    signals: {
        type: Array,
        default: () => [],
    },
    accent: {
        type: String,
        default: 'indigo',
    },
    delay: {
        type: Number,
        default: 0,
    },
});

const accentClasses = {
    indigo: { badge: 'bg-label-primary', bar: 'bg-primary' },
    emerald: { badge: 'bg-label-success', bar: 'bg-success' },
    sky: { badge: 'bg-label-info', bar: 'bg-info' },
};
</script>

<template>
    <div
        class="card h-100 animate-scale-in"
        :style="{ animationDelay: `${delay}ms` }"
    >
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0">{{ title }}</h6>
                <span
                    class="badge rounded-pill"
                    :class="accentClasses[accent]?.badge ?? accentClasses.indigo.badge"
                >
                    {{ score }}
                </span>
            </div>

            <div class="progress progress-sm mt-3">
                <div
                    class="progress-bar"
                    :class="accentClasses[accent]?.bar ?? accentClasses.indigo.bar"
                    role="progressbar"
                    :style="{ width: `${Math.min(score, 100)}%` }"
                    :aria-valuenow="Math.min(score, 100)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                />
            </div>

            <ul v-if="signals.length" class="list-unstyled mb-0 mt-3 small text-body-secondary" style="max-height: 9rem; overflow-y: auto;">
                <li
                    v-for="(signal, index) in signals"
                    :key="signal"
                    class="animate-fade-in mb-1"
                    :style="{ animationDelay: `${delay + 80 + index * 40}ms` }"
                >
                    • {{ signal }}
                </li>
            </ul>
            <p v-else class="mb-0 mt-3 small text-muted">No signals detected.</p>
        </div>
    </div>
</template>
