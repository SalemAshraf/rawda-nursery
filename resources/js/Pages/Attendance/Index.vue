<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Icon from "@/Components/Icon.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, reactive } from "vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({ date: String, isToday: Boolean, children: Array });
const { t } = useI18n();
const q = ref(""),
    room = ref(""),
    status = ref("all");
const checkout = reactive({}),
    busy = ref(null),
    errors = reactive({});
const rooms = computed(() => [
    ...new Set(props.children.map((c) => c.classroom).filter(Boolean)),
]);
const state = (c) =>
    c.check_out_at ? "collected" : c.check_in_at ? "on_site" : "not_arrived";
const rows = computed(() =>
    props.children.filter(
        (c) =>
            (!q.value ||
                c.name.toLowerCase().includes(q.value.toLowerCase())) &&
            (!room.value || c.classroom === room.value) &&
            (status.value === "all" || state(c) === status.value),
    ),
);
const count = (s) => props.children.filter((c) => state(c) === s).length;
function goToDate(e) {
    router.get(
        route("attendance.index"),
        { date: e.target.value },
        { preserveState: false },
    );
}
function submit(c, out = false) {
    if (busy.value) return;
    busy.value = c.id;
    delete errors[c.id];
    router.post(
        route(out ? "attendance.check-out" : "attendance.check-in", c.id),
        out ? { pickup_person_id: checkout[c.id] } : {},
        {
            preserveScroll: true,
            onError: (e) => {
                errors[c.id] = Object.values(e).join(" ");
            },
            onFinish: () => (busy.value = null),
        },
    );
}
</script>
<template>
    <Head :title="t('Attendance')" />
    <AuthenticatedLayout
        ><div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ t("Daily operations") }}</div>
                    <h1>{{ t("Attendance") }}</h1>
                    <p>
                        {{
                            t(
                                "Verify pickup identity at the door before recording checkout.",
                            )
                        }}
                    </p>
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-500"
                    ><span>{{ t("Date") }}</span
                    ><input
                        type="date"
                        :value="date"
                        class="rounded-lg border-gray-200 text-sm"
                        @change="goToDate"
                /></label>
            </div>
            <div class="kpi-grid !grid-cols-3">
                <div
                    v-for="s in [
                        { k: 'on_site', v: 'On site now', icon: 'children' },
                        { k: 'not_arrived', v: 'Not arrived', icon: 'sun' },
                        { k: 'collected', v: 'Collected', icon: 'attendance' },
                    ]"
                    :key="s.k"
                    class="kpi-card"
                >
                    <span class="kpi-label">{{ t(s.v) }}</span
                    ><span class="kpi-icon"><Icon :name="s.icon" /></span
                    ><b class="kpi-value">{{ count(s.k) }}</b>
                </div>
            </div>
            <p
                v-if="!isToday"
                class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800"
            >
                {{
                    t(
                        "Viewing a past date. Check-in and checkout only apply to today.",
                    )
                }}
            </p>
            <section class="panel">
                <div class="panel-header flex-wrap">
                    <h2>{{ t("Today’s register") }}</h2>
                    <div class="flex flex-wrap gap-2">
                        <input
                            v-model="q"
                            type="search"
                            :placeholder="t('Search children')"
                            :aria-label="t('Search children')"
                            class="search-field !w-auto"
                        /><select
                            v-model="room"
                            class="search-field !w-auto"
                            :aria-label="t('Classroom')"
                        >
                            <option value="">{{ t("All children") }}</option>
                            <option v-for="r in rooms" :key="r">{{ r }}</option>
                        </select>
                    </div>
                    <div class="filter-pills !mt-0 w-full">
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
                <div class="attendance-list">
                    <article
                        v-for="c in rows"
                        :key="c.id"
                        class="attendance-child"
                    >
                        <div class="attendance-identity">
                            <Link
                                :href="route('children.show', c.id)"
                                class="child-cell"
                                ><span class="child-avatar">{{
                                    c.name[0]
                                }}</span
                                ><span
                                    >{{ c.name
                                    }}<small
                                        class="block text-[10px] font-normal text-gray-400 mt-1"
                                        >{{
                                            c.classroom || t("No classroom")
                                        }}</small
                                    ></span
                                ></Link
                            >
                            <p
                                v-if="c.allergies"
                                class="mt-2 text-[11px] text-amber-700"
                            >
                                {{ t("Allergy alert") }}: {{ c.allergies }}
                            </p>
                        </div>
                        <div class="attendance-times">
                            <span
                                class="status-pill"
                                :class="'status-' + state(c)"
                                >{{
                                    t(
                                        state(c) === "on_site"
                                            ? "On site"
                                            : state(c) === "collected"
                                              ? "Collected"
                                              : "Not arrived",
                                    )
                                }}</span
                            >
                            <p class="mt-2 text-[11px] text-gray-400">
                                {{ t("In") }}
                                <b dir="ltr">{{ c.check_in_at || "—" }}</b> ·
                                {{ t("Out") }}
                                <b dir="ltr">{{ c.check_out_at || "—" }}</b>
                            </p>
                            <p
                                v-if="c.picked_up_by"
                                class="mt-1 text-[10px] text-gray-500"
                            >
                                {{ t("Picked up by") }}: {{ c.picked_up_by }}
                            </p>
                        </div>
                        <div class="attendance-actions" v-if="isToday">
                            <button
                                v-if="!c.check_in_at"
                                class="action-primary"
                                :disabled="busy !== null"
                                @click="submit(c)"
                            >
                                {{ t("Check in") }}</button
                            ><template v-else-if="!c.check_out_at"
                                ><label
                                    :for="'pickup-' + c.id"
                                    class="sr-only"
                                    >{{ t("Pickup person") }}</label
                                ><select
                                    :id="'pickup-' + c.id"
                                    v-model="checkout[c.id]"
                                    class="search-field"
                                    :disabled="busy !== null"
                                >
                                    <option :value="undefined" disabled>
                                        {{ t("Pickup person") }}
                                    </option>
                                    <option
                                        v-for="p in c.pickup_persons"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.name }}
                                    </option></select
                                ><button
                                    class="action-primary shrink-0 disabled:opacity-40"
                                    :disabled="!checkout[c.id] || busy !== null"
                                    @click="submit(c, true)"
                                >
                                    {{ t("Check out") }}
                                </button></template
                            ><span v-else class="text-xs text-green-700"
                                >✓ {{ t("Done") }}</span
                            >
                            <p
                                v-if="
                                    c.check_in_at &&
                                    !c.check_out_at &&
                                    !c.pickup_persons.length
                                "
                                class="w-full text-xs text-amber-700"
                            >
                                {{ t("No authorized pickup person on file.") }}
                                <Link
                                    :href="route('children.show', c.id)"
                                    class="underline"
                                    >{{ t("Review child profile") }}</Link
                                >
                            </p>
                            <p
                                v-if="errors[c.id]"
                                role="alert"
                                class="w-full text-xs text-rose-700"
                            >
                                {{ errors[c.id] }}
                            </p>
                        </div>
                    </article>
                    <p v-if="!rows.length" class="empty-state">
                        {{ t("No matching children") }}
                    </p>
                </div>
            </section>
        </div></AuthenticatedLayout
    >
</template>
