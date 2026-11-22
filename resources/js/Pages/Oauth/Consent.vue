<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { useI18n } from "@/lib/i18n";
const props = defineProps({
    rid: String,
    blocked: String,
    app: String,
    redirectHost: String,
    user: Object,
    nursery: String,
});
const { locale, toggleLocale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const form = useForm({ rid: props.rid, decision: "deny" });
function decide(value) {
    form.decision = value;
    form.post(route("oauth.decide"));
}
</script>
<template>
    <Head :title="L('موافقة ربط AI', 'AI connection consent')" />
    <main class="consent" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
        <ApplicationLogo class="logo" /><button
            class="language"
            @click="toggleLocale"
        >
            {{ locale === "ar" ? "English" : "العربية" }}
        </button>
        <p class="eyebrow">
            {{ L("مراجعة إذن القراءة", "Review read permission") }}
        </p>
        <h1>
            {{
                L(
                    "هل تسمح لهذا التطبيق بقراءة الإجماليات؟",
                    "Allow this app to read aggregates?",
                )
            }}
        </h1>
        <div class="identity">
            <strong>{{ nursery }}</strong>
            <p>{{ user.name }} · {{ user.email }}</p>
        </div>
        <p>
            {{ L("اسم التطبيق الذي سجّل نفسه:", "Self-registered app name:") }}
            <strong>{{ app }}</strong><br />{{
                L("عنوان العودة:", "Return host:")
            }}
            <b dir="ltr">{{ redirectHost }}</b>
        </p>
        <p class="notice">
            {{
                L(
                    "اسم التطبيق يقدمه الطرف الطالب، وليس إثبات هوية من Nursery. وافق فقط إذا بدأت الربط بنفسك من حساب Claude المقصود.",
                    "The requesting app supplies its own name; Nursery does not verify that identity. Approve only if you started this connection from your intended Claude account.",
                )
            }}
        </p>
        <h2>{{ L("ما سيمكنه قراءته", "What it can read") }}</h2>
        <ul>
            <li>
                {{
                    L(
                        "إجماليات الحضور والقبول وسعة الفصول.",
                        "Attendance, admissions and classroom capacity totals.",
                    )
                }}
            </li>
            <li>
                {{
                    L(
                        "إجماليات الرسوم والتحصيل اليدوي والمتبقي.",
                        "Manual fee, collection and outstanding totals.",
                    )
                }}
            </li>
        </ul>
        <div class="excluded">
            {{
                L(
                    "لا أسماء أو اتصالات للأطفال أو أولياء الأمور. لا بيانات صحة أو استلام أو حوادث أو ملاحظات حرة. لا تعديل أو رسائل أو دفع.",
                    "No child or guardian identities or contacts. No health, pickup, incident or free-text records. No changes, messages or payments.",
                )
            }}
        </div>
        <p>
            {{
                L(
                    "ستصل الأرقام المطلوبة إلى مزود AI الخارجي وفق شروط حسابك. يمكنك إلغاء الوصول المستقبلي من صفحة ربط Claude. الإلغاء لا يمس محادثات سبق إرسالها.",
                    "Requested aggregates go to the external AI provider under your account terms. Revoke future access on the Claude connection page. Revocation does not remove conversations already shared.",
                )
            }}
        </p>
        <p v-if="blocked" class="blocked">
            {{
                L(
                    "الموافقة غير متاحة. يلزم مالك الحضانة مع تفعيل الربط واشتراك يسمح بالوصول.",
                    "Approval is unavailable. The nursery owner must enable connections and have eligible subscription access.",
                )
            }}
            <Link :href="route('ai.index')">{{
                L("إعدادات الربط", "Connection settings")
            }}</Link>
        </p>
        <div class="actions">
            <button
                v-if="!blocked"
                class="allow"
                :disabled="form.processing"
                @click="decide('allow')"
            >
                {{
                    L("أوافق على قراءة الإجماليات", "Allow aggregate reading")
                }}</button
            ><button :disabled="form.processing" @click="decide('deny')">
                {{ L("رفض والعودة", "Deny and return") }}
            </button>
        </div>
        <p v-for="e in form.errors" :key="e">{{ e }}</p>
    </main>
</template>
<style scoped>
.consent {
    max-width: 720px;
    margin: 40px auto;
    padding: 36px;
    background: white;
    border: 1px solid #dbe5dc;
    border-radius: 24px;
    position: relative;
}
.logo {
    width: 160px;
    margin-bottom: 30px;
}
.language {
    position: absolute;
    top: 36px;
    inset-inline-end: 36px;
    color: #42664f;
}
.eyebrow {
    font-weight: 800;
    color: #52765e;
}
h1 {
    font-size: 28px;
    line-height: 1.6;
    margin: 12px 0 20px;
}
h2 {
    font-size: 19px;
    margin-top: 22px;
}
.consent p,
.consent li,
.excluded {
    line-height: 1.9;
    font-size: 14px;
    margin: 12px 0;
    color: #4d5c51;
}
.identity,
.excluded {
    background: #eef5ef;
    padding: 16px;
    border-radius: 12px;
    overflow-wrap: anywhere;
}
.identity p {
    margin: 3px 0;
}
.notice {
    font-size: 12px !important;
}
.consent ul {
    list-style: disc;
    padding-inline-start: 24px;
}
.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 24px;
}
.actions button {
    border: 1px solid #bbcdbf;
    border-radius: 10px;
    padding: 12px 18px;
}
.actions .allow {
    color: white;
    background: #365c44;
}
.blocked {
    color: #963a28 !important;
}
button:disabled {
    opacity: 0.5;
}
@media (max-width: 760px) {
    .consent {
        margin: 14px;
        padding: 22px;
    }
    h1 {
        font-size: 24px;
    }
    .language {
        top: 22px;
        inset-inline-end: 22px;
    }
    .logo {
        width: 135px;
    }
}
</style>
