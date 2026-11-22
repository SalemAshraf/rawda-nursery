<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Trend from "@/Components/Trend.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
const page = usePage();
const props = defineProps({
    range: Object,
    currency: String,
    daily: Array,
    summary: Object,
    methods: Array,
    rooms: Array,
});
const f = reactive({ ...props.range }),
    more = ref(false);
const apply = () => router.get(route("reports"), f, { preserveState: true });
const rows = computed(() =>
    [...props.daily].reverse().slice(0, more.value ? 366 : 10),
);
const money = (n) =>
    (n / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }) +
    " " +
    props.currency;
</script>
<template>
    <Head :title="t('Reports')" /><AuthenticatedLayout
        ><div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ t("Administration") }}</div>
                    <h1>{{ t("Reports") }}</h1>
                    <p>
                        {{
                            t(
                                "Care activity and manually recorded collections. No tax or accounting certification is implied.",
                            )
                        }}
                    </p>
                </div>
                <form
                    class="flex flex-wrap items-end gap-2"
                    @submit.prevent="apply"
                >
                    <label class="text-xs text-gray-500"
                        >{{ t("From")
                        }}<input
                            v-model="f.from"
                            type="date"
                            class="search-field mt-1"
                            required /></label
                    ><label class="text-xs text-gray-500"
                        >{{ t("To")
                        }}<input
                            v-model="f.to"
                            type="date"
                            class="search-field mt-1"
                            required /></label
                    ><button class="action-primary">{{ t("Apply") }}</button>
                </form>
            </div>
            <p
                v-for="e in page.props.errors"
                :key="e"
                role="alert"
                class="mb-3 text-xs text-rose-700"
            >
                {{ e }}
            </p>
            <div class="kpi-grid">
                <div
                    v-for="c in [
                        { k: 'collected', l: 'Recorded collections' },
                        { k: 'issued', l: 'Issued tuition' },
                        { k: 'arrivals', l: 'Check-in records' },
                        { k: 'care_logs', l: 'Care records saved' },
                    ]"
                    :key="c.k"
                    class="kpi-card"
                >
                    <span class="kpi-label">{{ t(c.l) }}</span
                    ><b
                        class="kpi-value !text-2xl"
                        :dir="
                            ['collected', 'issued'].includes(c.k)
                                ? 'ltr'
                                : undefined
                        "
                        >{{ summary[c.k] }}</b
                    ><small>{{ range.from }} · {{ range.to }}</small>
                </div>
            </div>
            <div class="grid gap-5 xl:grid-cols-2">
                <section class="panel">
                    <div class="panel-header">
                        <h2>{{ t("Daily attendance records") }}</h2>
                    </div>
                    <div class="panel-body">
                        <Trend
                            :values="daily.map((d) => d.arrivals)"
                            :label="t('Daily attendance records')"
                        />
                        <div
                            class="flex justify-between text-[10px] text-gray-400"
                            dir="ltr"
                        >
                            <span>{{ range.from }}</span
                            ><span>{{ range.to }}</span>
                        </div>
                    </div>
                </section>
                <section class="panel">
                    <div class="panel-header">
                        <h2>
                            {{ t("Daily recorded collections") }} ({{
                                currency
                            }})
                        </h2>
                    </div>
                    <div class="panel-body">
                        <Trend
                            :values="daily.map((d) => d.collected_minor / 100)"
                            :label="t('Daily recorded collections')"
                        />
                        <div
                            class="flex justify-between text-[10px] text-gray-400"
                            dir="ltr"
                        >
                            <span>{{ range.from }}</span
                            ><span>{{ range.to }}</span>
                        </div>
                    </div>
                </section>
                <section class="panel">
                    <div class="panel-header">
                        <h2>{{ t("Collections by method") }}</h2>
                    </div>
                    <div class="panel-body">
                        <div
                            v-for="m in methods"
                            :key="m.method"
                            class="attention-row"
                        >
                            <span>{{ t(m.method) }} ({{ m.count }})</span
                            ><strong class="ms-auto" dir="ltr">{{
                                m.amount
                            }}</strong>
                        </div>
                        <p v-if="!methods.length" class="empty-state">
                            {{ t("No payments in this range") }}
                        </p>
                    </div>
                </section>
                <section class="panel finance-card">
                    <div class="panel-header">
                        <h2>{{ t("Current outstanding fees") }}</h2>
                    </div>
                    <div class="panel-body">
                        <strong class="finance-value" dir="ltr">{{
                            summary.outstanding_now
                        }}</strong>
                        <p class="text-xs text-green-800/70 leading-6">
                            {{
                                t(
                                    "All current open invoices, not limited to the selected dates. Draft and void invoices are excluded.",
                                )
                            }}
                        </p>
                    </div>
                </section>
            </div>
            <section class="panel mt-5">
                <div class="panel-header">
                    <h2>{{ t("Daily activity ledger") }}</h2>
                    <button class="text-link" @click="more = !more">
                        {{ t(more ? "Show recent days" : "Show all days") }}
                    </button>
                </div>
                <div class="report-day-cards">
                    <article
                        v-for="d in rows"
                        :key="d.date"
                        class="p-4 border-t border-gray-100"
                    >
                        <time class="text-xs font-semibold" dir="ltr">{{
                            d.date
                        }}</time>
                        <div
                            class="grid grid-cols-2 gap-3 mt-3 text-xs text-gray-500"
                        >
                            <span
                                >{{ t("Check-in records") }}:
                                <b>{{ d.arrivals }}</b></span
                            ><span
                                >{{ t("Collected") }}:
                                <b>{{ d.departures }}</b></span
                            ><span
                                >{{ t("Care records saved") }}:
                                <b>{{ d.care_logs }}</b></span
                            ><span
                                >{{ t("Recorded collections") }}:
                                <b class="block mt-1" dir="ltr">{{
                                    money(d.collected_minor)
                                }}</b></span
                            >
                        </div>
                    </article>
                </div>
                <div class="overflow-x-auto report-day-table">
                    <table class="ops-table">
                        <thead>
                            <tr>
                                <th>{{ t("Date") }}</th>
                                <th>{{ t("Check-in records") }}</th>
                                <th>{{ t("Collected") }}</th>
                                <th>{{ t("Care records saved") }}</th>
                                <th>{{ t("Recorded collections") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in rows" :key="d.date">
                                <td dir="ltr">{{ d.date }}</td>
                                <td>{{ d.arrivals }}</td>
                                <td>{{ d.departures }}</td>
                                <td>{{ d.care_logs }}</td>
                                <td dir="ltr">
                                    {{ money(d.collected_minor) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="panel mt-5">
                <div class="panel-header">
                    <h2>{{ t("Current classroom enrollment") }}</h2>
                </div>
                <p class="px-5 pb-4 text-xs text-gray-500">
                    {{
                        t(
                            "Current active children, not historical enrollment in the selected date range.",
                        )
                    }}
                </p>
                <div class="panel-body grid gap-3 md:grid-cols-3">
                    <div v-for="r in rooms" :key="r.id" class="room-card">
                        <h3>{{ r.name }}</h3>
                        <strong>{{ r.children_count }}</strong
                        ><small>
                            / {{ r.capacity || "—" }} {{ t("capacity") }}</small
                        >
                    </div>
                </div>
            </section>
        </div></AuthenticatedLayout
    >
</template>
