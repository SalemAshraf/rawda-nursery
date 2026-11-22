<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { useI18n } from "@/lib/i18n";
const { locale, toggleLocale } = useI18n();
const page = usePage();
</script>
<template>
    <div class="platform-shell">
        <header class="platform-top">
            <Link href="/" class="brand"
                ><span class="brand-mark">✳</span
                ><span
                    >{{ locale === "ar" ? "براعم" : "Baraim"
                    }}<small>{{
                        locale === "ar"
                            ? "إدارة منصة البيع"
                            : "Platform administration"
                    }}</small></span
                ></Link
            >
            <div class="flex gap-2">
                <button @click="toggleLocale" class="utility-button">
                    {{ locale === "ar" ? "English" : "العربية" }}</button
                ><Link
                    v-if="page.url != '/platform/login'"
                    :href="route('platform.logout')"
                    as="button"
                    method="post"
                    class="utility-button"
                    >{{ locale === "ar" ? "خروج" : "Log out" }}</Link
                >
            </div>
        </header>
        <main class="platform-main">
            <p
                v-if="page.props.flash?.success"
                role="status"
                class="saas-notice"
            >
                {{ page.props.flash.success }}
            </p>
            <slot />
        </main>
    </div>
</template>
