<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    subscription: Object,
    legacy: Boolean,
    plans: Array,
    requests: Array,
});
const { locale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const form = useForm({ saas_plan_id: "", message: "" });
const labels = {
    pending: ["بانتظار التفعيل", "Pending activation"],
    active: ["نشط", "Active"],
    past_due: ["بانتظار التجديد", "Renewal due"],
    suspended: ["موقوف", "Suspended"],
};
</script>
<template>
    <Head
        :title="L('اشتراك الحضانة', 'Nursery subscription')"
    /><AuthenticatedLayout
        ><div class="ops-page">
            <header class="ops-heading">
                <div>
                    <p class="eyebrow">
                        {{ L("مساحة عملك", "Your workspace") }}
                    </p>
                    <h1>{{ L("الباقة والاشتراك", "Plan & subscription") }}</h1>
                    <p>
                        {{
                            L(
                                "طلب الاشتراك لا يحصّل رسوماً ولا يغيّر باقتك تلقائياً.",
                                "A request does not charge money or change your current plan.",
                            )
                        }}
                    </p>
                </div>
            </header>
            <div class="saas-card">
                <span class="saas-pill">{{
                    legacy
                        ? L("وصول قائم محفوظ", "Existing access preserved")
                        : L(...(labels[subscription.status] || labels.pending))
                }}</span>
                <h2>
                    {{
                        subscription?.plan
                            ? locale === "ar"
                                ? subscription.plan.name_ar
                                : subscription.plan.name_en
                            : L("لم تحدد باقة بعد", "No plan selected")
                    }}
                </h2>
                <p v-if="subscription?.period_ends_at">
                    {{ L("نهاية الفترة", "Period ends") }}:
                    {{
                        new Date(
                            subscription.period_ends_at,
                        ).toLocaleDateString(
                            locale === "ar" ? "ar-EG" : "en-GB",
                        )
                    }}
                </p>
                <p v-else>
                    {{
                        L(
                            "لا يوجد تاريخ انتهاء مسجل. حالة التفعيل موضحة أعلاه.",
                            "No end date is recorded. Activation status is shown above.",
                        )
                    }}
                </p>
            </div>
            <div class="subscription-grid">
                <section class="saas-card">
                    <h2>
                        {{
                            L(
                                "طلب تفعيل أو تغيير باقة",
                                "Request activation or a plan change",
                            )
                        }}
                    </h2>
                    <form
                        @submit.prevent="
                            form.post(route('subscription.request'), {
                                onSuccess: () => form.reset(),
                            })
                        "
                        class="saas-form"
                    >
                        <label
                            >{{ L("الباقة المطلوبة", "Requested plan")
                            }}<select v-model="form.saas_plan_id">
                                <option value="">
                                    {{
                                        L(
                                            "أحتاج عرضاً مناسباً لحضانتي",
                                            "Help me choose a plan",
                                        )
                                    }}
                                </option>
                                <option v-for="p in plans" :value="p.id">
                                    {{
                                        locale === "ar" ? p.name_ar : p.name_en
                                    }}
                                </option>
                            </select></label
                        ><label
                            >{{ L("احتياجات الحضانة", "Nursery needs")
                            }}<textarea
                                v-model="form.message"
                                rows="4"
                                maxlength="2000"
                            />
                        </label>
                        <p
                            v-for="e in form.errors"
                            role="alert"
                            class="text-red-700"
                        >
                            {{ e }}
                        </p>
                        <button
                            class="saas-primary"
                            :disabled="form.processing"
                        >
                            {{
                                L(
                                    "إرسال طلب للمراجعة",
                                    "Submit request for review",
                                )
                            }}
                        </button>
                    </form>
                </section>
                <section class="saas-card">
                    <h2>{{ L("آخر الطلبات", "Recent requests") }}</h2>
                    <div v-for="r in requests" class="platform-row">
                        <span
                            >#{{ r.id }} ·
                            {{
                                new Date(r.created_at).toLocaleDateString()
                            }}</span
                        ><span>{{
                            {
                                new: L("جديد", "New"),
                                contacted: L("تم التواصل", "Contacted"),
                                qualified: L("مؤهل", "Qualified"),
                                closed: L("مغلق", "Closed"),
                            }[r.status]
                        }}</span>
                    </div>
                    <p v-if="!requests.length">
                        {{ L("لا توجد طلبات بعد.", "No requests yet.") }}
                    </p>
                    <p class="saas-muted">
                        {{
                            L(
                                "تتم مراجعة السعر وشروط التفعيل مع إدارة المنصة. لا توجد بوابة دفع مفعلة.",
                                "Price and activation terms are reviewed with the platform team. No payment gateway is active.",
                            )
                        }}
                    </p>
                </section>
            </div>
        </div></AuthenticatedLayout
    >
</template>
