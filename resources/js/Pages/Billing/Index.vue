<script setup>
import { useI18n } from '@/lib/i18n';
const { t } = useI18n();
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({ currency: String, plans: Array, invoices: Array, children: Array });

const planForm = useForm({ name: '', amount: '', period: 'monthly' });
const invoiceForm = useForm({ child_id: '', description: '', issue_date: '', due_date: '', amount: '' });
const paymentForms = reactive({});
props.invoices.forEach((i) => {
    paymentForms[i.id] = useForm({ amount: '', method: 'cash', reference: '' });
});
const openPayments = ref(null);

function addPlan() {
    planForm.post(route('billing.plans.store'), { onSuccess: () => planForm.reset() });
}
function addInvoice() {
    invoiceForm.post(route('billing.invoices.store'), { onSuccess: () => invoiceForm.reset() });
}
function post(action, id) {
    router.post(route(action, id), {}, { preserveScroll: true });
}
function addPayment(id) {
    paymentForms[id].post(route('billing.payments.store', id), {
        preserveScroll: true,
        onSuccess: () => paymentForms[id].reset(),
    });
}
</script>

<template>
    <Head :title='t("Billing")' />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-navy-900">{{ t("Billing") }}</h2>
        </template>

        <div class="py-8 billing-page">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-xs text-gray-500">{{ t("Payments are recorded manually (cash, transfer, wallet reference). Online collection is not part of this version.") }}</p>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Tuition plans") }}</h3>
                    <ul class="mb-4 space-y-1 text-sm">
                        <li v-for="p in plans" :key="p.id" class="flex justify-between">
                            <span>{{ p.name }} <span class="text-gray-400">({{ t(p.period) }})</span></span>
                            <span>{{ p.amount }}</span>
                        </li>
                        <li v-if="plans.length === 0" class="text-gray-400">{{ t("No plans yet.") }}</li>
                    </ul>
                    <form class="flex flex-wrap items-end gap-2 text-sm" @submit.prevent="addPlan">
                        <input v-model="planForm.name" :placeholder='t("Plan name")' class="rounded-lg border-gray-300" required />
                        <input v-model="planForm.amount" :placeholder='t("Amount")' class="w-28 rounded-lg border-gray-300" required />
                        <select v-model="planForm.period" class="rounded-lg border-gray-300">
                            <option value="monthly">{{ t("monthly") }}</option>
                            <option value="term">{{ t("term") }}</option>
                            <option value="yearly">{{ t("yearly") }}</option>
                        </select>
                        <button class="rounded-lg bg-navy-900 px-3 py-2 text-white" :disabled="planForm.processing">{{ t("Add plan") }}</button>
                        <InputError :message="planForm.errors.amount" />
                    </form>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Invoices") }}</h3>
                    <form class="mb-4 flex flex-wrap items-end gap-2 text-sm" @submit.prevent="addInvoice">
                        <label class="block">
                            <span class="mb-0.5 block text-xs text-gray-500">{{ t("Child") }}</span>
                            <select v-model="invoiceForm.child_id" class="rounded-lg border-gray-300" required>
                                <option value="" disabled>{{ t("Select child") }}</option>
                                <option v-for="c in children" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-0.5 block text-xs text-gray-500">{{ t("Description") }}</span>
                            <input v-model="invoiceForm.description" :placeholder='t("e.g. November tuition")' class="rounded-lg border-gray-300" />
                        </label>
                        <label class="block">
                            <span class="mb-0.5 block text-xs text-gray-500">{{ t("Issue date") }}</span>
                            <input v-model="invoiceForm.issue_date" type="date" class="rounded-lg border-gray-300" required />
                        </label>
                        <label class="block">
                            <span class="mb-0.5 block text-xs text-gray-500">{{ t("Due date") }}</span>
                            <input v-model="invoiceForm.due_date" type="date" class="rounded-lg border-gray-300" required />
                        </label>
                        <label class="block">
                            <span class="mb-0.5 block text-xs text-gray-500">{{ t("Amount") }} ({{ currency }})</span>
                            <input v-model="invoiceForm.amount" :placeholder='t("0.00")' class="w-28 rounded-lg border-gray-300" required />
                        </label>
                        <button class="rounded-lg bg-navy-900 px-3 py-2 text-white" :disabled="invoiceForm.processing">{{ t("Create invoice") }}</button>
                        <InputError :message="invoiceForm.errors.child_id || invoiceForm.errors.amount" />
                    </form>

                    <div class="overflow-x-auto record-table-wrap">
                        <table role="table" class="record-table invoice-table min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2">{{ t("Child") }}</th>
                                    <th class="px-3 py-2">{{ t("Due") }}</th>
                                    <th class="px-3 py-2">{{ t("Amount") }}</th>
                                    <th class="px-3 py-2">{{ t("Paid") }}</th>
                                    <th class="px-3 py-2">{{ t("Outstanding") }}</th>
                                    <th class="px-3 py-2">{{ t("Status") }}</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template v-for="i in invoices" :key="i.id">
                                    <tr>
                                        <td :data-label="t('Child')" class="px-3 py-2 font-medium text-navy-900">{{ i.child }}</td>
                                        <td :data-label="t('Due')" class="px-3 py-2"><span dir="ltr" class="inline-block">{{ i.due_date }}</span></td>
                                        <td :data-label="t('Amount')" class="px-3 py-2"><span dir="ltr" class="inline-block">{{ i.amount }}</span></td>
                                        <td :data-label="t('Paid')" class="px-3 py-2"><span dir="ltr" class="inline-block">{{ i.paid }}</span></td>
                                        <td :data-label="t('Outstanding')" class="px-3 py-2"><span dir="ltr" class="inline-block">{{ i.outstanding }}</span></td>
                                        <td :data-label="t('Status')" class="px-3 py-2">
                                            <span :class="{
                                                'bg-gray-100 text-gray-600': i.status === 'draft',
                                                'bg-blue-100 text-blue-800': i.status === 'sent' && !i.overdue,
                                                'bg-red-100 text-red-800': i.overdue,
                                                'bg-mint-100 text-navy-900': i.status === 'paid',
                                                'bg-gray-200 text-gray-500': i.status === 'void',
                                            }" class="rounded-full px-2 py-0.5 text-xs">
                                                {{ t(i.overdue ? 'overdue' : i.status) }}
                                            </span>
                                        </td>
                                        <td class="record-actions px-3 py-2">
                                            <div class="flex flex-wrap gap-2 text-xs">
                                                <button v-if="i.status === 'draft'" class="text-navy-700 hover:underline" @click="post('billing.invoices.send', i.id)">{{ t("Send") }}</button>
                                                <button v-if="i.status !== 'paid' && i.status !== 'void'" class="text-navy-700 hover:underline" @click="openPayments = openPayments === i.id ? null : i.id">{{ t("Payment") }}</button>
                                                <button v-if="(i.status === 'draft' || i.status === 'sent') && i.payments.length === 0" class="text-red-600 hover:underline" @click="post('billing.invoices.void', i.id)">{{ t("Void") }}</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="openPayments === i.id" class="record-details">
                                        <td colspan="7" class="bg-gray-50 px-3 py-2">
                                            <form class="flex flex-wrap items-center gap-2 text-xs" @submit.prevent="addPayment(i.id)">
                                                <input v-model="paymentForms[i.id].amount" :placeholder='t("Amount")' class="w-24 rounded-lg border-gray-300 py-1" required />
                                                <select v-model="paymentForms[i.id].method" class="rounded-lg border-gray-300 py-1">
                                                    <option value="cash">{{ t("cash") }}</option>
                                                    <option value="bank transfer">{{ t("bank transfer") }}</option>
                                                    <option value="wallet">{{ t("wallet") }}</option>
                                                    <option value="other">{{ t("other") }}</option>
                                                </select>
                                                <input v-model="paymentForms[i.id].reference" :placeholder='t("Reference (optional)")' class="rounded-lg border-gray-300 py-1" />
                                                <button class="rounded-lg bg-mint-500 px-3 py-1 font-medium text-navy-900">{{ t("Record payment") }}</button>
                                                <InputError :message="paymentForms[i.id].errors.amount" />
                                            </form>
                                            <ul class="mt-2 space-y-0.5 text-xs text-gray-500">
                                                <li v-for="p in i.payments" :key="p.id">{{ p.paid_at }} · {{ p.amount }} · {{ p.method }} {{ p.reference ? '· ' + p.reference : '' }}</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="invoices.length === 0">
                                    <td colspan="7" class="px-3 py-6 text-center text-gray-400">{{ t("No invoices yet.") }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
