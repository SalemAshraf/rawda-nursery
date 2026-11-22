<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import PlatformLayout from "@/Layouts/PlatformLayout.vue";
import { useI18n } from "@/lib/i18n";
const { locale } = useI18n();
const L = (ar, en) => (locale.value === "ar" ? ar : en);
const form = useForm({ email: "", password: "" });
</script>
<template>
    <Head :title="L('دخول إدارة المنصة', 'Platform login')" /><PlatformLayout
        ><section class="platform-login saas-card">
            <p class="eyebrow">
                {{
                    L(
                        "حساب مستقل عن حسابات الحضانات",
                        "Separate from nursery accounts",
                    )
                }}
            </p>
            <h1>
                {{ L("إدارة منصة Nursery", "Nursery platform administration") }}
            </h1>
            <p>
                {{
                    L(
                        "لإدارة الباقات وطلبات البيع والاشتراكات فقط.",
                        "Manage plans, sales inquiries and subscriptions.",
                    )
                }}
            </p>
            <form
                @submit.prevent="
                    form.post(route('platform.login.store'), {
                        onFinish: () => form.reset('password'),
                    })
                "
                class="saas-form"
            >
                <label
                    >{{ L("البريد الإلكتروني", "Email")
                    }}<input
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        dir="ltr" /></label
                ><label
                    >{{ L("كلمة المرور", "Password")
                    }}<input
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                /></label>
                <p v-for="e in form.errors" class="text-red-700" role="alert">
                    {{ e }}
                </p>
                <button class="saas-primary" :disabled="form.processing">
                    {{ L("دخول إدارة المنصة", "Sign in to platform") }}
                </button>
            </form>
        </section></PlatformLayout
    >
</template>
