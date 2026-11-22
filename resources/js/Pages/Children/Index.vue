<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ children: Array, classrooms: Array, canManage: Boolean });

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
    <Head title="Children" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-navy-900">Children</h2>
                <div v-if="canManage" class="flex gap-2">
                    <button class="rounded-lg border border-navy-900 px-3 py-1.5 text-sm text-navy-900" @click="showAddClassroom = !showAddClassroom">+ Classroom</button>
                    <button class="rounded-lg bg-navy-900 px-3 py-1.5 text-sm text-white" @click="showAddChild = !showAddChild">+ Child</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form v-if="showAddClassroom" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4" @submit.prevent="addClassroom">
                    <div>
                        <label class="block text-xs text-gray-500">Classroom name</label>
                        <input v-model="classroomForm.name" class="rounded-lg border-gray-300" required />
                        <InputError :message="classroomForm.errors.name" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Capacity</label>
                        <input v-model="classroomForm.capacity" type="number" min="1" class="w-24 rounded-lg border-gray-300" />
                    </div>
                    <button class="rounded-lg bg-mint-500 px-4 py-2 text-sm font-medium text-navy-900" :disabled="classroomForm.processing">Save classroom</button>
                </form>

                <form v-if="showAddChild" class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="addChild">
                    <div>
                        <label class="block text-xs text-gray-500">First name</label>
                        <input v-model="childForm.first_name" class="w-full rounded-lg border-gray-300" required />
                        <InputError :message="childForm.errors.first_name" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Last name</label>
                        <input v-model="childForm.last_name" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Date of birth</label>
                        <input v-model="childForm.dob" type="date" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Classroom</label>
                        <select v-model="childForm.classroom_id" class="w-full rounded-lg border-gray-300">
                            <option value="">None</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">Allergies (visible to all staff)</label>
                        <input v-model="childForm.allergies" class="w-full rounded-lg border-gray-300" placeholder="e.g. peanuts, lactose" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">Health notes (owner/manager only)</label>
                        <input v-model="childForm.health_notes" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button class="rounded-lg bg-navy-900 px-4 py-2 text-sm text-white" :disabled="childForm.processing">Save child</button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Classroom</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Allergies</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in children" :key="c.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <Link :href="route('children.show', c.id)" class="font-medium text-navy-900 hover:underline">{{ c.name }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ c.classroom || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="c.status === 'active' ? 'bg-mint-100 text-navy-900' : 'bg-gray-100 text-gray-500'" class="rounded-full px-2 py-0.5 text-xs">{{ c.status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="c.has_allergies" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Yes</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="children.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">No children yet. Add the first child to start the register.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
