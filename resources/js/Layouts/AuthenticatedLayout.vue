<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import Icon from "@/Components/Icon.vue";
import { useI18n } from "@/lib/i18n";
const { t, locale, toggleLocale } = useI18n();
const page = usePage();
const open = ref(false);
const user = computed(() => page.props.auth.user);
const nav = computed(() => [
    { label: "Overview", route: "dashboard", icon: "overview" },
    ...(user.value.role !== "teacher"
        ? [{ label: "Admissions", route: "admissions.index", icon: "children" }]
        : []),
    { label: "Classrooms", route: "classrooms.index", icon: "overview" },
    { label: "Children", route: "children.index", icon: "children" },
    { label: "Attendance", route: "attendance.index", icon: "attendance" },
    { label: "Daily log", route: "logs.index", icon: "logs" },
    { label: "Incidents", route: "incidents.index", icon: "incidents" },
    ...(user.value.role !== "teacher"
        ? [{ label: "Billing", route: "billing.index", icon: "billing" }]
        : []),
    ...(user.value.role !== "teacher"
        ? [{ label: "Reports", route: "reports", icon: "overview" }]
        : []),
    ...(user.value.role !== "teacher"
        ? [{ label: "Activity history", route: "audit.index", icon: "logs" }]
        : []),
    ...(user.value.role === "owner"
        ? [{ label: "Staff", route: "staff.index", icon: "staff" }]
        : []),
]);
const active = (n) =>
    route().current(
        n.route.includes(".") ? n.route.split(".")[0] + ".*" : n.route,
    );
</script>
<template>
    <div class="workspace">
        <button
            v-if="open"
            class="nav-overlay"
            :aria-label="t('Close menu')"
            @click="open = false"
        ></button>
        <aside class="sidebar" :class="{ 'is-open': open }">
            <Link :href="route('dashboard')" class="brand"
                ><span class="brand-mark">✳</span
                ><span
                    >{{ t("Baraim")
                    }}<small>{{ t("Nursery workspace") }}</small></span
                ></Link
            >
            <div class="nursery-label">
                <span class="nursery-avatar">{{
                    (page.props.auth.nursery?.name || "B")[0]
                }}</span
                ><span
                    >{{ page.props.auth.nursery?.name
                    }}<small>{{ t(user.role) }}</small></span
                >
            </div>
            <p class="nav-heading">{{ t("Daily operations") }}</p>
            <nav>
                <Link
                    v-for="n in nav"
                    :key="n.route"
                    :href="route(n.route)"
                    class="side-link"
                    :class="{ active: active(n) }"
                    :aria-current="active(n) ? 'page' : undefined"
                    @click="open = false"
                    ><Icon :name="n.icon" />{{ t(n.label) }}</Link
                >
            </nav>
            <div class="sidebar-bottom">
                <div class="safety-note">
                    <Icon name="attendance" />
                    <p>
                        {{
                            t(
                                "Pickup authorization records are not identity verification. Staff must check identity at the door.",
                            )
                        }}
                    </p>
                </div>
                <Link :href="route('profile.edit')" class="user-card"
                    ><span class="user-avatar">{{ user.name[0] }}</span
                    ><span
                        >{{ user.name }}<small>{{ t("Profile") }}</small></span
                    ></Link
                >
            </div>
        </aside>
        <div class="workspace-body">
            <header class="topbar">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        class="mobile-menu"
                        :aria-label="t('Menu')"
                        :aria-expanded="open"
                        @click="open = !open"
                    >
                        <Icon name="menu" /></button
                    ><span class="topbar-title">{{
                        t("Nursery workspace")
                    }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button class="utility-button" @click="toggleLocale">
                        {{ locale === "ar" ? "English" : "العربية" }}</button
                    ><Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="utility-button"
                        >{{ t("Log out") }}</Link
                    >
                </div>
            </header>
            <main>
                <p
                    v-if="page.props.flash?.success"
                    role="status"
                    class="mx-5 mt-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
                >
                    {{ t(page.props.flash.success) }}
                </p>
                <header v-if="$slots.header" class="page-heading">
                    <slot name="header" />
                </header>
                <slot />
            </main>
        </div>
    </div>
</template>
