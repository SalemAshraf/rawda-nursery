<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, nextTick, ref } from "vue";
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
const props = defineProps({
    rows: Object,
    filters: Object,
    counts: Object,
    classrooms: Array,
    today: String,
});
const showForm = ref(false),
    editing = ref(null),
    enrolling = ref(null),
    q = ref(props.filters.search || "");
const states = ["inquiry", "tour", "waitlist", "enrolled", "closed"];
const form = useForm({
    first_name: "",
    last_name: "",
    dob: "",
    guardian_name: "",
    guardian_phone: "",
    guardian_relation: "",
    classroom_id: "",
    follow_up_on: "",
    notes: "",
    status: "inquiry",
});
const enrollment = useForm({ classroom_id: "", confirmed: false });
const selectedRoom = computed(() =>
    props.classrooms.find((r) => r.id == enrollment.classroom_id),
);
async function open(a = null) {
    editing.value = a?.id || null;
    form.reset();
    form.clearErrors();
    if (a) Object.keys(form.data()).forEach((k) => (form[k] = a[k] ?? ""));
    showForm.value = true;
    await nextTick();
    document
        .querySelector("#admission-form")
        .scrollIntoView({ behavior: "smooth", block: "start" });
}
function save() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    };
    editing.value
        ? form.put(route("admissions.update", editing.value), opts)
        : form.post(route("admissions.store"), opts);
}
function filter(status = props.filters.status || "") {
    router.get(
        route("admissions.index"),
        { search: q.value, status },
        { preserveState: true },
    );
}
async function prepareEnroll(a) {
    enrolling.value = a;
    enrollment.reset();
    enrollment.clearErrors();
    enrollment.classroom_id = a.classroom_id || "";
    await nextTick();
    document
        .querySelector("#enrollment-form")
        .scrollIntoView({ behavior: "smooth", block: "start" });
}
function enroll() {
    enrollment.post(route("admissions.enroll", enrolling.value.id));
}
const due = (a) =>
    a.follow_up_on &&
    a.follow_up_on <= props.today &&
    !["enrolled", "closed"].includes(a.status);
