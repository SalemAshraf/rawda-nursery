<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Icon from "@/Components/Icon.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    today: String,
    stats: Object,
    classrooms: Array,
    register: Array,
    pendingIncidents: Array,
    canManage: Boolean,
});
const { t, locale } = useI18n();
const q = ref("");
const status = ref("all");
const filtered = computed(() =>
    props.register.filter(
        (c) =>
            (!q.value ||
                c.name.toLowerCase().includes(q.value.toLowerCase())) &&
            (status.value === "all" || c.status === status.value),
    ),
);
const dayLabel = computed(() =>
    new Intl.DateTimeFormat(locale.value === "ar" ? "ar-EG" : "en-GB", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
        timeZone: "UTC",
    }).format(new Date(props.today + "T12:00:00Z")),
);
const cards = computed(() => [
    {
        key: "on_site",
        label: "On site now",
        icon: "children",
        href: "attendance.index",
        hint: "Enrolled children",
        sub: props.stats.active_children,
    },
    {
        key: "not_arrived",
        label: "Not arrived",
        icon: "sun",
        href: "attendance.index",
        hint: "registered",
        sub: props.stats.present_today,
    },
    {
        key: "checked_out_today",
        label: "Collected",
        icon: "attendance",
        href: "attendance.index",
        hint: "Today at a glance",
    },
    {
        key: "missing_logs",
        label: "Missing daily logs",
        icon: "logs",
        href: "logs.index",
        hint: "Daily care",
    },
]);
</script>
<template>
    <Head :title="t('Overview')" />
    <AuthenticatedLayout>
        <div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ dayLabel }}</div>
                    <h1>{{ t("Today at a glance") }}</h1>
                    <p>{{ t("A calmer day. Every child accounted for.") }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="canManage"
                        :href="route('children.index')"
                        class="action-secondary"
                        >+ {{ t("Add a child") }}</Link
                    ><Link
                        :href="route('attendance.index')"
                        class="action-primary"
                        ><Icon name="attendance" />{{
                            t("Open attendance")
                        }}</Link
                    >
                </div>
            </div>
            <div class="kpi-grid">
                <Link
                    v-for="c in cards"
                    :key="c.key"
                    :href="route(c.href)"
                    class="kpi-card"
                    ><span class="kpi-label">{{ t(c.label) }}</span
                    ><span class="kpi-icon"><Icon :name="c.icon" /></span
                    ><strong class="kpi-value">{{ stats[c.key] }}</strong
                    ><small
                        >{{ c.sub !== undefined ? c.sub + " " : ""
                        }}{{ t(c.hint) }}</small
                    ></Link
                >
            </div>
            <div v-if="!stats.active_children" class="panel empty-state mb-5">
                <h3>{{ t("Start with your first child") }}</h3>
                <p>
                    {{
                        t(
                            "Add classrooms and child profiles, then manage attendance and care from here.",
                        )
                    }}
                </p>
                <Link
                    v-if="canManage"
                    :href="route('children.index')"
                    class="action-primary"
                    >{{ t("Add a child") }}</Link
                >
            </div>
            <div class="ops-grid">
                <div class="space-y-5 min-w-0">
                    <section class="panel">
                        <div class="panel-header">
                            <h2>{{ t("Classroom pulse") }}</h2>
                            <Link :href="route('children.index')"
                                >{{ t("View children") }} ↗</Link
                            >
                        </div>
                        <div class="panel-body">
                            <div v-if="classrooms.length" class="room-grid">
                                <div
                                    v-for="room in classrooms"
                                    :key="room.id"
                                    class="room-card"
                                >
                                    <h3>{{ room.name }}</h3>
                                    <div>
                                        <strong>{{ room.on_site }}</strong
                                        ><small>
                                            /
                                            {{ room.capacity || room.enrolled }}
                                            {{
                                                t(
                                                    room.capacity
                                                        ? "capacity"
                                                        : "enrolled",
                                                )
                                            }}</small
                                        >
                                    </div>
                                    <div class="progress-track">
                                        <div
                                            class="progress-fill"
                                            :style="{
                                                width:
                                                    Math.min(
                                                        100,
                                                        room.capacity ||
                                                            room.enrolled
                                                            ? (room.on_site /
                                                                  (room.capacity ||
                                                                      room.enrolled)) *
                                                                  100
                                                            : 0,
                                                    ) + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <small
                                        >{{ room.enrolled }}
                                        {{ t("enrolled") }} · {{ room.on_site }}
                                        {{ t("on site")
                                        }}<span
                                            v-if="
                                                room.capacity &&
                                                room.enrolled > room.capacity
                                            "
                                            class="text-amber-700"
                                        >
                                            · {{ t("Over capacity") }}</span
                                        ></small
                                    >
                                </div>
                            </div>
                            <p v-else class="empty-state">
                                {{ t("No classrooms yet") }}
                                <Link
                                    v-if="canManage"
                                    :href="route('children.index')"
                                    class="text-link"
                                    >{{ t("Create classroom") }}</Link
                                >
                            </p>
                        </div>
                    </section>
                    <section class="panel">
                        <div class="panel-header">
                            <h2>
                                {{ t("Today’s register") }}
                                <span class="text-xs font-normal text-gray-400"
                                    >({{ register.length }})</span
                                >
                            </h2>
                            <Link :href="route('attendance.index')"
                                >{{ t("Open attendance") }} ↗</Link
                            >
                        </div>
                        <div class="panel-body !pb-4">
                            <label class="sr-only" for="register-search">{{
                                t("Search children")
                            }}</label
                            ><input
                                id="register-search"
                                v-model="q"
                                type="search"
                                :placeholder="t('Search children')"
                                class="search-field"
                            />
                            <div class="filter-pills">
                                <button
                                    v-for="s in [
                                        { k: 'all', v: 'All children' },
                                        { k: 'on_site', v: 'On site' },
                                        { k: 'not_arrived', v: 'Not arrived' },
                                        { k: 'collected', v: 'Collected' },
                                    ]"
                                    :key="s.k"
                                    :class="{ selected: status === s.k }"
                                    :aria-pressed="status === s.k"
                                    @click="status = s.k"
                                >
                                    {{ t(s.v) }}
                                </button>
                            </div>
                        </div>
                        <div class="dashboard-register-cards">
                            <article v-for="c in filtered" :key="c.id">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <Link
                                        :href="route('children.show', c.id)"
                                        class="child-cell"
                                        ><span class="child-avatar">{{
                                            c.name[0]
                                        }}</span
                                        >{{ c.name }}</Link
                                    ><span
                                        class="status-pill"
                                        :class="'status-' + c.status"
                                        >{{
                                            t(
                                                c.status === "on_site"
                                                    ? "On site"
                                                    : c.status === "collected"
                                                      ? "Collected"
                                                      : "Not arrived",
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500"
                                >
                                    <span>{{
                                        c.classroom || t("No classroom")
                                    }}</span
                                    ><span
                                        v-if="c.has_allergies"
                                        class="text-amber-700"
                                        >● {{ t("Allergy alert") }}</span
                                    ><span
                                        v-if="!c.has_pickup"
                                        class="text-rose-700"
                                        >{{ t("No pickup list") }}</span
                                    ><span
                                        v-if="c.has_log"
                                        class="text-green-700"
                                        >{{ t("Daily log saved") }}</span
                                    ><span
                                        v-else-if="c.status !== 'not_arrived'"
                                        >{{ t("Log needed") }}</span
                                    >
                                </div>
                            </article>
                            <p v-if="!filtered.length" class="empty-state">
                                {{ t("No matching children") }}
                            </p>
                        </div>
                        <div class="table-scroll dashboard-register-table">
                            <table class="ops-table">
                                <thead>
                                    <tr>
                                        <th>{{ t("Child") }}</th>
                                        <th>{{ t("Classroom") }}</th>
                                        <th>{{ t("Status") }}</th>
                                        <th>{{ t("Care notes") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in filtered" :key="c.id">
                                        <td>
                                            <Link
                                                :href="
                                                    route('children.show', c.id)
                                                "
                                                class="child-cell"
                                                ><span class="child-avatar">{{
                                                    c.name
                                                        .split(" ")
                                                        .map((n) => n[0])
                                                        .slice(0, 2)
                                                        .join("")
                                                }}</span
                                                >{{ c.name }}</Link
                                            >
                                        </td>
                                        <td class="text-gray-400">
                                            {{
                                                c.classroom || t("No classroom")
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                class="status-pill"
                                                :class="'status-' + c.status"
                                                >{{
                                                    t(
                                                        c.status === "on_site"
                                                            ? "On site"
                                                            : c.status ===
                                                                "collected"
                                                              ? "Collected"
                                                              : "Not arrived",
                                                    )
                                                }}</span
                                            >
                                        </td>
                                        <td>
                                            <span
                                                v-if="c.has_allergies"
                                                class="block text-[10px] text-amber-700"
                                                >●
                                                {{ t("Allergy alert") }}</span
                                            ><span
                                                v-if="!c.has_pickup"
                                                class="block text-[10px] text-rose-600"
                                                >{{ t("No pickup list") }}</span
                                            ><span
                                                v-if="c.has_log"
                                                class="text-[10px] text-emerald-600"
                                                >{{
                                                    t("Daily log saved")
                                                }}</span
                                            ><span
                                                v-else-if="
                                                    c.status !== 'not_arrived'
                                                "
                                                class="text-[10px] text-gray-400"
                                                >{{ t("Log needed") }}</span
                                            >
                                        </td>
                                    </tr>
                                    <tr v-if="!filtered.length">
                                        <td colspan="4" class="empty-state">
                                            {{ t("No matching children") }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
                <div class="space-y-5">
                    <section class="panel">
                        <div class="panel-header">
                            <h2>{{ t("Needs attention") }}</h2>
                            <Icon name="incidents" class="text-amber-500" />
                        </div>
                        <div class="panel-body">
                            <Link
                                :href="route('logs.index')"
                                class="attention-row"
                                ><b class="attention-count">{{
                                    stats.missing_logs
                                }}</b
                                >{{ t("Missing daily logs")
                                }}<span>↗</span></Link
                            ><Link
                                :href="route('children.index')"
                                class="attention-row"
                                ><b class="attention-count">{{
                                    stats.missing_pickup
                                }}</b
                                >{{ t("No authorized pickup")
                                }}<span>↗</span></Link
                            ><Link
                                :href="route('incidents.index')"
                                class="attention-row"
                                ><b class="attention-count">{{
                                    stats.unacknowledged_incidents
                                }}</b
                                >{{ t("Pending acknowledgement")
                                }}<span>↗</span></Link
                            >
                        </div>
                    </section>
                    <section class="panel">
                        <div class="panel-header">
                            <h2>{{ t("Follow-up queue") }}</h2>
                            <Link :href="route('incidents.index')"
                                >{{ t("Open incidents") }} ↗</Link
                            >
                        </div>
                        <div class="panel-body">
                            <div
                                v-for="i in pendingIncidents"
                                :key="i.id"
                                class="attention-row"
                            >
                                <div class="min-w-0">
                                    <Link
                                        :href="
                                            route('children.show', i.child_id)
                                        "
                                        class="block font-semibold"
                                        >{{ i.name }}</Link
                                    ><small
                                        class="mt-1 block text-[10px] text-gray-400"
                                        >{{ i.occurred_at }}</small
                                    >
                                </div>
                                <span class="status-pill status-not_arrived">{{
                                    t(i.severity)
                                }}</span>
                            </div>
                            <p
                                v-if="!pendingIncidents.length"
                                class="empty-state"
                            >
                                {{ t("No pending incidents") }}
                            </p>
                        </div>
                    </section>
                    <section v-if="canManage" class="panel finance-card">
                        <div class="panel-header !pb-0">
                            <h2>{{ t("Fees outstanding") }}</h2>
                            <Icon name="billing" class="text-green-700" />
                        </div>
                        <div class="panel-body">
                            <strong class="finance-value" dir="ltr">{{
                                stats.outstanding
                            }}</strong>
                            <p class="text-[11px] text-green-800/60 mb-4">
                                {{ stats.overdue_invoices }}
                                {{ t("Overdue invoices") }}
                            </p>
                            <Link
                                :href="route('billing.index')"
                                class="action-secondary !bg-white/70 w-full"
                                >{{ t("Review billing") }} ↗</Link
                            >
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
