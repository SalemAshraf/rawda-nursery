<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ date: String, children: Array });

const forms = reactive({});
props.children.forEach((c) => {
    forms[c.id] = useForm({
        date: props.date,
        meals: c.log?.meals || '',
        naps: c.log?.naps || '',
        mood: c.log?.mood || '',
        activities: c.log?.activities || '',
        notes: c.log?.notes || '',
    });
});

function goToDate(e) {
    router.get(route('logs.index'), { date: e.target.value }, { preserveState: false });
}
function save(childId) {
    forms[childId].post(route('logs.upsert', childId), { preserveScroll: true });
}
</script>

<template>
    <Head title="Daily log" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-navy-900">Daily log</h2>
                <input type="date" :value="date" class="rounded-lg border-gray-300 text-sm" @change="goToDate" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-for="c in children" :key="c.id" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <span class="font-medium text-navy-900">{{ c.name }}</span>
                            <span class="ml-2 text-xs text-gray-400">{{ c.classroom || 'No classroom' }}</span>
                        </div>
                        <span v-if="c.log" class="text-xs text-mint-600">Saved</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm md:grid-cols-5">
                        <input v-model="forms[c.id].meals" placeholder="Meals" class="rounded-lg border-gray-300" />
                        <input v-model="forms[c.id].naps" placeholder="Naps" class="rounded-lg border-gray-300" />
                        <input v-model="forms[c.id].mood" placeholder="Mood" class="rounded-lg border-gray-300" />
                        <input v-model="forms[c.id].activities" placeholder="Activities" class="rounded-lg border-gray-300" />
                        <input v-model="forms[c.id].notes" placeholder="Notes" class="rounded-lg border-gray-300" />
                    </div>
                    <button class="mt-2 rounded-lg bg-navy-900 px-3 py-1 text-xs text-white" :disabled="forms[c.id].processing" @click="save(c.id)">
                        Save
                    </button>
                </div>
                <p v-if="children.length === 0" class="py-8 text-center text-gray-400">No active children.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
