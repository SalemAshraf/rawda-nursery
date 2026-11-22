<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, watch, nextTick, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    nursery: Object,
    progress: Object,
    financial_locked: Boolean,
    dates_locked: Boolean,
    writable: Boolean,
});
const { locale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const form = useForm({
    action: "details",
    name: props.nursery.name,
    timezone: props.nursery.timezone,
    currency: props.nursery.currency,
});
const finish = useForm({ action: "finish", safety_review: false });
const steps = computed(() => [
    L("بيانات الحضانة", "Nursery details"),
    L("الفصول والسعة", "Classrooms & capacity"),
    L("أول طفل وولي أمر", "First child & guardian"),
    L("قائمة الاستلام", "Pickup permissions"),
    L("مراجعة البداية", "Ready-to-start review"),
]);
const current = computed(() => Number(props.nursery.setup_step || 0));
const ready = computed(
    () =>
        props.progress.rooms > 0 &&
        props.progress.children > 0 &&
        props.progress.pickup_gaps === 0 &&
        props.progress.guardian_gaps === 0,
);
const scrollStep = () =>
    nextTick(() =>
        document
            .querySelector('.setup-steps [aria-current="step"]')
            ?.scrollIntoView({ block: "nearest", inline: "center" }),
    );
onMounted(scrollStep);
watch(current, scrollStep);
function go(i) {
    router.post(
        route("setup.update"),
        { action: "step", step: i },
        { preserveScroll: true },
    );
}
const complete = (i) =>
    i === 0
        ? !!props.nursery.name
        : i === 1
          ? props.progress.rooms > 0
          : i === 2
            ? props.progress.children > 0 && props.progress.guardian_gaps === 0
            : i === 3
              ? props.progress.children > 0 && props.progress.pickup_gaps === 0
              : !!props.nursery.setup_completed_at;
</script>
<template>
    <Head
        :title="L('دليل إعداد الحضانة', 'Nursery setup guide')"
    /><AuthenticatedLayout
        ><div class="ops-page setup-page">
            <header class="ops-heading">
                <div>
                    <p class="eyebrow">
                        {{ L("خطوة بخطوة، على راحتك", "One step at a time") }}
                    </p>
                    <h1>
                        {{
                            L(
                                "لنجهز يومك الأول في Nursery",
                                "Let’s prepare your first nursery day",
                            )
                        }}
                    </h1>
                    <p>
                        {{
                            L(
                                "تُحفظ خطوتك تلقائياً. ارجع هنا من القائمة في أي وقت.",
                                "Your place is saved. Return from the menu any time.",
                            )
                        }}
                    </p>
                </div>
                <button
                    class="saas-secondary"
                    @click="
                        router.post(route('setup.update'), { action: 'later' })
                    "
                >
                    {{ L("أكمل لاحقاً", "Continue later") }}
                </button>
            </header>
            <p v-if="nursery.setup_completed_at" class="saas-notice">
                {{
                    L(
                        "أكملت الدليل من قبل. يمكنك مراجعته دون حذف بياناتك أو إعادة إعداد الحضانة.",
                        "You have completed this guide. Review it without deleting or resetting nursery data.",
                    )
                }}
            </p>
            <div class="setup-layout">
                <nav
                    class="setup-steps"
                    :aria-label="L('خطوات الإعداد', 'Setup steps')"
                >
                    <button
                        v-for="(s, i) in steps"
                        :key="i"
                        @click="go(i)"
                        :class="{ current: current === i, done: complete(i) }"
                        :aria-current="current === i ? 'step' : undefined"
                    >
                        <span>{{ complete(i) ? "✓" : i + 1 }}</span>
                        <div>
                            {{ s
                            }}<small>{{
                                complete(i)
                                    ? L(
                                          "متاح في السجلات",
                                          "Present in your records",
                                      )
                                    : L("خطوة للإعداد", "Setup step")
                            }}</small>
                        </div>
                    </button>
                </nav>
                <section class="saas-card setup-content">
                    <p class="eyebrow">
                        {{ L("الخطوة", "Step") }} {{ current + 1 }}
                        {{ L("من", "of") }} 5
                    </p>
                    <h2>{{ steps[current] }}</h2>
                    <template v-if="current === 0"
                        ><p>
                            {{
                                L(
                                    "بيانات المساحة التي سيعمل عليها الفريق. نحافظ على توقيت وعملة السجلات الموجودة.",
                                    "Your team’s workspace details. Existing record currencies and dates are protected.",
                                )
                            }}
                        </p>
                        <form
                            class="saas-form"
                            @submit.prevent="form.post(route('setup.update'))"
                        >
                            <label
                                >{{ L("اسم الحضانة", "Nursery name")
                                }}<input
                                    v-model="form.name"
                                    required
                                    maxlength="180"
                            /></label>
                            <div class="saas-two">
                                <label
                                    >{{ L("التوقيت", "Timezone")
                                    }}<select
                                        v-model="form.timezone"
                                        :disabled="dates_locked"
                                    >
                                        <option
                                            v-for="z in [
                                                'Africa/Cairo',
                                                'Asia/Riyadh',
                                                'Asia/Dubai',
                                                'Asia/Kuwait',
                                                'Asia/Qatar',
                                                'Asia/Bahrain',
                                                'Asia/Muscat',
                                            ]"
                                        >
                                            {{ z }}
                                        </option>
                                    </select></label
                                ><label
                                    >{{ L("عملة الرسوم", "Fee currency")
                                    }}<select
                                        v-model="form.currency"
                                        :disabled="financial_locked"
                                    >
                                        <option
                                            v-for="c in [
                                                'EGP',
                                                'SAR',
                                                'AED',
                                                'QAR',
                                            ]"
                                        >
                                            {{ c }}
                                        </option>
                                        <option
                                            v-if="
                                                ![
                                                    'EGP',
                                                    'SAR',
                                                    'AED',
                                                    'QAR',
                                                ].includes(nursery.currency)
                                            "
                                        >
                                            {{ nursery.currency }}
                                        </option>
                                    </select></label
                                >
                            </div>
                            <p class="saas-muted">
                                {{
                                    L(
                                        "لا يغيّر الدليل عملة فواتير قائمة أو توقيت سجلات حضور ورعاية موجودة. العملات ذات ثلاث منازل عشرية تحتاج مراجعة منفصلة قبل الاستخدام المالي.",
                                        "This guide does not reinterpret existing invoice currencies or attendance dates. Three-decimal currencies require a separate review before financial use.",
                                    )
                                }}
                            </p>
                            <p
                                v-for="e in form.errors"
                                class="text-red-700"
                                role="alert"
                            >
                                {{ e }}
                            </p>
                            <button
                                class="saas-primary"
                                :disabled="form.processing"
                            >
                                {{ L("حفظ ومتابعة", "Save & continue") }}
                            </button>
                        </form></template
                    ><template v-else-if="current === 1"
                        ><p>
                            {{
                                L(
                                    "أضف فصلاً وحدد سعته التشغيلية. السعة مؤشر داخلي وليست اعتماداً لنسبة إشراف أو ترخيص.",
                                    "Add a classroom and its operating capacity. Capacity is an internal indicator, not a staffing or licensing certification.",
                                )
                            }}
                        </p>
                        <div class="setup-metric">
                            <strong>{{ progress.rooms }}</strong>{{
                                L("فصول مسجلة", "classrooms recorded")
                            }}
                        </div>
                        <Link
                            v-if="writable"
                            :href="route('classrooms.index')"
                            class="saas-primary"
                            >{{
                                L(
                                    "إدارة الفصول والسعة",
                                    "Manage classrooms & capacity",
                                )
                            }}
                            ↗</Link
                        ></template
                    ><template v-else-if="current === 2"
                        ><p>
                            {{
                                L(
                                    "أضف ملف طفل وربطه بولي الأمر، أو استخدم طلب قبول مؤكد. إضافة ولي أمر لا تمنحه تلقائياً صلاحية الاستلام.",
                                    "Create a child record and link a guardian, or enroll a confirmed admissions inquiry. A guardian link does not automatically authorize pickup.",
                                )
                            }}
                        </p>
                        <div class="setup-metric">
                            <strong>{{ progress.children }}</strong>{{
                                L("أطفال نشطون", "active children")
                            }}
                        </div>
                        <Link
                            v-if="writable"
                            :href="route('children.index')"
                            class="saas-primary"
                            >{{
                                L(
                                    "ملفات الأطفال وأولياء الأمور",
                                    "Children & guardian records",
                                )
                            }}
                            ↗</Link
                        ></template
                    ><template v-else-if="current === 3"
                        ><p>
                            {{
                                L(
                                    "افتح ملف كل طفل واعتمد الأشخاص المصرح لهم بالاستلام. يجب على الفريق التحقق من الهوية عند الباب، حتى لو ظهر الاسم في النظام.",
                                    "Open each child record and authorize pickup people. Staff must check physical identity at the door, even when a person appears in the system.",
                                )
                            }}
                        </p>
                        <div class="setup-metric">
                            <strong>{{ progress.pickup_gaps }}</strong>{{
                                L(
                                    "أطفال نشطون دون مستلم معتمد",
                                    "active children without authorized pickup",
                                )
                            }}
                        </div>
                        <Link
                            v-if="writable"
                            :href="route('children.index')"
                            class="saas-primary"
                            >{{
                                L(
                                    "مراجعة ملفات الاستلام",
                                    "Review pickup records",
                                )
                            }}
                            ↗</Link
                        ></template
                    ><template v-else
                        ><p>
                            {{
                                L(
                                    "قبل بدء التشغيل، راجع الحساسية والأدوار والسعة مع الفريق. اكتمال الدليل لا يعني جاهزية قانونية أو مراجعة طبية.",
                                    "Before operating, review allergies, staff roles and capacity. Finishing this guide is not legal or medical clearance.",
                                )
                            }}
                        </p>
                        <div class="flex flex-wrap gap-3 mt-6">
                            <Link
                                :href="route('staff.index')"
                                class="saas-secondary"
                                >{{
                                    L(
                                        "فريق العمل والصلاحيات",
                                        "Staff & permissions",
                                    )
                                }}</Link><Link
                                :href="route('attendance.index')"
                                class="saas-secondary"
                                >{{
                                    L("الحضور والاستلام", "Attendance & pickup")
                                }}</Link
                            >
                        </div>
                        <form
                            class="saas-form"
                            @submit.prevent="finish.post(route('setup.update'))"
                        >
                            <label class="saas-check"
                                ><input
                                    type="checkbox"
                                    v-model="finish.safety_review"
                                    required
                                />{{
                                    L(
                                        "راجعت خطوات الحساسية والاستلام وأعرف أن التحقق من الهوية مسؤولية الفريق.",
                                        "I reviewed allergy and pickup steps and understand that staff must verify identity.",
                                    )
                                }}</label
                            >
                            <p v-if="!ready" class="saas-muted">
                                {{
                                    L(
                                        "لإكمال الدليل، أضف فصلاً وطفلاً وأكمل قوائم الاستلام لكل طفل نشط. يمكنك المتابعة لاحقاً.",
                                        "To finish, add a classroom and a child, and complete pickup lists for all active children. You can continue later.",
                                    )
                                }}
                            </p>
                            <p
                                v-for="e in finish.errors"
                                class="text-red-700"
                                role="alert"
                            >
                                {{ e }}
                            </p>
                            <button
                                class="saas-primary"
                                :disabled="!ready || finish.processing"
                            >
                                {{
                                    L(
                                        "إنهاء الدليل وفتح اللوحة",
                                        "Finish guide & open overview",
                                    )
                                }}
                            </button>
                        </form></template
                    >
                    <p
                        v-if="!writable && current > 0 && current < 4"
                        class="saas-notice mt-6"
                    >
                        {{
                            L(
                                "مساحتك بانتظار التفعيل. يمكنك مراجعة الدليل الآن وطلب التفعيل من الاشتراك قبل إضافة السجلات.",
                                "Your workspace awaits activation. Review the guide now and request activation before adding records.",
                            )
                        }}
                        <Link
                            :href="route('subscription.index')"
                            class="underline"
                            >{{ L("الاشتراك", "Subscription") }}</Link
                        >
                    </p>
                    <footer v-if="current > 0" class="setup-footer">
                        <button class="saas-secondary" @click="go(current - 1)">
                            {{ L("السابق", "Back") }}</button><button
                            v-if="current < 4"
                            class="saas-primary"
                            @click="go(current + 1)"
                        >
                            {{ L("الخطوة التالية", "Next step") }}
                        </button>
                    </footer>
                </section>
            </div>
        </div></AuthenticatedLayout
    >
</template>
