<script setup>
import { useI18n } from '@/lib/i18n';
const { t } = useI18n();
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    child: Object, guardians: Array, pickupPersons: Array,
    incidents: Array, classrooms: Array, canManage: Boolean, canSeeHealth: Boolean,
});

const showEdit = ref(false);

const editForm = useForm({
    first_name: props.child.first_name,
    last_name: props.child.last_name,
    dob: props.child.dob || '',
    gender: props.child.gender || '',
    classroom_id: props.child.classroom_id || '',
    allergies: props.child.allergies || '',
    health_notes: props.child.health_notes || '',
    status: props.child.status,
});

const guardianForm = useForm({ name: '', phone: '', email: '', relation: '', is_primary: false });
const pickupForm = useForm({ name: '', relation: '', phone: '' });
const incidentForm = useForm({ occurred_at: '', severity: 'minor', description: '' });

function saveEdit() {
    editForm.transform((d) => ({ ...d, classroom_id: d.classroom_id === '' ? null : d.classroom_id }))
        .put(route('children.update', props.child.id), { onSuccess: () => (showEdit.value = false) });
}
function addGuardian() {
    guardianForm.post(route('guardians.store', props.child.id), { onSuccess: () => guardianForm.reset(), preserveScroll: true });
}
function addPickup() {
    pickupForm.post(route('pickups.store', props.child.id), { onSuccess: () => pickupForm.reset(), preserveScroll: true });
}
function revokePickup(id) {
    if (confirm('Remove this person from the authorized pickup list?')) {
        pickupForm.delete(route('pickups.destroy', id), { preserveScroll: true });
    }
}
function reportIncident() {
    incidentForm.post(route('incidents.store', props.child.id), { onSuccess: () => incidentForm.reset(), preserveScroll: true });
}
</script>

