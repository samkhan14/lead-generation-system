<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmailHtmlEditor from '@/Components/Admin/EmailHtmlEditor.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    providers: { type: Object, default: () => ({ data: [] }) },
});

const page = usePage();

const form = useForm({
    to_email: page.props.auth?.user?.email ?? '',
    to_name: '',
    subject: '',
    html_body: '<p>Hello,</p><p>Your message here.</p>',
    text_body: '',
    from_name: '',
    from_email: '',
    reply_to: '',
    email_provider_id: '',
    is_test: false,
});

const submit = () => {
    form.post(route('admin.email.sends.store'));
};
</script>

<template>
    <Head title="Compose Email" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900">Send single email</h1>
        </template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="to_email" value="To email" />
                        <TextInput id="to_email" v-model="form.to_email" type="email" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.to_email" />
                    </div>
                    <div>
                        <InputLabel for="to_name" value="To name" />
                        <TextInput id="to_name" v-model="form.to_name" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel for="subject" value="Subject" />
                    <TextInput id="subject" v-model="form.subject" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.subject" />
                </div>
                <div>
                    <InputLabel for="provider" value="Provider" />
                    <select id="provider" v-model="form.email_provider_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Auto</option>
                        <option v-for="p in providers.data" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.is_test" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    Mark as test send
                </label>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <InputLabel value="Email body" />
                <EmailHtmlEditor v-model="form.html_body" class="mt-2" />
                <InputError class="mt-2" :message="form.errors.html_body || form.errors.email" />
            </div>

            <div class="flex justify-end gap-3">
                <SecondaryButton type="button" @click="router.visit(route('admin.email.sends.index'))">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Queue email</PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
