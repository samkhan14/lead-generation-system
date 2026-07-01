<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    call: { type: Object, required: true },
});
</script>

<template>
    <Head title="Voice Call Detail" />
    <AdminLayout>
        <template #header>
            <div>
                <Link :href="route('admin.voice.calls.index')" class="text-sm text-slate-500 hover:text-slate-700">← Back to voice calls</Link>
                <h1 class="mt-1 text-xl font-semibold text-slate-900">Call {{ call.uuid?.slice(0, 8) }}…</h1>
                <p class="text-sm text-slate-500 capitalize">{{ call.status?.replace('_', ' ') }} · {{ call.provider?.name }}</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div v-if="call.error_message" class="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">{{ call.error_message }}</div>
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Transcript</h2>
                    <pre class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ call.transcript || 'No transcript yet.' }}</pre>
                </section>
                <section v-if="call.summary" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Summary</h2>
                    <p class="mt-3 text-sm text-slate-600">{{ call.summary }}</p>
                </section>
            </div>
            <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Lead</dt><dd class="font-medium text-slate-900">{{ call.lead?.full_name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Employee</dt><dd class="font-medium text-slate-900">{{ call.employee?.name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">From</dt><dd class="font-medium text-slate-900">{{ call.from_number ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">To</dt><dd class="font-medium text-slate-900">{{ call.to_number ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Duration</dt><dd class="font-medium text-slate-900">{{ call.duration_seconds ? `${call.duration_seconds}s` : '—' }}</dd></div>
                    <div><dt class="text-slate-500">External ID</dt><dd class="font-medium break-all text-slate-900">{{ call.external_call_id ?? '—' }}</dd></div>
                </dl>
            </aside>
        </div>
    </AdminLayout>
</template>