<template>
    <Head :title="child.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-navy-900">
                    {{ child.name }}
                    <span :class="child.status === 'active' ? 'bg-mint-100 text-navy-900' : 'bg-gray-100 text-gray-500'" class="ml-2 rounded-full px-2 py-0.5 text-xs">{{ t(child.status) }}</span>
                </h2>
                <button v-if="canManage" class="rounded-lg border border-navy-900 px-3 py-1.5 text-sm text-navy-900" @click="showEdit = !showEdit">
                    {{ t(showEdit ? 'Cancel' : 'Edit') }}
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Record") }}</h3>
                    <dl v-if="!showEdit" class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="text-gray-500">{{ t("Classroom") }}</dt><dd>{{ child.classroom || '—' }}</dd>
                        <dt class="text-gray-500">{{ t("Date of birth") }}</dt><dd>{{ child.dob || '—' }}</dd>
                        <dt class="text-gray-500">{{ t("Enrolled") }}</dt><dd>{{ child.enrolled_at || '—' }}</dd>
                        <dt class="text-gray-500">{{ t("Allergies") }}</dt>
                        <dd>
                            <span v-if="child.allergies" class="rounded bg-amber-100 px-2 py-0.5 text-amber-800">{{ child.allergies }}</span>
                            <span v-else>—</span>
                        </dd>
                        <template v-if="canSeeHealth">
                            <dt class="text-gray-500">{{ t("Health notes") }}</dt><dd>{{ child.health_notes || '—' }}</dd>
                        </template>
                        <p v-else class="col-span-2 text-xs text-gray-400">{{ t("Health notes are restricted to owner and manager roles.") }}</p>
                    </dl>

                    <form v-else class="child-detail-form grid grid-cols-2 gap-3 text-sm" @submit.prevent="saveEdit">
                        <div><label class="text-xs text-gray-500">{{ t("First name") }}</label><input v-model="editForm.first_name" class="w-full rounded-lg border-gray-300" required /></div>
                        <div><label class="text-xs text-gray-500">{{ t("Last name") }}</label><input v-model="editForm.last_name" class="w-full rounded-lg border-gray-300" required /></div>
                        <div><label class="text-xs text-gray-500">{{ t("Date of birth") }}</label><input v-model="editForm.dob" type="date" class="w-full rounded-lg border-gray-300" /></div>
                        <div>
                            <label class="text-xs text-gray-500">{{ t("Status") }}</label>
                            <select v-model="editForm.status" class="w-full rounded-lg border-gray-300">
                                <option value="active">{{ t("active") }}</option>
                                <option value="archived">{{ t("archived") }}</option>
                            </select>
                        </div>
                        <div class="col-span-2"><label class="text-xs text-gray-500">{{ t("Classroom") }}</label><select v-model="editForm.classroom_id" class="w-full rounded-lg border-gray-300"><option value="">{{ t("No classroom") }}</option><option v-for="r in classrooms" :key="r.id" :value="r.id">{{ r.name }}</option></select></div><div class="col-span-2"><label class="text-xs text-gray-500">{{ t("Allergies") }}</label><input v-model="editForm.allergies" class="w-full rounded-lg border-gray-300" /></div>
                        <div v-if="canSeeHealth" class="col-span-2"><label class="text-xs text-gray-500">{{ t("Health notes") }}</label><textarea v-model="editForm.health_notes" rows="2" class="w-full rounded-lg border-gray-300"></textarea></div>
                        <div class="col-span-2">
                            <button class="rounded-lg bg-navy-900 px-4 py-2 text-sm text-white" :disabled="editForm.processing">{{ t("Save") }}</button>
                            <InputError :message="editForm.errors.classroom_id" />
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Guardians") }}</h3>
                    <ul class="mb-4 space-y-2 text-sm">
                        <li v-for="g in guardians" :key="g.id" class="flex items-center justify-between">
                            <span>{{ g.name }} <span class="text-gray-400">({{ g.relation }})</span></span>
                            <span class="text-gray-500">{{ g.phone }}</span>
                        </li>
                        <li v-if="guardians.length === 0" class="text-gray-400">{{ t("No guardians recorded.") }}</li>
                    </ul>
                    <form v-if="canManage" class="child-detail-form grid grid-cols-2 gap-2 text-sm" @submit.prevent="addGuardian">
                        <input v-model="guardianForm.name" :placeholder='t("Name")' class="rounded-lg border-gray-300" required />
                        <input v-model="guardianForm.relation" :placeholder='t("Relation (e.g. mother)")' class="rounded-lg border-gray-300" required />
                        <input v-model="guardianForm.phone" :placeholder='t("Phone")' class="rounded-lg border-gray-300" required />
                        <input v-model="guardianForm.email" :placeholder='t("Email (optional)")' class="rounded-lg border-gray-300" />
                        <label class="col-span-2 flex items-center gap-2 text-xs text-gray-500">
                            <input v-model="guardianForm.is_primary" type="checkbox" class="rounded" />{{ t("Primary contact") }}</label>
                        <button class="col-span-2 rounded-lg bg-navy-900 px-3 py-1.5 text-white" :disabled="guardianForm.processing">{{ t("Add guardian") }}</button>
                    </form>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-1 font-semibold text-navy-900">{{ t("Authorized pickup") }}</h3>
                    <p class="mb-3 text-xs text-gray-500">{{ t("Only the people listed here can be recorded at checkout. Listing authorizes pickup collection only; staff must still verify the person's identity at the door.") }}</p>
                    <ul class="mb-4 space-y-2 text-sm">
                        <li v-for="p in pickupPersons" :key="p.id" class="flex items-center justify-between">
                            <span :class="{ 'text-gray-400 line-through': !p.active }">
                                {{ p.name }} <span class="text-gray-400">({{ p.relation }})</span>
                            </span>
                            <button v-if="p.active && canManage" class="text-xs text-red-600 hover:underline" @click="revokePickup(p.id)">{{ t("Remove") }}</button>
                        </li>
                        <li v-if="pickupPersons.length === 0" class="text-amber-700">{{ t("Nobody is authorized yet. The child cannot be checked out until someone is added.") }}</li>
                    </ul>
                    <form v-if="canManage" class="child-detail-form grid grid-cols-3 gap-2 text-sm" @submit.prevent="addPickup">
                        <input v-model="pickupForm.name" :placeholder='t("Name")' class="rounded-lg border-gray-300" required />
                        <input v-model="pickupForm.relation" :placeholder='t("Relation")' class="rounded-lg border-gray-300" required />
                        <input v-model="pickupForm.phone" :placeholder='t("Phone")' class="rounded-lg border-gray-300" />
                        <button class="col-span-3 rounded-lg bg-navy-900 px-3 py-1.5 text-white" :disabled="pickupForm.processing">{{ t("Authorize pickup person") }}</button>
                    </form>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Incidents") }}</h3>
                    <ul class="mb-4 space-y-3 text-sm">
                        <li v-for="i in incidents" :key="i.id" class="rounded-lg border border-gray-100 p-3">
                            <div class="flex items-center justify-between">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-800': i.severity === 'minor',
                                    'bg-orange-100 text-orange-800': i.severity === 'moderate',
                                    'bg-red-100 text-red-800': i.severity === 'severe',
                                }" class="rounded-full px-2 py-0.5 text-xs">{{ t(i.severity) }}</span>
                                <span class="text-xs text-gray-400">{{ i.occurred_at }} · {{ i.reported_by }}</span>
                            </div>
                            <p class="mt-1">{{ i.description }}</p>
                            <p class="mt-1 text-xs" :class="i.acknowledged ? 'text-mint-600' : 'text-amber-700'">
                                {{ t(i.acknowledged ? 'Guardian acknowledged' : 'Awaiting guardian acknowledgement') }}
                            </p>
                        </li>
                        <li v-if="incidents.length === 0" class="text-gray-400">{{ t("No incidents recorded.") }}</li>
                    </ul>
                    <form class="child-detail-form grid grid-cols-2 gap-2 text-sm" @submit.prevent="reportIncident">
                        <input v-model="incidentForm.occurred_at" type="datetime-local" class="rounded-lg border-gray-300" required />
                        <select v-model="incidentForm.severity" class="rounded-lg border-gray-300">
                            <option value="minor">{{ t("minor") }}</option>
                            <option value="moderate">{{ t("moderate") }}</option>
                            <option value="severe">{{ t("severe") }}</option>
                        </select>
                        <textarea v-model="incidentForm.description" rows="2" :placeholder='t("What happened")' class="col-span-2 rounded-lg border-gray-300" required></textarea>
                        <button class="col-span-2 rounded-lg bg-navy-900 px-3 py-1.5 text-white" :disabled="incidentForm.processing">{{ t("Report incident") }}</button>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
