<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ today: String, stats: Object });

const cards = [
    { key: 'active_children', label: 'Children enrolled', href: 'children.index' },
    { key: 'present_today', label: 'Present today', href: 'attendance.index' },
    { key: 'checked_out_today', label: 'Checked out today', href: 'attendance.index' },
    { key: 'outstanding', label: 'Fees outstanding', href: 'billing.index' },
    { key: 'overdue_invoices', label: 'Overdue invoices', href: 'billing.index' },
    { key: 'unacknowledged_incidents', label: 'Incidents awaiting guardian acknowledgement', href: 'incidents.index' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-navy-900">Today, {{ today }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <Link
                        v-for="card in cards"
                        :key="card.key"
                        :href="route(card.href)"
                        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-mint-500"
                    >
                        <div class="text-2xl font-bold text-navy-900">{{ stats[card.key] }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ card.label }}</div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
