<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useI18n } from "@/lib/i18n";
const props = defineProps({ plans: Array });
const { locale, toggleLocale } = useI18n();
const L = (a, e) => (locale.value === "ar" ? a : e);
const page = usePage();
const sent = ref(false);
const form = useForm({
    nursery_name: "",
    name: "",
    email: "",
    phone: "",
    message: "",
    saas_plan_id: "",
    consent: false,
});
const features = [
    [
        "01",
        "كل طفل، بتفاصيله المهمة",
        "Every child, thoughtfully known",
        "ملف الطفل وولي الأمر والحساسية والأشخاص المصرح لهم بالاستلام، مع صلاحيات منفصلة للفريق.",
        "Child and guardian records, allergies and authorized pickup people, with separate staff permissions.",
    ],
    [
        "02",
        "صباح منظم، وتسليم موثق",
        "Calm arrivals. Recorded handovers.",
        "حضور وانصراف بتاريخ الحضانة المحلي، وسجل يوضح من استلم الطفل ومتى. يظل التحقق من الهوية مسؤولية الفريق.",
        "Attendance on your local nursery date and a record of who collected each child. Physical identity checks remain the staff’s responsibility.",
    ],
    [
        "03",
        "تفاصيل اليوم لا تضيع",
        "The little details stay together",
        "وجبات ونوم ومزاج وأنشطة وملاحظات، مع تنبيهات للسجلات الناقصة ومتابعة الحوادث داخل الحضانة.",
        "Meals, naps, mood, activities and notes, with missing-log indicators and internal incident follow-up.",
    ],
    [
        "04",
        "من الاستفسار إلى أول يوم",
        "From first inquiry to the first day",
        "تابع الاستفسارات والزيارات وقائمة الانتظار، ثم حوّل الطلب المؤكد إلى ملف طفل دون تكرار نفس الطلب.",
        "Track inquiries, visits and waitlists, then convert a confirmed inquiry into a child record without duplicating that inquiry.",
    ],
    [
        "05",
        "إدارة الرسوم بوضوح",
        "Nursery fees, clearly recorded",
        "خطط رسوم وفواتير ودفعات مسجلة يدوياً، مع متابعة المتبقي والمتأخر وتقارير التحصيل.",
        "Tuition plans, invoices and manually recorded payments, with outstanding balances and collection reports.",
    ],
    [
        "06",
        "اعرف ما يحتاج اهتمامك",
        "Know what needs attention",
        "لوحة يومية وتقارير حسب الفترة وسعة الفصول وسجل نشاط للإدارة، بالعربية والإنجليزية وعلى الهاتف.",
        "A daily overview, date-range reports, classroom capacity and management activity history, in Arabic and English on mobile too.",
    ],
];
function choose(id) {
    form.saas_plan_id = id;
    document.getElementById("contact").scrollIntoView({ behavior: "smooth" });
}
function submit() {
    form.post(route("sales.inquiry"), {
        preserveScroll: true,
        onSuccess: () => {
            sent.value = true;
            form.reset();
        },
    });
}
</script>
<template>
    <Head
        :title="
            L(
                'Nursery | يوم حضانة أهدأ، وإدارة أوضح',
                'Nursery | A calmer nursery day',
            )
        "
        ><meta
            head-key="description"
            name="description"
            :content="
                L(
                    'إدارة الحضور والرعاية والقبول والرسوم في مساحة عربية واحدة للحضانة.',
                    'Attendance, care, admissions and fee records in one Arabic-first nursery workspace.',
                )
            "
    /></Head>
    <div class="landing">
        <header class="landing-nav">
            <Link href="/" class="brand"><ApplicationLogo /></Link>
            <nav class="landing-links">
                <a href="#features">{{
                    L("داخل Nursery", "Inside Nursery")
                }}</a><a href="#ai">{{
                    L("الذكاء الاصطناعي", "AI insights")
                }}</a
                ><a href="#plans">{{ L("الباقات", "Plans") }}</a
                ><a href="#questions">{{ L("أسئلة شائعة", "Questions") }}</a>
            </nav>
            <div class="landing-nav-actions">
                <button @click="toggleLocale" class="landing-language">
                    {{ locale === "ar" ? "English" : "العربية" }}</button><Link
                    :href="route('login')"
                    class="saas-secondary"
                    >{{ L("دخول الحضانة", "Nursery login") }}</Link
                >
            </div>
        </header>
        <main>
            <section class="landing-hero">
                <div class="hero-copy">
                    <p class="landing-eyebrow">
                        <span></span
                        >{{
                            L(
                                "للحضانات ورياض الأطفال",
                                "For nurseries & preschools",
                            )
                        }}
                    </p>
                    <h1>
                        {{
                            L(
                                "مساحة أهدأ للإدارة.",
                                "A calmer place to manage.",
                            )
                        }}<br /><em>{{
                            L("ووقت أكثر للصغار.", "More time for little ones.")
                        }}</em>
                    </h1>
                    <p class="hero-description">
                        {{
                            L(
                                "من أول حضور حتى آخر استلام. اجمع يوم الحضانة وملفات الأطفال والقبول والرسوم في مساحة واحدة، بدل التفاصيل المتفرقة بين الدفاتر والجداول.",
                                "From the first arrival to the last pickup. Bring your nursery’s day, child records, admissions and fees together, instead of piecing them together across notebooks and spreadsheets.",
                            )
                        }}
                    </p>
                    <div class="hero-actions">
                        <a href="#contact" class="saas-primary"
                            >{{ L("اطلب عرضاً لحضانتك", "Request a walkthrough")
                            }}<span aria-hidden="true">↗</span></a
                        ><a href="#workspace" class="hero-text-link"
                            >{{
                                L("شاهد مساحة العمل", "Explore the workspace")
                            }}
                            ↓</a
                        >
                    </div>
                    <p class="hero-footnote">
                        {{
                            L(
                                "عربي أولاً · صلاحيات للفريق · مناسب للهاتف",
                                "Arabic-first · Staff permissions · Mobile-friendly",
                            )
                        }}
                    </p>
                </div>
                <div class="hero-product">
                    <div class="hero-orbit orbit-one"></div>
                    <div class="hero-orbit orbit-two"></div>
                    <div class="product-window">
                        <div class="window-bar">
                            <span class="window-dots">● ● ●</span
                            ><span>{{
                                L("Nursery / نظرة عامة", "Nursery / Overview")
                            }}</span
                            ><span>✳</span>
                        </div>
                        <picture>
                            <source media="(max-width: 700px)" :srcset="L('/images/dashboard-rich-mobile-ar.png', '/images/dashboard-rich-mobile-en.png')" width="390" height="1200" />
                        <img
                            :src="L('/images/dashboard-rich-ar.png', '/images/dashboard-rich-en.png')"
                            width="1440"
                            height="1400"
                            :alt="
                                L(
                                    'معاينة لوحة Nursery ببيانات تمثيلية',
                                    'Nursery dashboard preview with synthetic data',
                                )
                            "
                        />
                        </picture>
                    </div>
                </div>
            </section>
            <section class="landing-principles">
                <div>
                    <strong>{{
                        L("يوم واحد. صورة أوضح.", "One day. A clearer picture.")
                    }}</strong>
                    <p>
                        {{
                            L(
                                "إدارة مترابطة، من الاستقبال إلى التحصيل.",
                                "Connected records, from reception to collections.",
                            )
                        }}
                    </p>
                </div>
                <span>{{
                    L("الحضور والاستلام", "Attendance & pickup")
                }}</span><span>{{ L("اليوميات والرعاية", "Daily care") }}</span
                ><span>{{ L("القبول والرسوم", "Admissions & fees") }}</span>
            </section>
            <section id="features" class="landing-section">
                <div class="section-heading">
                    <p class="landing-eyebrow">
                        {{
                            L(
                                "صُمم ليوم الحضانة الحقيقي",
                                "Made for the real nursery day",
                            )
                        }}
                    </p>
                    <h2>
                        {{ L("التفاصيل الصغيرة،", "The small details,")
                        }}<br />{{
                            L("في مكانها الصحيح.", "in the right place.")
                        }}
                    </h2>
                    <p>
                        {{
                            L(
                                "أدوات واضحة للاستقبال والمعلمات والإدارة، مع فصل ما يحتاج كل دور إلى رؤيته.",
                                "Clear tools for reception, educators and management, with access shaped around each role.",
                            )
                        }}
                    </p>
                </div>
                <div class="landing-feature-grid">
                    <article v-for="f in features" :key="f[0]">
                        <span class="feature-number">{{ f[0] }}</span>
                        <h3>{{ locale === "ar" ? f[1] : f[2] }}</h3>
                        <p>{{ locale === "ar" ? f[3] : f[4] }}</p>
                    </article>
                </div>
            </section>
            <section id="ai" class="nursery-ai">
                <div class="ai-heading">
                    <p class="ai-kicker">
                        NURSERY × CLAUDE
                        <span>{{
                            L(
                                "قراءة فقط · باختيار المالك",
                                "READ ONLY · OWNER OPT-IN",
                            )
                        }}</span>
                    </p>
                    <h2>
                        {{ L("سؤال بسيط.", "A simple question.") }}<br /><em>{{
                            L(
                                "وراءه صورة أوضح.",
                                "A clearer picture behind it.",
                            )
                        }}</em>
                    </h2>
                    <p>
                        {{
                            L(
                                "اسأل Claude عن أرقام حضانتك بدل التنقل بين التقارير. ربط مخصص يقرأ إجماليات الحضور والقبول والسعة والرسوم، دون كشف ملفات الأطفال.",
                                "Ask Claude about your nursery numbers instead of moving between reports. A custom connection reads attendance, admissions, capacity and fee aggregates, without exposing child records.",
                            )
                        }}
                    </p>
                    <a :href="route('ai.index')" class="ai-link"
                        >{{
                            L("اكتشف إعداد الربط", "Explore connection setup")
                        }}
                        ↗</a
                    >
                </div>
                <div class="ai-questions">
                    <div class="ai-chat-top">
                        <span class="ai-orbit">✧</span
                        ><span
                            >{{
                                L(
                                    "أسئلة من يوم الإدارة",
                                    "Questions from a manager’s day",
                                )
                            }}<small>{{
                                L(
                                    "أمثلة للأسئلة",
                                    "Suggested questions",
                                )
                            }}</small></span
                        >
                    </div>
                    <article>
                        <b>01</b>
                        <div>
                            <h3>
                                {{
                                    L(
                                        "كم طفلاً وصل اليوم؟",
                                        "How many children arrived today?",
                                    )
                                }}
                            </h3>
                            <p>
                                {{
                                    L(
                                        "إجمالي الوصول والانصراف ومن لم يسجل انصرافه، بتاريخ الحضانة المحلي.",
                                        "Arrival, departure and not-yet-checked-out totals on the nursery’s local date.",
                                    )
                                }}
                            </p>
                        </div>
                    </article>
                    <article>
                        <b>02</b>
                        <div>
                            <h3>
                                {{
                                    L(
                                        "ما حالة طلبات القبول؟",
                                        "Where do admissions stand?",
                                    )
                                }}
                            </h3>
                            <p>
                                {{
                                    L(
                                        "عدد الاستفسارات والزيارات وقائمة الانتظار والمتابعات المستحقة، دون أسماء أو هواتف.",
                                        "Inquiry, visit, waitlist and due-follow-up counts, without names or phone numbers.",
                                    )
                                }}
                            </p>
                        </div>
                    </article>
                    <article>
                        <b>03</b>
                        <div>
                            <h3>
                                {{
                                    L(
                                        "كم بلغ التحصيل هذا الشهر؟",
                                        "How much did we collect this month?",
                                    )
                                }}
                            </h3>
                            <p>
                                {{
                                    L(
                                        "إجمالي الدفعات اليدوية والفواتير خلال الفترة، مع فصل المتبقي الحالي عن إجمالي الفترة.",
                                        "Manual collections and issued fees in the period, with current outstanding balances shown separately.",
                                    )
                                }}
                            </p>
                        </div>
                    </article>
                    <article>
                        <b>04</b>
                        <div>
                            <h3>
                                {{
                                    L(
                                        "كيف تقارن السعة بعدد الأطفال؟",
                                        "How does entered capacity compare with enrollment?",
                                    )
                                }}
                            </h3>
                            <p>
                                {{
                                    L(
                                        "مقارنة إجمالية للسعة التي أدخلتها الإدارة والأطفال المقيدين. ليست شهادة ترخيص أو التزام قانوني.",
                                        "Aggregate entered capacity and current enrollment. Not a licensing or legal compliance assessment.",
                                    )
                                }}
                            </p>
                        </div>
                    </article>
                </div>
                <div class="ai-boundary">
                    <strong>{{
                        L(
                            "خصوصية الأطفال. وتحكّم للمالك.",
                            "Child privacy. Owner control.",
                        )
                    }}</strong>
                    <p>
                        {{
                            L(
                                "تصل إلى Claude إجماليات فقط، بموافقة المالك. تبقى هويات الأطفال وأولياء الأمور وبيانات الصحة والاستلام والحوادث والملاحظات خارج الربط. الوصول للقراءة فقط ويمكن إلغاؤه في أي وقت.",
                                "Only aggregates are shared with Claude, with owner consent. Child and guardian identities, health, pickup, incident and free-text records stay outside the connection. Access is read-only and can be revoked at any time.",
                            )
                        }}
                    </p>
                    <small>{{
                        L(
                            "يتطلب حساب Claude يدعم الموصلات المخصصة، وتخضع الإجماليات المشاركة لشروط مزود الخدمة. بعد الإعداد، راجع الإجابات مع تقارير Nursery.",
                            "Requires a Claude account with custom connector support. Shared aggregates follow the provider's terms. After setup, check answers against Nursery reports.",
                        )
                    }}</small>
                </div>
            </section>
            <section id="workspace" class="landing-workflow">
                <div>
                    <p class="landing-eyebrow">
                        {{
                            L(
                                "من أول دقيقة في اليوم",
                                "From the first minute of the day",
                            )
                        }}
                    </p>
                    <h2>
                        {{
                            L(
                                "ابدأ يومك بصورة،",
                                "Start the day with a picture,",
                            )
                        }}<br />{{
                            L("لا بقائمة أسئلة.", "not a list of questions.")
                        }}
                    </h2>
                    <p>
                        {{
                            L(
                                "من وصل؟ من استلم طفله؟ أي فصل يقترب من سعته؟ وأي سجل رعاية يحتاج متابعة؟ اللوحة تجمع هذه الإشارات أمام الإدارة.",
                                "Who arrived? Who collected their child? Which classroom is near capacity? Which care records need attention? The overview brings those signals together for management.",
                            )
                        }}
                    </p>
                    <a href="#contact" class="saas-primary"
                        >{{
                            L(
                                "ناقش احتياجات حضانتك",
                                "Discuss your nursery’s needs",
                            )
                        }}
                        ↗</a
                    >
                </div>
                <ol>
                    <li>
                        <b>01</b
                        ><span
                            ><strong>{{
                                L("سجّل البداية", "Record the arrival")
                            }}</strong
                            ><small>{{
                                L(
                                    "حضور الطفل والفصل وملاحظات الحساسية.",
                                    "Child attendance, classroom and allergy information.",
                                )
                            }}</small></span
                        >
                    </li>
                    <li>
                        <b>02</b
                        ><span
                            ><strong>{{
                                L(
                                    "وثّق تفاصيل الرعاية",
                                    "Keep care details together",
                                )
                            }}</strong
                            ><small>{{
                                L(
                                    "وجبات ونوم وأنشطة ومتابعات اليوم.",
                                    "Meals, naps, activities and daily follow-ups.",
                                )
                            }}</small></span
                        >
                    </li>
                    <li>
                        <b>03</b
                        ><span
                            ><strong>{{
                                L(
                                    "اختم باستلام مسجل",
                                    "Finish with a recorded handover",
                                )
                            }}</strong
                            ><small>{{
                                L(
                                    "مستلم معتمد للطفل، وسجل واضح للفريق.",
                                    "An authorized pickup person and a clear staff record.",
                                )
                            }}</small></span
                        >
                    </li>
                </ol>
            </section>
            <section id="plans" class="landing-section">
                <div class="section-heading">
                    <p class="landing-eyebrow">
                        {{ L("الباقات والاشتراك", "Plans & subscription") }}
                    </p>
                    <h2>
                        {{
                            L(
                                "خطوة تناسب حضانتك.",
                                "A next step that fits your nursery.",
                            )
                        }}
                    </h2>
                    <p>
                        {{
                            L(
                                "اطلب توضيح الباقة وشروطها قبل التفعيل. إرسال الطلب لا يحصّل أي مبلغ.",
                                "Review a plan and its terms before activation. Submitting a request does not charge money.",
                            )
                        }}
                    </p>
                </div>
                <div v-if="plans.length" class="landing-plan-grid">
                    <article v-for="p in plans" class="landing-plan">
                        <span class="landing-eyebrow">{{
                            locale === "ar" ? p.name_ar : p.name_en
                        }}</span>
                        <h3>
                            {{
                                p.price_minor === null
                                    ? L("عرض سعر حسب الطلب", "Quote on request")
                                    : (p.price_minor / 100).toFixed(2) +
                                      " " +
                                      p.currency
                            }}
                        </h3>
                        <small v-if="p.price_minor !== null">{{
                            p.interval === "yearly"
                                ? L("سنوياً", "per year")
                                : L("شهرياً", "per month")
                        }}</small>
                        <p>
                            {{
                                locale === "ar"
                                    ? p.description_ar
                                    : p.description_en
                            }}
                        </p>
                        <ul>
                            <li
                                v-for="f in locale === 'ar'
                                    ? p.features_ar
                                    : p.features_en"
                            >
                                ✓ {{ f }}
                            </li>
                        </ul>
                        <button @click="choose(p.id)" class="saas-primary">
                            {{ L("اسأل عن هذه الباقة", "Ask about this plan") }}
                        </button>
                    </article>
                </div>
                <div v-else class="quote-card">
                    <div>
                        <h3>
                            {{
                                L(
                                    "عرض يناسب طريقة عمل حضانتك.",
                                    "A proposal for the way your nursery works.",
                                )
                            }}
                        </h3>
                        <p>
                            {{
                                L(
                                    "أخبرنا عن فريقك واحتياجاتك، لنتفق على الباقة وشروط التفعيل المناسبة قبل أي التزام مالي.",
                                    "Tell us about your team and needs. Review a suitable plan and activation terms before any financial commitment.",
                                )
                            }}
                        </p>
                    </div>
                    <a href="#contact" class="saas-primary"
                        >{{
                            L("طلب عرض للحضانة", "Request a nursery proposal")
                        }}
                        ↗</a
                    >
                </div>
            </section>
            <section id="questions" class="landing-faq landing-section">
                <div class="section-heading">
                    <p class="landing-eyebrow">
                        {{ L("قبل أن تبدأ", "Before you start") }}
                    </p>
                    <h2>{{ L("أسئلة في محلها.", "Fair questions.") }}</h2>
                </div>
                <div>
                    <details>
                        <summary>
                            {{
                                L(
                                    "هل يوجد تطبيق أو حساب لولي الأمر؟",
                                    "Is there a parent app or login?",
                                )
                            }}
                        </summary>
                        <p>
                            {{
                                L(
                                    "Nursery مساحة داخلية لفريق الحضانة. دخول أولياء الأمور والرسائل والإشعارات التلقائية لهم غير مشمولة.",
                                    "Nursery is an internal staff workspace. Parent logins and automatic parent messages or notifications are not included.",
                                )
                            }}
                        </p>
                    </details>
                    <details>
                        <summary>
                            {{
                                L(
                                    "هل تحصّل Nursery الرسوم أونلاين؟",
                                    "Does Nursery collect fees online?",
                                )
                            }}
                        </summary>
                        <p>
                            {{
                                L(
                                    "لا. تُسجّل الإدارة الدفعات يدوياً وتتابع الفواتير والأرصدة داخل Nursery؛ التحصيل الإلكتروني غير مشمول.",
                                    "No. Management records payments manually and tracks invoices and balances in Nursery; online payment collection is not included.",
                                )
                            }}
                        </p>
                    </details>
                    <details>
                        <summary>
                            {{
                                L(
                                    "هل تسجيل المستلم يكفي للتحقق من هويته؟",
                                    "Does a pickup record verify identity?",
                                )
                            }}
                        </summary>
                        <p>
                            {{
                                L(
                                    "لا. النظام يسجل صلاحية الاستلام الخاصة بكل طفل، وعلى الفريق التحقق من هوية الشخص فعلياً عند الباب.",
                                    "No. The system records each child’s pickup permissions. Staff must physically verify the person’s identity at the door.",
                                )
                            }}
                        </p>
                    </details>
                    <details>
                        <summary>
                            {{
                                L(
                                    "هل يمكن استخدام العربية والهاتف؟",
                                    "Can we use Arabic and mobile?",
                                )
                            }}
                        </summary>
                        <p>
                            {{
                                L(
                                    "نعم. مساحة العمل عربية باتجاه من اليمين لليسار، مع تبديل للإنجليزية وتصميم يستجيب لحجم الهاتف.",
                                    "Yes. The workspace supports Arabic right-to-left, an English switch and a phone-responsive layout.",
                                )
                            }}
                        </p>
                    </details>
                </div>
            </section>
            <section id="contact" class="landing-contact">
                <div>
                    <p class="landing-eyebrow">
                        {{ L("الخطوة التالية", "The next step") }}
                    </p>
                    <h2>
                        {{ L("لنعرف حضانتك", "Tell us about your nursery")
                        }}<br />{{ L("عن قرب.", "and how your day works.") }}
                    </h2>
                    <p>
                        {{
                            L(
                                "شارك بيانات التواصل واحتياجات الفريق. يُحفظ الطلب لدى إدارة منصة Nursery للمراجعة، دون دفع أو تفعيل تلقائي.",
                                "Share your contact details and team needs. The Nursery platform team receives the request for review, without a payment or automatic activation.",
                            )
                        }}
                    </p>
                    <div class="contact-mark" aria-hidden="true">✳</div>
                    <Link :href="route('register')" class="hero-text-link"
                        >{{
                            L(
                                "تريد إنشاء حساب وطلب تفعيل مساحة العمل؟",
                                "Want to create an account and request workspace activation?",
                            )
                        }}
                        ↗</Link
                    >
                </div>
                <section class="contact-form-card">
                    <p v-if="sent" role="status" class="saas-notice">
                        {{
                            L(
                                "تم حفظ طلبك للمراجعة. لا يتم تحصيل أي مبلغ.",
                                "Your request has been saved for review. No payment has been taken.",
                            )
                        }}
                    </p>
                    <form @submit.prevent="submit" class="saas-form">
                        <div class="saas-two">
                            <label
                                >{{ L("اسم الحضانة", "Nursery name")
                                }}<input
                                    v-model="form.nursery_name"
                                    required
                                    maxlength="180"
                                    autocomplete="organization" /></label
                            ><label
                                >{{ L("اسمك", "Your name")
                                }}<input
                                    v-model="form.name"
                                    required
                                    maxlength="120"
                                    autocomplete="name"
                            /></label>
                        </div>
                        <label
                            >{{ L("البريد الإلكتروني", "Email")
                            }}<input
                                v-model="form.email"
                                type="email"
                                required
                                maxlength="180"
                                autocomplete="email"
                                dir="ltr" /></label
                        ><label
                            >{{
                                L(
                                    "هاتف التواصل (اختياري)",
                                    "Contact phone (optional)",
                                )
                            }}<input
                                v-model="form.phone"
                                type="tel"
                                maxlength="40"
                                autocomplete="tel"
                                dir="ltr" /></label
                        ><label v-if="plans.length"
                            >{{ L("الباقة محل الاهتمام", "Plan of interest")
                            }}<select v-model="form.saas_plan_id">
                                <option value="">
                                    {{
                                        L(
                                            "أحتاج مساعدة في الاختيار",
                                            "Help me choose",
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
                            >{{
                                L(
                                    "ما الذي تريد تنظيمه في حضانتك؟",
                                    "What would you like to organize?",
                                )
                            }}<textarea
                                v-model="form.message"
                                rows="3"
                                maxlength="2000"
                            /></label
                        ><label class="saas-check"
                            ><input
                                type="checkbox"
                                v-model="form.consent"
                                required
                            />{{
                                L(
                                    "أوافق على حفظ هذه البيانات لدى إدارة Nursery لمراجعة طلبي. لا أدرج بيانات أطفال أو معلومات صحية هنا.",
                                    "I agree to these details being stored by Nursery’s platform team to review my request. I will not include child or health information here.",
                                )
                            }}</label
                        >
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
                            {{
                                form.processing
                                    ? L("جارٍ الحفظ...", "Saving...")
                                    : L(
                                          "إرسال طلب العرض",
                                          "Send proposal request",
                                      )
                            }}
                            <span>↗</span>
                        </button>
                    </form>
                </section>
            </section>
        </main>
        <footer class="landing-footer">
            <div class="footer-grid">
                <div class="footer-brand">
                    <Link href="/" class="brand"><ApplicationLogo /></Link>
                    <p>{{ L("يوم حضانة أهدأ، وإدارة أوضح.", "A calmer nursery day. A clearer view.") }}</p>
                </div>
                <nav class="footer-col" :aria-label="L('المنتج', 'Product')">
                    <h3>{{ L("المنتج", "Product") }}</h3>
                    <a href="#features">{{ L("داخل Nursery", "Inside Nursery") }}</a>
                    <a href="#ai">{{ L("الذكاء الاصطناعي", "AI insights") }}</a>
                    <a href="#plans">{{ L("الباقات", "Plans") }}</a>
                    <a href="#questions">{{ L("أسئلة شائعة", "Questions") }}</a>
                </nav>
                <nav class="footer-col" :aria-label="L('الحساب', 'Account')">
                    <h3>{{ L("الحساب", "Account") }}</h3>
                    <Link :href="route('login')">{{ L("دخول الحضانة", "Nursery login") }}</Link>
                    <Link :href="route('register')">{{ L("إنشاء حساب", "Create account") }}</Link>
                </nav>
                <div class="footer-col">
                    <h3>{{ L("تواصل", "Contact") }}</h3>
                    <a href="#contact">{{ L("اطلب عرضاً لحضانتك", "Request a walkthrough") }}</a>
                    <a href="https://wa.me/201098971853" target="_blank" rel="noopener noreferrer"><span dir="ltr">+20 109 897 1853</span></a>
                    <a href="https://www.facebook.com/RevivSolutions" target="_blank" rel="noopener noreferrer">Facebook</a>
                </div>
            </div>
            <div class="footer-bar">
                <p>© {{ new Date().getFullYear() }} Nursery. {{ L("جميع الحقوق محفوظة.", "All rights reserved.") }}</p>
                <p class="builder-credit">
                    <span>{{ L("تم تطوير هذا النظام بواسطة", "Built by") }}</span>
                    <a href="https://wa.me/201098971853" target="_blank" rel="noopener noreferrer" dir="ltr">Reviv Solutions</a>
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing-faq > * { min-width: 0; }
.landing-faq .section-heading { max-width: 100%; }
.product-window picture { display: block; }
.product-window img { height: auto; }
@media (max-width: 700px) { .product-window { transform: none; } }

.landing-footer { flex-wrap: wrap; }
.builder-credit {
    flex-basis: 100%;
    display: flex;
    align-items: baseline;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.35em;
    order: 4;
    padding-top: 18px;
    border-top: 1px solid #e0e5da;
    font-size: 12px;
    line-height: 1.9;
    color: #63745e;
}
.builder-credit a { color: #35533d; font-weight: 650; text-decoration: underline; text-underline-offset: 3px; }
.builder-credit a:hover { color: #172e1e; }
.builder-credit a:focus-visible { outline: 2px solid #52705b; outline-offset: 4px; border-radius: 2px; }

.nursery-ai {
    margin: 35px auto 80px;
    max-width: 1320px;
    padding: 64px;
    display: grid;
    grid-template-columns: 1fr 1.05fr;
    gap: 48px;
    background: #193e30;
    color: #f4f5e9;
    border-radius: 32px;
    scroll-margin-top: 100px;
}
.ai-kicker {
    font-size: 12px;
    letter-spacing: 0.1em;
    font-weight: 800;
    color: #bfd9bd;
}
.ai-kicker span {
    display: block;
    letter-spacing: 0;
    margin-top: 14px;
    font-size: 11px;
    border: 1px solid #70927b;
    width: fit-content;
    padding: 6px 10px;
    border-radius: 30px;
}
.ai-heading h2 {
    font-size: clamp(35px, 4vw, 58px);
    line-height: 1.45;
    margin: 25px 0;
}
.ai-heading h2 em {
    font-style: normal;
    color: #d3dcab;
}
.ai-heading > p:not(.ai-kicker) {
    color: #ccdacb;
    line-height: 2;
    font-size: 16px;
    max-width: 430px;
}
.ai-link {
    display: inline-block;
    background: #d5e1b9;
    color: #193e30;
    padding: 13px 22px;
    border-radius: 10px;
    margin-top: 28px;
    font-weight: 800;
}
.ai-questions {
    background: #244d3c;
    border: 1px solid #4c725b;
    border-radius: 22px;
    padding: 26px;
}
.ai-chat-top {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 22px;
}
.ai-chat-top small {
    display: block;
    font-size: 10px;
    color: #c6d4bf;
    margin-top: 5px;
    font-weight: 400;
}
.ai-orbit {
    font-size: 32px;
    color: #d3dcab;
}
.ai-questions article {
    display: flex;
    gap: 16px;
    border-top: 1px solid #52705b;
    padding: 18px 0 0;
    margin-top: 18px;
}
.ai-questions article > b {
    font-size: 11px;
    color: #cfdda9;
    padding-top: 5px;
}
.ai-questions h3 {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.7;
}
.ai-questions article p {
    font-size: 12px;
    color: #c9d9ca;
    line-height: 1.9;
    margin-top: 7px;
}
.ai-boundary {
    grid-column: 1/-1;
    border-top: 1px solid #52705b;
    padding-top: 28px;
}
.ai-boundary strong {
    font-size: 20px;
}
.ai-boundary p {
    color: #d1dfce;
    line-height: 2;
    font-size: 14px;
    max-width: 1050px;
    margin: 12px 0;
}
.ai-boundary small {
    color: #b7cbb9;
    line-height: 1.9;
    display: block;
    max-width: 1050px;
}
@media (max-width: 1000px) {
    .nursery-ai {
        margin: 30px 24px 55px;
        padding: 36px;
        gap: 28px;
    }
    .ai-heading h2 {
        font-size: 38px;
    }
}
@media (max-width: 700px) {
    .nursery-ai {
        margin: 25px 16px 40px;
        padding: 26px 20px;
        grid-template-columns: 1fr;
        border-radius: 22px;
    }
    .ai-heading h2 {
        font-size: 38px;
    }
    .ai-heading > p:not(.ai-kicker) {
        font-size: 14px;
    }
    .ai-questions {
        padding: 20px 16px;
    }
    .ai-boundary {
        grid-column: auto;
    }
    .ai-kicker {
        font-size: 11px;
    }
}
</style>
