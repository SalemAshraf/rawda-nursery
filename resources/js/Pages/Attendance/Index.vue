<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ date: String, isToday: Boolean, children: Array });

const checkout = reactive({});

function goToDate(e) {
    router.get(route('attendance.index'), { date: e.target.value }, { preserveState: true });
}
function checkIn(childId) {
    router.post(route('attendance.check-in', childId), {}, { preserveScroll: true });
}
function checkOut(childId) {
    const pickupId = checkout[childId];
    router.post(route('attendance.check-out', childId), { pickup_person_id: pickupId }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Attendance" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-navy-900">Attendance</h2>
                <input type="date" :value="date" class="rounded-lg border-gray-300 text-sm" @change="goToDate" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p v-if="!isToday" class="mb-3 rounded-lg bg-gray-100 px-3 py-2 text-xs text-gray-500">
                    Viewing a past date. Check-in and checkout only apply to today.
                </p>
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Child</th>
                                <th class="px-4 py-3">Classroom</th>
                                <th class="px-4 py-3">In</th>
                                <th class="px-4 py-3">Out</th>
                                <th class="px-4 py-3">Picked up by</th>
                                <th v-if="isToday" class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in children" :key="c.id">
                                <td class="px-4 py-3 font-medium text-navy-900">{{ c.name }}</td>
                                <td class="px-4 py-3">{{ c.classroom || '—' }}</td>
                                <td class="px-4 py-3">{{ c.check_in_at || '—' }}</td>
                                <td class="px-4 py-3">{{ c.check_out_at || '—' }}</td>
                                <td class="px-4 py-3">{{ c.picked_up_by || '—' }}</td>
                                <td v-if="isToday" class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!c.check_in_at"
                                            class="rounded-lg bg-mint-500 px-3 py-1 text-xs font-medium text-navy-900"
                                            @click="checkIn(c.id)"
                                        >Check in</button>
                                        <template v-else-if="!c.check_out_at">
                                            <select v-model="checkout[c.id]" class="rounded-lg border-gray-300 py-1 text-xs">
                                                <option :value="undefined" disabled>Pickup person</option>
                                                <option v-for="p in c.pickup_persons" :key="p.id" :value="p.id">{{ p.name }}</option>
                                            </select>
                                            <button
                                                class="rounded-lg bg-navy-900 px-3 py-1 text-xs text-white disabled:opacity-40"
                                                :disabled="!checkout[c.id]"
                                                @click="checkOut(c.id)"
                                            >Check out</button>
                                        </template>
                                        <span v-else class="text-xs text-gray-400">Done</span>
                                    </div>
                                    <p v-if="c.check_in_at && !c.check_out_at && c.pickup_persons.length === 0" class="mt-1 text-xs text-amber-700">
                                        No authorized pickup person on file.
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="children.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">No active children.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
