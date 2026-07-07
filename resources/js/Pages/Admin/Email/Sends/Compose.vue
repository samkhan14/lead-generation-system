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
            <h4 class="mb-0 fw-bold">Send single email</h4>
        </template>

        <form class="mx-auto vstack gap-4" style="max-width: 48rem;" @submit.prevent="submit">
            <div class="card">
                <div class="card-body vstack gap-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <InputLabel for="to_email" value="To email" />
                            <TextInput id="to_email" v-model="form.to_email" type="email" class="mt-1" required />
                            <InputError class="mt-1" :message="form.errors.to_email" />
                        </div>
                        <div class="col-sm-6">
                            <InputLabel for="to_name" value="To name" />
                            <TextInput id="to_name" v-model="form.to_name" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="subject" value="Subject" />
                        <TextInput id="subject" v-model="form.subject" class="mt-1" required />
                        <InputError class="mt-1" :message="form.errors.subject" />
                    </div>
                    <div>
                        <InputLabel for="provider" value="Provider" />
                        <select id="provider" v-model="form.email_provider_id" class="form-select mt-1">
                            <option value="">Auto</option>
                            <option v-for="p in providers.data" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input id="is_test" v-model="form.is_test" type="checkbox" class="form-check-input" />
                        <label class="form-check-label small" for="is_test">Mark as test send</label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <InputLabel value="Email body" />
                    <EmailHtmlEditor v-model="form.html_body" class="mt-2" />
                    <InputError class="mt-2" :message="form.errors.html_body || form.errors.email" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <SecondaryButton type="button" @click="router.visit(route('admin.email.sends.index'))">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Queue email</PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
