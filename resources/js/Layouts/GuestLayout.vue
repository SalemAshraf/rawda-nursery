<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "@/lib/i18n";
const { t, locale, toggleLocale } = useI18n();
</script>
<template>
    <div class="auth-shell">
        <header
            class="flex items-center justify-between max-w-6xl mx-auto w-full p-6"
        >
            <Link href="/" class="brand"
                ><span class="brand-mark">✳</span
                ><span
                    >{{ t("Nursery")
                    }}<small>{{ t("Nursery workspace") }}</small></span
                ></Link
            ><button class="utility-button" @click="toggleLocale">
                {{ locale === "ar" ? "English" : "العربية" }}
            </button>
        </header>
        <main class="auth-panel">
            <div class="eyebrow">{{ t("Nursery workspace") }}</div>
            <h1 class="text-2xl font-bold text-green-900 mb-2">
                {{
                    t(
                        route().current("register")
                            ? "Create your nursery workspace"
                            : "Welcome back",
                    )
                }}
            </h1>
            <p class="text-sm text-gray-500 mb-7">
                {{ t("Your nursery, thoughtfully connected.") }}
            </p>
            <slot />
        </main>
    </div>
</template>
