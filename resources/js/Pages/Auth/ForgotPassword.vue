<script setup>
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('Forgot Password')" />

        <div class="mb-4 text-sm text-gray-600">
            {{
                t(
                    "Enter your account email to request a password reset link. If no message arrives, contact the platform team.",
                )
            }}
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ t('Request received. Check your inbox, or contact the platform team if no message arrives.') }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="t('Email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="t(form.errors.email)" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    >{{ t("Email Password Reset Link") }}</PrimaryButton
                >
            </div>
        </form>
        <a href="/#contact" class="mt-4 block text-sm text-gray-600 underline">{{ t('Contact the platform team') }}</a>
    </GuestLayout>
</template>
