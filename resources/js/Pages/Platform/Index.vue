<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PlatformLayout from "@/Layouts/PlatformLayout.vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    stats: Object,
    nurseries: Object,
    plans: Array,
    inquiries: Object,
    audits: Array,
    filters: Object,
});
const { locale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const tab = ref("nurseries");
const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const editId = ref(null);
const featuresAr = ref("");
const featuresEn = ref("");
const plan = useForm({
    name_ar: "",
    name_en: "",
    description_ar: "",
    description_en: "",
    price: "",
    currency: "EGP",
    interval: "monthly",
    features_ar: [],
    features_en: [],
    published: false,
    sort: 0,
});
function edit(p) {
    editId.value = p.id;
    Object.keys(plan.data()).forEach((k) => (plan[k] = p[k] ?? ""));
    plan.price = p.price_minor === null ? "" : (p.price_minor / 100).toFixed(2);
    featuresAr.value = (p.features_ar || []).join("\n");
    featuresEn.value = (p.features_en || []).join("\n");
    tab.value = "plans";
}
function save() {
    plan.features_ar = featuresAr.value.split("\n").filter(Boolean);
    plan.features_en = featuresEn.value.split("\n").filter(Boolean);
    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            plan.reset();
            editId.value = null;
            featuresAr.value = "";
            featuresEn.value = "";
        },
    };
    editId.value
        ? plan.put(route("platform.plans.update", editId.value), opts)
        : plan.post(route("platform.plans.store"), opts);
}
const selected = ref(null);
const sub = useForm({
    status: "active",
    saas_plan_id: "",
    period_ends_at: "",
    operator_note: "",
    confirm: false,
});
function choose(n) {
    selected.value = n;
    sub.reset();
    sub.status = n.status || "active";
    sub.saas_plan_id = n.saas_plan_id || "";
    sub.period_ends_at = n.period_ends_at
        ? n.period_ends_at.replace(" ", "T").slice(0, 16)
        : "";
}
const statusName = (s) =>
    ({
        pending: L("بانتظار التفعيل", "Pending"),
        active: L("نشط", "Active"),
        past_due: L("بانتظار التجديد", "Renewal due"),
        suspended: L("موقوف", "Suspended"),
        new: L("جديد", "New"),
        contacted: L("تم التواصل", "Contacted"),
        qualified: L("مؤهل", "Qualified"),
        closed: L("مغلق", "Closed"),
    })[s] || L("وصول قائم", "Existing access");
