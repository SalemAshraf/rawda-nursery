<script setup>
import { useI18n } from '@/lib/i18n';
const { t } = useI18n();
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ children: Array, classrooms: Array, canManage: Boolean });

const search = ref(''), classroomFilter=ref(''), statusFilter=ref('');
const filtered = computed(()=>props.children.filter(c=>(!search.value||c.name.toLowerCase().includes(search.value.toLowerCase()))&&(!classroomFilter.value||c.classroom===classroomFilter.value)&&(!statusFilter.value||c.status===statusFilter.value)));
const showAddChild = ref(false);
const showAddClassroom = ref(false);

const childForm = useForm({
    first_name: '', last_name: '', dob: '', gender: '',
    classroom_id: '', allergies: '', health_notes: '', enrolled_at: '',
});
const classroomForm = useForm({ name: '', capacity: '' });

function addChild() {
    childForm.post(route('children.store'), { onSuccess: () => { showAddChild.value = false; childForm.reset(); } });
}
function addClassroom() {
    classroomForm.post(route('classrooms.store'), { onSuccess: () => { showAddClassroom.value = false; classroomForm.reset(); } });
}
</script>

<template>
    <Head :title='t("Children")' />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-navy-900">{{ t("Children") }}</h2>
                <div v-if="canManage" class="flex gap-2">
                    <button class="rounded-lg border border-navy-900 px-3 py-1.5 text-sm text-navy-900" @click="showAddClassroom = !showAddClassroom">{{ t("+ Classroom") }}</button>
                    <button class="rounded-lg bg-navy-900 px-3 py-1.5 text-sm text-white" @click="showAddChild = !showAddChild">{{ t("+ Child") }}</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form v-if="showAddClassroom" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4" @submit.prevent="addClassroom">
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("Classroom name") }}</label>
                        <input v-model="classroomForm.name" class="rounded-lg border-gray-300" required />
                        <InputError :message="classroomForm.errors.name" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("Capacity") }}</label>
                        <input v-model="classroomForm.capacity" type="number" min="1" class="w-24 rounded-lg border-gray-300" />
                    </div>
                    <button class="rounded-lg bg-mint-500 px-4 py-2 text-sm font-medium text-navy-900" :disabled="classroomForm.processing">{{ t("Save classroom") }}</button>
                </form>

                <form v-if="showAddChild" class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="addChild">
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("First name") }}</label>
                        <input v-model="childForm.first_name" class="w-full rounded-lg border-gray-300" required />
                        <InputError :message="childForm.errors.first_name" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("Last name") }}</label>
                        <input v-model="childForm.last_name" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("Date of birth") }}</label>
                        <input v-model="childForm.dob" type="date" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">{{ t("Classroom") }}</label>
                        <select v-model="childForm.classroom_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ t("None") }}</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">{{ t("Allergies (visible to all staff)") }}</label>
                        <input v-model="childForm.allergies" class="w-full rounded-lg border-gray-300" :placeholder='t("e.g. peanuts, lactose")' />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">{{ t("Health notes (owner/manager only)") }}</label>
                        <input v-model="childForm.health_notes" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button class="rounded-lg bg-navy-900 px-4 py-2 text-sm text-white" :disabled="childForm.processing">{{ t("Save child") }}</button>
                    </div>
                </form>

                <section class="mb-4 flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4"><input v-model="search" type="search" :placeholder="t('Search children')" :aria-label="t('Search children')" class="search-field !w-auto flex-1" /><select v-model="classroomFilter" :aria-label="t('Classroom')" class="search-field !w-auto"><option value="">{{ t('Classrooms') }}</option><option v-for="r in classrooms" :key="r.id">{{ r.name }}</option></select><select v-model="statusFilter" :aria-label="t('Status')" class="search-field !w-auto"><option value="">{{ t('All statuses') }}</option><option value="active">{{ t('active') }}</option><option value="archived">{{ t('archived') }}</option></select></section><div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ t("Name") }}</th>
                                <th class="px-4 py-3">{{ t("Classroom") }}</th>
                                <th class="px-4 py-3">{{ t("Status") }}</th>
                                <th class="px-4 py-3">{{ t("Allergies") }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in filtered" :key="c.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <Link :href="route('children.show', c.id)" class="font-medium text-navy-900 hover:underline">{{ c.name }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ c.classroom || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="c.status === 'active' ? 'bg-mint-100 text-navy-900' : 'bg-gray-100 text-gray-500'" class="rounded-full px-2 py-0.5 text-xs">{{ t(c.status) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="c.has_allergies" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ t("Yes") }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">{{ t("No children yet. Add the first child to start the register.") }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
