<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ incidents: Array, canManage: Boolean });

const ackForms = reactive({});
props.incidents.forEach((i) => {
    ackForms[i.id] = useForm({ guardian_name: '' });
});

function acknowledge(id) {
    ackForms[id].post(route('incidents.acknowledge', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Incidents" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-navy-900">Incidents</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-3 px-4 sm:px-6 lg:px-8">
                <p class="text-xs text-gray-500">
                    Report an incident from the child's page. Acknowledgement here records that a named guardian
                    was informed offline (in person or by phone); there is no guardian login in this version.
                </p>
                <div v-for="i in incidents" :key="i.id" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <Link :href="route('children.show', i.child_id)" class="font-medium text-navy-900 hover:underline">{{ i.child }}</Link>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <span :class="{
                                'bg-yellow-100 text-yellow-800': i.severity === 'minor',
                                'bg-orange-100 text-orange-800': i.severity === 'moderate',
                                'bg-red-100 text-red-800': i.severity === 'severe',
                            }" class="rounded-full px-2 py-0.5">{{ i.severity }}</span>
                            {{ i.occurred_at }}
                        </div>
                    </div>
                    <p class="mt-2 text-sm">{{ i.description }}</p>
                    <p class="mt-1 text-xs text-gray-400">Reported by {{ i.reported_by }}</p>
                    <div class="mt-2">
                        <p v-if="i.acknowledged" class="text-xs text-mint-600">Acknowledged by {{ i.acknowledged_by }}</p>
                        <form v-else-if="canManage" class="flex items-center gap-2" @submit.prevent="acknowledge(i.id)">
                            <input v-model="ackForms[i.id].guardian_name" placeholder="Guardian name" class="rounded-lg border-gray-300 py-1 text-xs" required />
                            <button class="rounded-lg bg-navy-900 px-3 py-1 text-xs text-white">Record acknowledgement</button>
                            <InputError :message="ackForms[i.id].errors.guardian_name" />
                        </form>
                        <p v-else class="text-xs text-amber-700">Awaiting guardian acknowledgement</p>
                    </div>
                </div>
                <p v-if="incidents.length === 0" class="py-8 text-center text-gray-400">No incidents recorded.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
