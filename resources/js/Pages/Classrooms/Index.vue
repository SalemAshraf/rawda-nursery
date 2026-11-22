<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { useI18n } from "@/lib/i18n";
const { t } = useI18n();
const props = defineProps({ classrooms: Array, canManage: Boolean });
const editing = ref(null),
    showForm = ref(false);
const form = useForm({ name: "", capacity: "" });
function edit(room) {
    editing.value = room.id;
    showForm.value = true;
    form.name = room.name;
    form.capacity = room.capacity || "";
    form.clearErrors();
}
function add() {
    editing.value = null;
    showForm.value = true;
    form.reset();
    form.clearErrors();
}
function save() {
    const options = {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    };
    editing.value
        ? form.put(route("classrooms.update", editing.value), options)
        : form.post(route("classrooms.store"), options);
}
</script>
<template>
    <Head :title="t('Classrooms')" /><AuthenticatedLayout
        ><div class="ops-page">
            <div class="ops-heading">
                <div>
                    <div class="eyebrow">{{ t("Daily operations") }}</div>
                    <h1>{{ t("Classrooms") }}</h1>
                    <p>
                        {{ t("Enrolled children") }}:
                        {{
                            classrooms.reduce((s, r) => s + r.children_count, 0)
                        }}
                    </p>
                </div>
                <button v-if="canManage" class="action-primary" @click="add">
                    + {{ t("Create classroom") }}
                </button>
            </div>
            <form
                v-if="showForm"
                class="panel p-5 mb-5 flex flex-wrap items-end gap-4"
                @submit.prevent="save"
            >
                <label class="text-xs text-gray-500 flex-1"
                    >{{ t("Classroom name")
                    }}<input
                        v-model="form.name"
                        required
                        maxlength="100"
                        class="search-field mt-2" /></label
                ><label class="text-xs text-gray-500"
                    >{{ t("Capacity")
                    }}<input
                        v-model="form.capacity"
                        type="number"
                        min="1"
                        max="200"
                        class="search-field mt-2" /></label
                ><button class="action-primary" :disabled="form.processing">
                    {{ t("Save") }}</button
                ><button
                    class="action-secondary"
                    type="button"
                    @click="showForm = false"
                >
                    {{ t("Cancel") }}
                </button>
                <p
                    v-for="e in form.errors"
                    :key="e"
                    class="text-xs text-rose-700 w-full"
                    role="alert"
                >
                    {{ e }}
                </p>
            </form>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="r in classrooms" :key="r.id" class="panel p-6">
                    <div class="flex justify-between gap-3">
                        <h2 class="font-semibold">{{ r.name }}</h2>
                        <button
                            v-if="canManage"
                            class="text-link"
                            @click="edit(r)"
                        >
                            {{ t("Edit") }}
                        </button>
                    </div>
                    <p class="mt-6">
                        <b class="text-3xl text-green-800">{{
                            r.children_count
                        }}</b
                        ><span class="text-xs text-gray-500">
                            / {{ r.capacity || "—" }} {{ t("capacity") }}</span
                        >
                    </p>
                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            :class="{
                                '!bg-amber-500':
                                    r.capacity && r.children_count > r.capacity,
                            }"
                            :style="{
                                width:
                                    (r.capacity
                                        ? Math.min(
                                              100,
                                              (r.children_count / r.capacity) *
                                                  100,
                                          )
                                        : 0) + '%',
                            }"
                        ></div>
                    </div>
                    <p
                        class="text-xs"
                        :class="
                            r.capacity && r.children_count > r.capacity
                                ? 'text-amber-700'
                                : 'text-gray-500'
                        "
                    >
                        {{
                            r.capacity && r.children_count > r.capacity
                                ? t("Over capacity")
                                : t("Enrolled children")
                        }}
                    </p>
                </article>
            </div>
            <div v-if="!classrooms.length" class="panel empty-state">
                {{ t("No classrooms yet") }}
            </div>
        </div></AuthenticatedLayout
    >
</template>
