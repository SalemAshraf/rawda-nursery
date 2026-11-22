<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    enabled: Boolean,
    eligible: Boolean,
    endpoint: String,
    connections: Array,
});
const { locale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const form = useForm({ enabled: props.enabled, consent: false, password: "" });
function save() {
    form.post(route("ai.update"), {
        preserveScroll: true,
        onSuccess: () => form.reset("password", "consent"),
    });
}
</script>
<template>
    <Head :title="L('ربط Claude', 'Claude connection')" />
    <AuthenticatedLayout>
        <div class="ai-page" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <p class="eyebrow">NURSERY × CLAUDE</p>
            <h1>
                {{
                    L(
                        "اسأل عن يوم الحضانة. بالأرقام فقط.",
                        "Ask about the nursery day. Numbers only.",
                    )
                }}
            </h1>
            <p class="intro">
                {{
                    L(
                        "اربط Claude بتقارير حضانتك المجمعة، بعد اختيارك وموافقتك. الوصول للمالك فقط، وللقراءة فقط.",
                        "Connect Claude to aggregate nursery reports, only after you enable access and approve a connection. Owner-only, read-only access.",
                    )
                }}
            </p>
            <div class="ai-grid">
                <section class="ai-card">
                    <h2>
                        {{
                            L("حدود واضحة للخصوصية", "Clear privacy boundaries")
                        }}
                    </h2>
                    <p>
                        {{
                            L(
                                "المتاح: إجماليات الحضور وسعة الفصول والقبول والرسوم والتحصيل اليدوي. لا يرسل الربط أسماء الأطفال أو أولياء الأمور أو أرقامهم أو بيانات الصحة أو الاستلام أو الحوادث أو الملاحظات الحرة.",
                                "Available: attendance, classroom capacity, admissions and manual fee totals. The connector excludes child and guardian names, contacts, health records, pickup details, incidents and free-text notes.",
                            )
                        }}
                    </p>
                    <p>
                        {{
                            L(
                                "بعد موافقتك، تصل الإجماليات التي يطلبها Claude إلى مزود الخدمة الخارجي وتخضع لشروط حسابك لديه. راجع سياسة حضانتك قبل التفعيل. تعطيل الربط يوقف الوصول المستقبلي، ولا يحذف محادثات سبق إرسالها إلى Claude.",
                                "After consent, aggregates requested by Claude are shared with an external provider under your account terms. Check your nursery policy before enabling. Disconnecting stops future access but does not erase past Claude conversations.",
                            )
                        }}
                    </p>
                    <form @submit.prevent="save">
                        <label class="check"
                            ><input type="checkbox" v-model="form.enabled" />{{
                                L(
                                    "السماح بربط Claude",
                                    "Allow Claude connections",
                                )
                            }}</label
                        >
                        <label v-if="form.enabled" class="check"
                            ><input type="checkbox" v-model="form.consent" />{{
                                L(
                                    "أوافق على مشاركة هذه الإجماليات مع تطبيق AI بعد موافقة منفصلة لكل اتصال.",
                                    "I agree to share these aggregates with an AI app after separate connection consent.",
                                )
                            }}</label
                        >
                        <label
                            >{{
                                L(
                                    "كلمة مرور حسابك للتأكيد",
                                    "Confirm with your account password",
                                )
                            }}<input
                                type="password"
                                autocomplete="current-password"
                                v-model="form.password"
                                required
                        /></label>
                        <p
                            v-for="(err, key) in form.errors"
                            :key="key"
                            class="error"
                        >
                            {{ err }}
                        </p>
                        <button
                            class="saas-primary"
                            :disabled="form.processing"
                        >
                            {{ L("حفظ الاختيار", "Save preference") }}
                        </button>
                    </form>
                    <p class="status">
                        {{
                            enabled
                                ? L(
                                      "السماح مفعّل. ما زالت موافقة الاتصال مطلوبة.",
                                      "Connections enabled. Each connection still needs consent.",
                                  )
                                : L(
                                      "الربط معطّل افتراضياً.",
                                      "Connections are off by default.",
                                  )
                        }}
                    </p>
                    <p v-if="enabled && !eligible" class="error">
                        {{
                            L(
                                "الوصول متوقف بسبب حالة الاشتراك. يظل إلغاء الاتصال متاحاً.",
                                "Access is paused by subscription status. Revocation remains available.",
                            )
                        }}
                    </p>
                </section>
                <section class="ai-card steps">
                    <h2>
                        {{
                            L(
                                "اربطه من حسابك في Claude",
                                "Connect from your Claude account",
                            )
                        }}
                    </h2>
                    <ol>
                        <li>
                            {{
                                L(
                                    "فعّل السماح هنا بحساب مالك الحضانة.",
                                    "Enable connections here as the nursery owner.",
                                )
                            }}
                        </li>
                        <li>
                            {{
                                L(
                                    "من إعدادات الموصلات في Claude، أضف موصلاً مخصصاً بهذا العنوان:",
                                    "In Claude connector settings, add a custom connector using this address:",
                                )
                            }}<code>{{ endpoint }}</code>
                        </li>
                        <li>
                            {{
                                L(
                                    "سجّل الدخول إلى Nursery وراجع اسم الحضانة والإجماليات قبل الموافقة.",
                                    "Sign in to Nursery and review the nursery name and aggregate scope before allowing access.",
                                )
                            }}
                        </li>
                        <li>
                            {{
                                L(
                                    "ابدأ بسؤال مثل: ما إجمالي حضور اليوم؟ أو كم حصلنا هذا الشهر؟",
                                    "Start with: How many arrivals today? Or: How much did we collect this month?",
                                )
                            }}
                        </li>
                    </ol>
                    <p>
                        {{
                            L(
                                "يلزم حساب Claude يدعم الموصلات المخصصة. أضف الاتصال من حسابك في Claude، ثم قارن أول إجابة بتقرير Nursery للتأكد من الإعداد.",
                                "A Claude account with custom connector support is required. Add the connection from Claude, then compare your first answer with a Nursery report to check the setup.",
                            )
                        }}
                    </p>
                    <p>
                        {{
                            L(
                                "راجع الأرقام في تقارير Nursery. لا يستخدم لاتخاذ قرار طبي أو إجراء استلام أو إرسال رسائل أو تعديل سجل.",
                                "Check answers against Nursery reports. Do not use it for medical decisions, child handovers, messaging or record changes.",
                            )
                        }}
                    </p>
                    <details class="connection-help">
                        <summary>{{ L('متطلبات الربط واستخدامه', 'Connection requirements and use') }}</summary>
                        <p>{{ L('المحادثة تتم في حسابك لدى Claude، لا داخل Nursery. إتاحة الموصلات تخضع لحسابك وشروط المزود. إذا تعذر إتمام الربط، راجع دعم الموصلات في حسابك، ثم عنوان الاتصال وموافقة مالك الحضانة. يمكنك إلغاء الوصول من قائمة الاتصالات أدناه.', 'Chat takes place in your Claude account, not inside Nursery. Connector access depends on your account and provider terms. If setup cannot be completed, check connector support in your account, then the endpoint and nursery owner consent. You can revoke access in the connections list below.') }}</p>
                    </details>
                </section>
            </div>
            <section class="ai-card connection-list">
                <h2>
                    {{ L("الاتصالات المسموح لها", "Approved connections") }}
                </h2>
                <p v-if="!connections.length">
                    {{ L("لا توجد اتصالات نشطة.", "No active connections.") }}
                </p>
                <div
                    v-for="c in connections"
                    :key="c.family"
                    class="connection"
                >
                    <div>
                        <strong>Claude</strong>
                        <p>
                            {{ c.owner }} ·
                            <time dir="ltr">{{ c.connected_at }}</time>
                        </p>
                        <small
                            >{{ L("آخر استخدام:", "Last used:") }}
                            {{
                                c.last_used_at ||
                                L("لم يستخدم بعد", "Not used yet")
                            }}</small
                        >
                    </div>
                    <button
                        class="saas-secondary"
                        @click="
                            router.delete(route('ai.revoke', c.family), {
                                preserveScroll: true,
                            })
                        "
                    >
                        {{ L("إلغاء الاتصال", "Disconnect") }}
                    </button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
.connection-help { margin-top: 18px; border-top: 1px solid #e0e8e2; padding-top: 16px; }
.connection-help summary { cursor: pointer; font-size: 14px; font-weight: 700; color: #35533d; }
.ai-page {
    max-width: 1120px;
    margin: auto;
    padding: 24px;
}
.eyebrow {
    color: #456d59;
    font-weight: 800;
    letter-spacing: 0.12em;
}
h1 {
    font-size: clamp(25px, 3vw, 38px);
    line-height: 1.6;
    margin: 10px 0;
}
.intro {
    max-width: 820px;
    color: #66746c;
    line-height: 1.9;
    margin-bottom: 24px;
}
.ai-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.ai-card {
    background: white;
    border: 1px solid #dfe8e0;
    border-radius: 20px;
    padding: 26px;
}
.ai-card h2 {
    font-size: 20px;
    margin-bottom: 16px;
}
.ai-card p,
.ai-card li {
    font-size: 14px;
    line-height: 1.95;
    color: #536258;
}
.ai-card p {
    margin: 12px 0;
}
.ai-card form {
    display: grid;
    gap: 16px;
    margin-top: 24px;
}
.check {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    line-height: 1.9;
    font-size: 14px;
}
.check input {
    margin-top: 6px;
    flex-shrink: 0;
}
input[type="password"] {
    display: block;
    width: 100%;
    border: 1px solid #cbd8ce;
    border-radius: 10px;
    margin-top: 8px;
}
code {
    display: block;
    direction: ltr;
    text-align: left;
    overflow-wrap: anywhere;
    background: #f1f6f2;
    border-radius: 10px;
    padding: 14px;
    margin: 12px 0;
    font-size: 13px;
}
.steps ol {
    list-style: decimal;
    padding-inline-start: 20px;
}
.steps li {
    padding-inline-start: 4px;
    margin: 15px 0;
}
.status {
    font-weight: 700;
}
.error {
    color: #a43636 !important;
}
.connection-list {
    margin-top: 20px;
}
.connection {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 18px 0;
    border-top: 1px solid #e0e8e2;
}
.connection p {
    overflow-wrap: anywhere;
}
button:disabled {
    opacity: 0.5;
}
@media (max-width: 760px) {
    .ai-page {
        padding: 14px;
    }
    .ai-grid {
        grid-template-columns: 1fr;
    }
    .ai-card {
        padding: 20px;
    }
    .connection {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
