<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
const props = defineProps({ date: String, children: Array });
const search = ref(""),
    onlyMissing = ref(false),
    saved = reactive({});
const forms = reactive({});
props.children.forEach(
    (c) =>
        (forms[c.id] = useForm({
            date: props.date,
            meals: c.log?.meals || "",
            naps: c.log?.naps || "",
            mood: c.log?.mood || "",
            activities: c.log?.activities || "",
            notes: c.log?.notes || "",
        })),
);
const rows = computed(() =>
    props.children.filter(
        (c) =>
            (!search.value ||
                c.name.toLowerCase().includes(search.value.toLowerCase())) &&
            (!onlyMissing.value || !c.log),
    ),
);
const completed = computed(() => props.children.filter((c) => c.log).length);
function goToDate(e) {
    router.get(
        route("logs.index"),
        { date: e.target.value },
        { preserveState: false },
    );
}
function save(id) {
    forms[id].post(route("logs.upsert", id), {
        preserveScroll: true,
        onSuccess: () => (saved[id] = true),
    });
}
const fields = [
    { k: "meals", label: "Meals", max: 500 },
    { k: "naps", label: "Naps", max: 500 },
    { k: "mood", label: "Mood", max: 100 },
    { k: "activities", label: "Activities", max: 1000 },
    { k: "notes", label: "Notes", max: 2000 },
];
</script>
<template>
    <Head :title="t('Daily log')" /><AuthenticatedLayout
        ><div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ t("Daily care") }}</div>
                    <h1>{{ t("Daily logs") }}</h1>
                    <p>
                        {{ completed }} / {{ children.length }}
                        {{ t("Daily log saved") }}
                    </p>
                </div>
                <input
                    type="date"
                    :value="date"
                    :aria-label="t('Daily log')"
                    class="rounded-lg border-gray-200 text-sm"
                    @change="goToDate"
                />
            </div>
            <div class="panel p-4 mb-5 flex flex-wrap gap-3 items-center">
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('Search children')"
                    :aria-label="t('Search children')"
                    class="search-field !w-auto flex-1"
                /><label class="flex items-center gap-2 text-xs text-gray-500"
                    ><input
                        v-model="onlyMissing"
                        type="checkbox"
                        class="rounded border-gray-300 text-green-700"
                    />{{ t("Missing daily logs") }}</label
                >
            </div>
            <div class="space-y-4">
                <form
                    v-for="c in rows"
                    :key="c.id"
                    class="panel p-5"
                    @submit.prevent="save(c.id)"
                >
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <Link
                            :href="route('children.show', c.id)"
                            class="child-cell"
                            ><span class="child-avatar">{{ c.name[0] }}</span
                            ><span
                                >{{ c.name
                                }}<small
                                    class="block mt-1 text-[11px] font-normal text-gray-400"
                                    >{{
                                        c.classroom || t("No classroom")
                                    }}</small
                                ></span
                            ></Link
                        ><span
                            v-if="c.log || saved[c.id]"
                            role="status"
                            class="status-pill status-on_site"
                            >✓ {{ t("Saved") }}</span
                        >
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="f in fields"
                            :key="f.k"
                            class="text-xs text-gray-500"
                            :class="{ 'sm:col-span-2': f.k === 'notes' }"
                            >{{ t(f.label)
                            }}<textarea
                                v-model="forms[c.id][f.k]"
                                rows="2"
                                :maxlength="f.max"
                                class="search-field mt-2 !bg-white"
                                @input="saved[c.id] = false"
                            ></textarea>
                        </label>
                    </div>
                    <div class="flex items-center gap-3 mt-4">
                        <button
                            :disabled="forms[c.id].processing"
                            class="action-primary disabled:opacity-50"
                        >
                            {{ t("Save") }}</button
                        ><span
                            v-if="forms[c.id].isDirty"
                            class="text-xs text-amber-700"
                            >{{ t("Unsaved changes") }}</span
                        >
                    </div>
                    <p
                        v-for="e in forms[c.id].errors"
                        :key="e"
                        role="alert"
                        class="mt-2 text-xs text-rose-700"
                    >
                        {{ e }}
                    </p>
                </form>
                <p v-if="!rows.length" class="panel empty-state">
                    {{ t("No matching children") }}
                </p>
            </div>
        </div></AuthenticatedLayout
    >
</template>
