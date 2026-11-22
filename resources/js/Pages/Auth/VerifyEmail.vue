<script setup>
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
import { computed } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent",
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('Email Verification')" />

        <div class="mb-4 text-sm text-gray-600">
            {{
                t(
                    "Verify your email using the link you receive. If no message arrives, contact the platform team.",
                )
            }}
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            {{
                t(
                    "Request received. Check your inbox, or contact the platform team if no message arrives.",
                )
            }}
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    >{{ t("Resend Verification Email") }}</PrimaryButton
                >

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="verification-logout rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >{{ t("Log Out") }}</Link
                >
            </div>
        </form>
        <a href="/#contact" class="mt-4 block text-sm text-gray-600 underline">{{ t('Contact the platform team') }}</a>
    </GuestLayout>
</template>
<style scoped>
.verification-logout { background: transparent; color: #536258; padding: 8px 10px; border: 1px solid #dfe8e0; }
</style>