</script>
<template>
    <Head :title="t('Admissions')" /><AuthenticatedLayout
        ><div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ t("Administration") }}</div>
                    <h1>{{ t("Admissions") }}</h1>
                    <p>
                        {{ t("From first inquiry to the first day of care.") }}
                    </p>
                </div>
                <button class="action-primary" @click="open()">
                    + {{ t("New inquiry") }}
                </button>
            </div>
            <div class="admission-stages">
                <button
                    v-for="s in states"
                    :key="s"
                    class="kpi-card text-start"
                    :class="{ '!border-green-500': filters.status === s }"
                    :aria-pressed="filters.status === s"
                    @click="filter(filters.status === s ? '' : s)"
                >
                    <span class="kpi-label">{{ t(s) }}</span
                    ><b class="kpi-value">{{ counts[s] || 0 }}</b>
                </button>
            </div>
            <form
                v-if="showForm"
                id="admission-form"
                class="panel p-5 mb-5 scroll-mt-4"
                @submit.prevent="save"
            >
                <div class="flex items-center justify-between gap-3 mb-5">
                    <h2 class="font-semibold">
                        {{ t(editing ? "Edit inquiry" : "New inquiry") }}
                    </h2>
                    <button
                        type="button"
                        class="text-link"
                        @click="showForm = false"
                    >
                        {{ t("Cancel") }}
                    </button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <label
                        v-for="f in [
                            { k: 'first_name', l: 'First name' },
                            { k: 'last_name', l: 'Last name' },
                            { k: 'guardian_name', l: 'Guardian name' },
                            { k: 'guardian_phone', l: 'Phone' },
                            { k: 'guardian_relation', l: 'Relation' },
                        ]"
                        :key="f.k"
                        class="text-xs text-gray-500"
                        >{{ t(f.l)
                        }}<input
                            v-model="form[f.k]"
                            required
                            :maxlength="f.k === 'guardian_phone' ? 40 : 100"
                            class="search-field mt-2"
                            :type="
                                f.k === 'guardian_phone' ? 'tel' : 'text'
                            " /></label
                    ><label class="text-xs text-gray-500"
                        >{{ t("Date of birth")
                        }}<input
                            v-model="form.dob"
                            type="date"
                            :max="today"
                            class="search-field mt-2" /></label
                    ><label class="text-xs text-gray-500"
                        >{{ t("Preferred classroom")
                        }}<select
                            v-model="form.classroom_id"
                            class="search-field mt-2"
                        >
                            <option value="">{{ t("No classroom") }}</option>
                            <option
                                v-for="r in classrooms"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.name }} · {{ r.children_count }} /
                                {{ r.capacity || "—" }}
                            </option>
                        </select></label
                    ><label class="text-xs text-gray-500"
                        >{{ t("Next follow-up or tour")
                        }}<input
                            v-model="form.follow_up_on"
                            type="date"
                            class="search-field mt-2" /></label
                    ><label class="text-xs text-gray-500"
                        >{{ t("Stage")
                        }}<select
                            v-model="form.status"
                            class="search-field mt-2"
                        >
                            <option
                                v-for="s in states.filter(
                                    (s) => s !== 'enrolled',
                                )"
                                :key="s"
                                :value="s"
                            >
                                {{ t(s) }}
                            </option>
                        </select></label
                    ><label
                        class="text-xs text-gray-500 sm:col-span-2 xl:col-span-3"
                        >{{ t("Inquiry notes")
                        }}<textarea
                            v-model="form.notes"
                            maxlength="2000"
                            rows="2"
                            class="search-field mt-2"
                        ></textarea>
                    </label>
                </div>
                <p
                    v-for="e in form.errors"
                    :key="e"
                    class="text-xs text-rose-700 mt-2"
                    role="alert"
                >
                    {{ e }}
                </p>
                <button class="action-primary mt-4" :disabled="form.processing">
                    {{ t("Save inquiry") }}
                </button>
            </form>
            <form
                v-if="enrolling"
                id="enrollment-form"
                class="panel p-5 mb-5 !border-green-300 scroll-mt-4"
                @submit.prevent="enroll"
            >
                <h2 class="font-semibold mb-3">
                    {{ t("Enroll child") }}: {{ enrolling.first_name }}
                    {{ enrolling.last_name }}
                </h2>
                <p class="text-xs text-gray-500 mb-4 leading-6">
                    {{
                        t(
                            "Enrollment creates a child profile and links the recorded guardian. It does not charge fees, send messages or authorize pickup.",
                        )
                    }}
                </p>
                <div class="flex flex-wrap gap-3 items-end">
                    <label class="text-xs text-gray-500"
                        >{{ t("Classroom")
                        }}<select
                            v-model="enrollment.classroom_id"
                            required
                            class="search-field mt-2"
                        >
                            <option value="" disabled>
                                {{ t("Select classroom") }}
                            </option>
                            <option
                                v-for="r in classrooms"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.name }} · {{ r.children_count }} /
                                {{ r.capacity || "—" }}
                            </option>
                        </select></label
                    >
                    <p
                        v-if="
                            selectedRoom?.capacity &&
                            selectedRoom.children_count >= selectedRoom.capacity
                        "
                        class="text-xs text-amber-700"
                    >
                        {{ t("Room is at capacity. Review before enrolling.") }}
                    </p>
                </div>
                <label
                    class="flex items-start gap-2 mt-4 text-xs text-gray-600 leading-6"
                    ><input
                        v-model="enrollment.confirmed"
                        type="checkbox"
                        required
                        class="rounded border-gray-300 mt-1"
                    />{{
                        t(
                            "I checked the child directory for an existing record and reviewed the classroom and guardian details.",
                        )
                    }}</label
                >
                <p
                    v-for="e in enrollment.errors"
                    :key="e"
                    role="alert"
                    class="mt-2 text-xs text-rose-700"
                >
                    {{ e }}
                </p>
                <div class="flex gap-2 mt-4">
                    <button
                        class="action-primary disabled:opacity-50"
                        :disabled="
                            !enrollment.confirmed || enrollment.processing
                        "
                    >
                        {{ t("Confirm enrollment") }}</button
                    ><button
                        type="button"
                        class="action-secondary"
                        @click="enrolling = null"
                    >
                        {{ t("Cancel") }}</button
                    ><Link
                        :href="route('children.index')"
                        class="text-link self-center"
                        >{{ t("View children") }}</Link
                    >
                </div>
            </form>
            <form
                class="panel p-4 flex flex-wrap gap-3 mb-5"
                @submit.prevent="filter()"
            >
                <input
                    v-model="q"
                    type="search"
                    :placeholder="t('Search child, guardian or phone')"
                    :aria-label="t('Search')"
                    class="search-field !w-auto flex-1"
                /><button class="action-secondary">{{ t("Search") }}</button
                ><button
                    v-if="filters.status"
                    type="button"
                    class="text-link"
                    @click="filter('')"
                >
                    {{ t("All") }}
                </button>
            </form>
            <p class="text-xs text-gray-500 mb-4">
                {{
                    t(
                        "Follow-ups are staff records only. No automated messages are sent.",
                    )
                }}
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                <article v-for="a in rows.data" :key="a.id" class="panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">
                                {{ a.first_name }} {{ a.last_name }}
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ a.classroom || t("No classroom") }}
                            </p>
                        </div>
                        <span
                            class="status-pill"
                            :class="
                                a.status === 'enrolled'
                                    ? 'status-on_site'
                                    : a.status === 'closed'
                                      ? 'status-collected'
                                      : 'status-not_arrived'
                            "
                            >{{ t(a.status) }}</span
                        >
                    </div>
                    <div class="mt-5 rounded-lg bg-gray-50 p-3 text-xs">
                        <p class="font-medium">
                            {{ a.guardian_name }}
                            <span class="font-normal text-gray-400"
                                >({{ a.guardian_relation }})</span
                            >
                        </p>
                        <p class="mt-2 text-gray-500" dir="ltr">
                            {{ a.guardian_phone }}
                        </p>
                    </div>
                    <p
                        v-if="
                            a.follow_up_on &&
                            !['enrolled', 'closed'].includes(a.status)
                        "
                        class="mt-3 text-xs"
                        :class="due(a) ? 'text-amber-700' : 'text-gray-500'"
                    >
                        {{ t("Next follow-up or tour") }}:
                        <b dir="ltr">{{ a.follow_up_on }}</b><span
                            v-if="due(a)"
                        >
                            · {{ t("Follow-up due") }}</span
                        >
                    </p>
                    <p
                        v-if="a.notes"
                        class="mt-3 text-xs text-gray-500 leading-6 whitespace-pre-wrap break-words"
                    >
                        {{ a.notes }}
                    </p>
                    <div class="flex flex-wrap gap-2 mt-5">
                        <Link
                            v-if="a.child_id"
                            :href="route('children.show', a.child_id)"
                            class="action-primary"
                            >{{ t("Open child profile") }}</Link
                        ><template v-else
                            ><button class="action-secondary" @click="open(a)">
                                {{ t("Edit inquiry") }}</button
                            ><button
                                v-if="a.status !== 'closed'"
                                class="action-primary"
                                @click="prepareEnroll(a)"
                            >
                                {{ t("Enroll child") }}
                            </button></template
                        >
                    </div>
                </article>
            </div>
            <p v-if="!rows.data.length" class="panel empty-state">
                {{ t("No inquiries match this view") }}
            </p>
            <footer
                class="flex items-center justify-between gap-3 mt-5 text-xs text-gray-500"
            >
                <Link
                    v-if="rows.prev_page_url"
                    :href="rows.prev_page_url"
                    class="action-secondary"
                    >{{ t("Previous") }}</Link
                ><span
                    >{{ rows.current_page }} / {{ rows.last_page }} ·
                    {{ rows.total }}</span
                ><Link
                    v-if="rows.next_page_url"
                    :href="rows.next_page_url"
                    class="action-secondary"
                    >{{ t("Next") }}</Link
                >
            </footer>
        </div></AuthenticatedLayout
    >
</template>
