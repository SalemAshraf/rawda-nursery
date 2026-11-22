<script setup>
import { useI18n } from '@/lib/i18n';
const { t } = useI18n();
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ staff: Array });

const form = useForm({ name: '', email: '', role: 'teacher', password: '', password_confirmation: '' });

function addStaff() {
    form.post(route('staff.store'), { onSuccess: () => form.reset() });
}
</script>

<template>
    <Head :title='t("Staff")' />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-navy-900">{{ t("Staff") }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr><th class="px-3 py-2">{{ t("Name") }}</th><th class="px-3 py-2">{{ t("Email") }}</th><th class="px-3 py-2">{{ t("Role") }}</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in staff" :key="s.id">
                                <td class="px-3 py-2 font-medium text-navy-900">{{ s.name }}</td>
                                <td class="px-3 py-2">{{ s.email }}</td>
                                <td class="px-3 py-2">{{ t(s.role) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-navy-900">{{ t("Add a staff account") }}</h3>
                    <form class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2" @submit.prevent="addStaff">
                        <div>
                            <input v-model="form.name" :placeholder='t("Name")' class="w-full rounded-lg border-gray-300" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div>
                            <input v-model="form.email" type="email" :placeholder='t("Email")' class="w-full rounded-lg border-gray-300" required />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div>
                            <select v-model="form.role" class="w-full rounded-lg border-gray-300">
                                <option value="teacher">{{ t("teacher") }}</option>
                                <option value="manager">{{ t("manager") }}</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-400">{{ t("Teachers log attendance and the day. Managers also run records and billing.") }}</p>
                        </div>
                        <div>
                            <input v-model="form.password" type="password" :placeholder='t("Password")' class="w-full rounded-lg border-gray-300" required />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div>
                            <input v-model="form.password_confirmation" type="password" :placeholder='t("Confirm password")' class="w-full rounded-lg border-gray-300" required />
                        </div>
                        <div class="sm:col-span-2">
                            <button class="rounded-lg bg-navy-900 px-4 py-2 text-white" :disabled="form.processing">{{ t("Create account") }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
