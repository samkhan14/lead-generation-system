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
    indigo: {
        badge: 'ring-indigo-100 bg-indigo-50 text-indigo-700',
        bar: 'bg-indigo-500',
    },
    emerald: {
        badge: 'ring-emerald-100 bg-emerald-50 text-emerald-700',
        bar: 'bg-emerald-500',
    },
    sky: {
        badge: 'ring-sky-100 bg-sky-50 text-sky-700',
        bar: 'bg-sky-500',
    },
};
</script>

<template>
    <div
        class="animate-scale-in rounded-xl bg-white p-4 ring-1 ring-slate-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
        :style="{ animationDelay: `${delay}ms` }"
    >
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-slate-900">{{ title }}</h4>
            <span
                class="rounded-full px-2.5 py-0.5 text-sm font-bold ring-1 ring-inset"
                :class="accentClasses[accent]?.badge ?? accentClasses.indigo.badge"
            >
                {{ score }}
            </span>
        </div>

        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div
                class="h-full rounded-full transition-all duration-700 ease-out"
                :class="accentClasses[accent]?.bar ?? accentClasses.indigo.bar"
                :style="{ width: `${Math.min(score, 100)}%` }"
            />
        </div>

        <ul v-if="signals.length" class="mt-3 max-h-36 space-y-1 overflow-y-auto">
            <li
                v-for="(signal, index) in signals"
                :key="signal"
                class="animate-fade-in text-xs leading-relaxed text-slate-500"
                :style="{ animationDelay: `${delay + 80 + index * 40}ms` }"
            >
                • {{ signal }}
            </li>
        </ul>
        <p v-else class="mt-3 text-xs text-slate-400">No signals detected.</p>
    </div>
</template>
