<script setup>
import { useI18n } from '@/lib/i18n';
const { t } = useI18n();
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nursery_name: '',
    timezone: 'Africa/Cairo',
    currency: 'EGP',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title='t("Register")' />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="nursery_name" :value='t("Nursery name")' />

                <TextInput
                    id="nursery_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nursery_name"
                    required
                />

                <InputError class="mt-2" :message="form.errors.nursery_name" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="timezone" :value='t("Timezone")' />
                    <select
                        id="timezone"
                        v-model="form.timezone"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="Africa/Cairo">{{ t("Cairo") }}</option>
                        <option value="Asia/Riyadh">{{ t("Riyadh") }}</option>
                        <option value="Asia/Dubai">{{ t("Dubai") }}</option>
                        <option value="Asia/Kuwait">{{ t("Kuwait") }}</option>
                        <option value="Asia/Qatar">{{ t("Doha") }}</option>
                        <option value="Asia/Bahrain">{{ t("Bahrain") }}</option>
                        <option value="Asia/Muscat">{{ t("Muscat") }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.timezone" />
                </div>
                <div>
                    <InputLabel for="currency" :value='t("Currency")' />
                    <select
                        id="currency"
                        v-model="form.currency"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="EGP">EGP</option>
                        <option value="SAR">SAR</option>
                        <option value="AED">AED</option>
                        <option value="KWD">KWD</option>
                        <option value="QAR">QAR</option>
                        <option value="BHD">BHD</option>
                        <option value="OMR">OMR</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.currency" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="name" :value='t("Your name")' />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" :value='t("Email")' />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value='t("Password")' />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    :value='t("Confirm Password")'
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >{{ t("Already registered?") }}</Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >{{ t("Register") }}</PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