function filter() {
    router.get(
        route("platform.index"),
        { search: search.value, status: status.value },
        { preserveState: true },
    );
}
</script>
<template>
    <Head
        :title="L('إدارة المنصة', 'Platform administration')"
    /><PlatformLayout
        ><header class="ops-heading">
            <div>
                <p class="eyebrow">
                    {{ L("Nursery / إدارة البيع", "Nursery / Sales operations") }}
                </p>
                <h1>
                    {{ L("مساحة نمو المنصة", "Platform growth workspace") }}
                </h1>
                <p>
                    {{
                        L(
                            "إدارة تجارية مستقلة. لا تعرض ملفات الأطفال أو الملاحظات الصحية.",
                            "Separate commercial administration. No child records or health notes are shown.",
                        )
                    }}
                </p>
            </div>
            <Link href="/" class="saas-secondary">{{
                L("معاينة صفحة البيع", "View landing page")
            }}</Link>
        </header>
        <div class="platform-stats">
            <div class="saas-card" v-for="(v, k) in stats">
                <p>
                    {{
                        {
                            nurseries: L("حضانات مسجلة", "Nurseries"),
                            pending: L("بانتظار التفعيل", "Pending activation"),
                            inquiries: L(
                                "طلبات بيع جديدة",
                                "New sales inquiries",
                            ),
                            plans: L("باقات منشورة", "Published plans"),
                        }[k]
                    }}
                </p>
                <strong>{{ v }}</strong>
            </div>
        </div>
        <nav class="platform-tabs">
            <button
                v-for="k in ['nurseries', 'plans', 'inquiries', 'audit']"
                @click="tab = k"
                :class="{ selected: tab === k }"
            >
                {{
                    {
                        nurseries: L(
                            "الحضانات والاشتراكات",
                            "Nurseries & subscriptions",
                        ),
                        plans: L("الباقات والأسعار", "Plans & prices"),
                        inquiries: L("طلبات البيع", "Sales inquiries"),
                        audit: L("سجل الإدارة", "Admin history"),
                    }[k]
                }}
            </button>
        </nav>
        <section v-if="tab === 'nurseries'" class="saas-card">
            <h2>
                {{ L("الحضانات والاشتراكات", "Nurseries & subscriptions") }}
            </h2>
            <form @submit.prevent="filter" class="platform-filter">
                <input
                    v-model="search"
                    :placeholder="L('اسم الحضانة', 'Nursery name')"
                    :aria-label="L('بحث الحضانة', 'Search nursery')"
                /><select
                    v-model="status"
                    :aria-label="L('حالة الاشتراك', 'Subscription status')"
                >
                    <option value="">
                        {{ L("كل الحالات", "All statuses") }}
                    </option>
                    <option
                        v-for="s in [
                            'pending',
                            'active',
                            'past_due',
                            'suspended',
                            'legacy',
                        ]"
                        :value="s"
                    >
                        {{ statusName(s) }}
                    </option></select
                ><button class="saas-secondary">
                    {{ L("بحث", "Search") }}
                </button>
            </form>
            <div v-for="n in nurseries.data" class="platform-row">
                <div>
                    <strong>{{ n.name }}</strong
                    ><small
                        >{{ n.plan_name || L("دون باقة محددة", "No plan") }} ·
                        {{ statusName(n.status) }}<br />{{
                            n.period_ends_at ||
                            L("دون تاريخ انتهاء مسجل", "No recorded expiry")
                        }}</small
                    >
                </div>
                <button @click="choose(n)" class="saas-secondary">
                    {{ L("إدارة الاشتراك", "Manage subscription") }}
                </button>
            </div>
            <p v-if="!nurseries.data.length">
                {{ L("لا توجد نتائج.", "No results.") }}
            </p>
            <div class="platform-pagination">
                <template v-for="link in nurseries.links"
                    ><Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        :aria-current="link.active ? 'page' : undefined"
                        >{{ link.label.replace(/&[^;]+;/g, "") }}</Link
                    ></template
                >
            </div>
        </section>
        <section v-if="tab === 'plans'" class="subscription-grid">
            <div class="saas-card">
                <h2>
                    {{
                        editId
                            ? L("تعديل الباقة", "Edit plan")
                            : L("إضافة باقة", "Add plan")
                    }}
                </h2>
                <p class="saas-muted">
                    {{
                        L(
                            "السعر الفارغ يعني عرض سعر حسب الطلب. النشر يعرض الباقة على صفحة البيع. لا ينشئ ذلك تحصيلاً أو فاتورة.",
                            "A blank price means quote on request. Publishing shows the plan on the landing page. It creates no charge or invoice.",
                        )
                    }}
                </p>
                <form @submit.prevent="save" class="saas-form">
                    <div class="saas-two">
                        <label
                            >{{ L("الاسم بالعربية", "Arabic name")
                            }}<input
                                v-model="plan.name_ar"
                                required
                                maxlength="120" /></label
                        ><label
                            >{{ L("الاسم بالإنجليزية", "English name")
                            }}<input
                                v-model="plan.name_en"
                                required
                                maxlength="120"
                                dir="ltr"
                        /></label>
                    </div>
                    <label
                        >{{ L("الوصف بالعربية", "Arabic description")
                        }}<textarea
                            v-model="plan.description_ar"
                            maxlength="500"
                        /></label
                    ><label
                        >{{ L("الوصف بالإنجليزية", "English description")
                        }}<textarea
                            v-model="plan.description_en"
                            maxlength="500"
                            dir="ltr"
                        />
                    </label>
                    <div class="saas-two">
                        <label
                            >{{
                                L(
                                    "السعر شامل الضرائب والرسوم إن وجدت",
                                    "Price including applicable taxes & fees",
                                )
                            }}<input
                                v-model="plan.price"
                                inputmode="decimal"
                                placeholder="0.00" /></label
                        ><label
                            >{{ L("العملة", "Currency")
                            }}<select v-model="plan.currency">
                                <option
                                    v-for="c in [
                                        'EGP',
                                        'SAR',
                                        'AED',
                                        'QAR',
                                        'USD',
                                    ]"
                                >
                                    {{ c }}
                                </option>
                            </select></label
                        >
                    </div>
                    <label
                        >{{ L("الفترة", "Billing period")
                        }}<select v-model="plan.interval">
                            <option value="monthly">
                                {{ L("شهري", "Monthly") }}
                            </option>
                            <option value="yearly">
                                {{ L("سنوي", "Yearly") }}
                            </option>
                        </select></label
                    ><label
                        >{{
                            L(
                                "المزايا بالعربية، ميزة في كل سطر",
                                "Arabic features, one per line",
                            )
                        }}<textarea v-model="featuresAr" rows="4" /></label
                    ><label
                        >{{
                            L(
                                "المزايا بالإنجليزية، ميزة في كل سطر",
                                "English features, one per line",
                            )
                        }}<textarea
                            v-model="featuresEn"
                            rows="4"
                            dir="ltr"
                        /></label
                    ><label
                        >{{ L("ترتيب العرض", "Display order")
                        }}<input
                            type="number"
                            v-model="plan.sort"
                            min="0"
                            max="999" /></label
                    ><label class="saas-check"
                        ><input type="checkbox" v-model="plan.published" />{{
                            L(
                                "نشر الباقة على صفحة البيع",
                                "Publish plan on landing page",
                            )
                        }}</label
                    >
                    <p
                        v-for="e in plan.errors"
                        role="alert"
                        class="text-red-700"
                    >
                        {{ e }}
                    </p>
                    <button class="saas-primary" :disabled="plan.processing">
                        {{ L("حفظ الباقة", "Save plan") }}</button
                    ><button
                        type="button"
                        v-if="editId"
                        @click="
                            editId = null;
                            plan.reset();
                        "
                        class="saas-secondary"
                    >
                        {{ L("إلغاء التعديل", "Cancel edit") }}
                    </button>
                </form>
            </div>
            <div class="saas-card">
                <h2>{{ L("دليل الباقات", "Plan catalog") }}</h2>
                <p v-if="!plans.length">
                    {{
                        L(
                            "أضف باقة وحدد سعرها وشروط تفعيلها، ثم انشرها عندما تكون جاهزة.",
                            "Add a plan with its price and activation terms, then publish it when ready.",
                        )
                    }}
                </p>
                <div v-for="p in plans" class="platform-row">
                    <div>
                        <strong>{{
                            locale === "ar" ? p.name_ar : p.name_en
                        }}</strong
                        ><small
                            >{{
                                p.price_minor === null
                                    ? L("عرض سعر حسب الطلب", "Quote on request")
                                    : (p.price_minor / 100).toFixed(2) +
                                      " " +
                                      p.currency
                            }}
                            ·
                            {{
                                p.published
                                    ? L("منشورة", "Published")
                                    : L("مسودة", "Draft")
                            }}</small
                        >
                    </div>
                    <button class="saas-secondary" @click="edit(p)">
                        {{ L("تعديل", "Edit") }}
                    </button>
                </div>
            </div>
        </section>
        <section v-if="tab === 'inquiries'" class="saas-card">
            <h2>
                {{ L("طلبات البيع والتفعيل", "Sales & activation requests") }}
            </h2>
            <article v-for="i in inquiries.data" class="platform-inquiry">
                <div class="platform-row">
                    <div>
                        <strong>{{ i.nursery_name }}</strong
                        ><small
                            >{{ i.name }} · #{{ i.id }} ·
                            {{
                                new Date(i.created_at).toLocaleDateString()
                            }}</small
                        >
                    </div>
                    <select
                        :value="i.status"
                        :aria-label="L('مرحلة الطلب', 'Inquiry stage')"
                        @change="
                            router.put(
                                route('platform.inquiries.update', i.id),
                                { status: $event.target.value },
                                { preserveScroll: true, preserveState: true },
                            )
                        "
                    >
                        <option
                            v-for="s in [
                                'new',
                                'contacted',
                                'qualified',
                                'closed',
                            ]"
                            :value="s"
                        >
                            {{ statusName(s) }}
                        </option>
                    </select>
                </div>
                <p dir="ltr" class="text-start break-words">
                    {{ i.email }} {{ i.phone }}
                </p>
                <p class="whitespace-pre-wrap break-words">{{ i.message }}</p>
                <small
                    >{{
                        i.tenant_id
                            ? L("حضانة مسجلة", "Registered nursery") +
                              " #" +
                              i.tenant_id
                            : L("استفسار من صفحة البيع", "Landing-page inquiry")
                    }}
                    ·
                    {{
                        i.saas_plan_id
                            ? L("الباقة", "Plan") + " #" + i.saas_plan_id
                            : L("دون باقة", "No plan")
                    }}</small
                >
            </article>
            <p v-if="!inquiries.data.length">
                {{ L("لا توجد طلبات بعد.", "No requests yet.") }}
            </p>
            <div class="platform-pagination">
                <template v-for="l in inquiries.links"
                    ><Link v-if="l.url" :href="l.url" preserve-state>{{
                        l.label.replace(/&[^;]+;/g, "")
                    }}</Link></template
                >
            </div>
        </section>
        <section v-if="tab === 'audit'" class="saas-card">
            <h2>{{ L("آخر ٣٠ إجراء إداري", "Latest 30 admin actions") }}</h2>
            <div v-for="a in audits" class="platform-row">
                <code>{{ a.action }} #{{ a.subject_id || "" }}</code
                ><span>{{ new Date(a.created_at).toLocaleString() }}</span>
            </div>
            <p v-if="!audits.length">
                {{ L("لا توجد إجراءات مسجلة.", "No actions recorded.") }}
            </p>
        </section>
        <div
            v-if="selected"
            class="saas-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subscription-heading"
        >
            <section class="saas-card">
                <button
                    type="button"
                    @click="selected = null"
                    class="saas-close"
                    :aria-label="L('إغلاق', 'Close')"
                >
                    ×
                </button>
                <h2 id="subscription-heading">{{ selected.name }}</h2>
                <form
                    class="saas-form"
                    @submit.prevent="
                        sub.put(
                            route('platform.subscriptions.update', selected.id),
                            {
                                preserveScroll: true,
                                onSuccess: () => (selected = null),
                            },
                        )
                    "
                >
                    <label
                        >{{ L("الحالة", "Status")
                        }}<select v-model="sub.status">
                            <option
                                v-for="s in [
                                    'pending',
                                    'active',
                                    'past_due',
                                    'suspended',
                                ]"
                                :value="s"
                            >
                                {{ statusName(s) }}
                            </option>
                        </select></label
                    ><label
                        >{{ L("الباقة", "Plan")
                        }}<select v-model="sub.saas_plan_id">
                            <option value="">
                                {{ L("دون باقة", "No plan") }}
                            </option>
                            <option v-for="p in plans" :value="p.id">
                                {{ p.name_ar }}
                            </option>
                        </select></label
                    ><label
                        >{{
                            L(
                                "نهاية الفترة (توقيت الخادم UTC)، فارغ دون انتهاء",
                                "Period ends (server UTC), blank for no expiry",
                            )
                        }}<input
                            type="datetime-local"
                            v-model="sub.period_ends_at" /></label
                    ><label
                        >{{ L("ملاحظة داخلية", "Internal note")
                        }}<textarea
                            v-model="sub.operator_note"
                            maxlength="2000"
                        /></label
                    ><label class="saas-check"
                        ><input
                            type="checkbox"
                            v-model="sub.confirm"
                            required
                        />{{
                            L(
                                "أؤكد تغيير وصول الحضانة. هذا يسجل قراراً إدارياً ولا يحصّل دفعة.",
                                "I confirm changing nursery access. This records an admin decision and does not collect payment.",
                            )
                        }}</label
                    >
                    <p
                        v-for="e in sub.errors"
                        role="alert"
                        class="text-red-700"
                    >
                        {{ e }}
                    </p>
                    <button class="saas-primary" :disabled="sub.processing">
                        {{ L("حفظ حالة الاشتراك", "Save subscription state") }}
                    </button>
                </form>
            </section>
        </div></PlatformLayout
    >
</template>
