# Market study: nursery management SaaS, Egypt and Gulf

Compiled 22 Nov 2026 from live competitor sites. Claims below are
vendor-specific observations, not market-wide statistics. Sources at the end.

## Egypt

Live Egyptian products found: Hadana, Kindaria, iCare Kids, LumiNest, Nestora,
Little Steps, Takwen, EduX, HUE CRM. The space is occupied; a new entrant
competes on execution and distribution, not novelty.

**Hadana (elhadana.com)** — the most complete local reference. Features listed
on its site: check-in/out, QR pickup codes, authorized pickup persons per
child, planned absences, unexplained-absence nudges, daily logs (meals, naps,
diapers, mood), bulk room logging, attendance export; incident reports with
severity + parent acknowledgement, allergies, medication schedule and log;
lesson plans, developmental milestones; parent-teacher chat, announcements,
photo gallery with admin review before publishing; tuition plans, bulk
invoices, online payment via Paymob and Fawry, PDF receipts, overdue
reminders; buses/routes with live position; staff records, certification
expiry warnings, multi-branch, audit log, role permissions, Arabic parent app
(Android/iOS), offline staff app, kiosk mode.

**Kindaria (business.kindaria.com/pricing)** — published Egyptian pricing:
starts at 75 EGP per child per month; one month free trial; no setup fees;
free data migration from Excel or old systems. Plans: Basic (dashboard,
teacher app, parent app, attendance, daily reports, secure pickup,
communication, health/safety, fees and invoices with reminders), Growth (adds
AI assistant, nursery website, admissions + waitlist), Enterprise (multi-
branch). Notably: NO online payment collection ("ليس بعد") — parents see
invoices, staff record payments manually. Pricing is confirmed per nursery on
WhatsApp by child count.

**Other Egyptian players** (feature claims from their own sites, not verified
in depth): iCare Kids (school + daycare), LumiNest, Nestora, Little Steps,
Takwen (attendance/fees/parents), EduX, HUE CRM for nurseries and therapy
centers.

## Gulf

Live Gulf-facing products found: Bambini, KinderLink (Saudi), Nashaa,
Parent.app GCC, CuePilot, Clayox (UAE, AED billing + VAT), Skooly, EduX Saudi
and UAE pages.

**Bambini (getbambini.com)** — Gulf-positioned: owner dashboard (attendance,
rooms, fees, enrolment), three apps (owner/teacher/parent), fees and payments,
enrolment, daily reports, messages, authorised pickup, offline mode,
multi-branch on Premium/Enterprise.

**KinderLink (kinderlink.sa)** — Saudi childcare/preschool management.

**Country differences that matter for product design:**

- Saudi Arabia: ZATCA e-invoicing applies to invoices issued there; SAR and
  Asia/Riyadh; mada/stc pay are the local payment rails. (ZATCA relevance is
  flagged from the EduX Saudi page; exact obligations need verification with a
  Saudi tax source before claiming compliance.)
- UAE: VAT-registered invoicing (Clayox advertises "AED billing & VAT").
- Egypt: Paymob/Fawry are the rails Hadana uses; Kindaria's price point (75
  EGP/child/month) anchors local willingness to pay. One vendor's price is not
  a market price.

## What this means for Rawda v1

1. The core day loop (attendance + authorized pickup + daily log + incidents +
   fees) is the validated baseline every competitor ships. That is what v1
   implements.
2. Online payments are NOT table stakes in Egypt (Kindaria ships without
   them), so manual payment recording in v1 is acceptable; Paymob/Fawry
   integration is a phase-2 item, ZATCA-compliant invoicing a Saudi-phase item.
3. Differentiation candidates seen in competitors: parent app, photos with
   review, bus tracking, development milestones, enrolment funnel,
   multi-branch. All deferred; see plan.md phases.
4. Distribution, not features, is the open question: every vertical already
   has an Egyptian player. The first 5 buyers decide.

## Sources

- https://elhadana.com/en/features
- https://business.kindaria.com/pricing
- https://getbambini.com/en
- https://kinderlink.sa/en/
- https://www.parent.app/gcc/childcare-management-software
- https://www.icarekidsegypt.com/
- https://luminesthub.com/
- https://getnestora.app/
- https://uselittlesteps.com/
- https://takwen.net/en
- https://www.clayox.com/childcare-management-software-uae
- https://dc-edux.com/en/nursery-management-system-saudi-arabia.html

## Gulf research pass 2 (22 Nov 2026): Kuwait, Qatar, Bahrain, Oman

Sources are vendor sites (features/published prices), not demand evidence. Licensing/tax requirements per country need official verification before any commitment.

### Kuwait
- Rawdah (https://rawdahapp.com, by KABSOOLA LLC): bilingual EN/AR with RTL, published pricing KWD 25/60/120 per month by branch/child/staff tiers, online payment via Checkout.com, SMS credits, admissions pipeline, health records. NOTE: the name "Rawdah" is taken by this Kuwaiti product; our working name must change before any launch.
- TinyHub (https://tinyhubkw.com): daily record per child, admin review, branded PDF export for parents.
- Tasdeed (https://tasdeed.app): Arabic cloud product, monthly child subscriptions in KWD, meals/transport/attendance/staff payroll; has country pages for Kuwait, Bahrain and Oman with local currencies.

### Qatar
- Bloomz Hub (https://bloomzhub.com): built in Qatar for Gulf nurseries, Arabic-first, enrollment/attendance/invoicing/parent communication plus double-entry accounting; also runs a nursery directory.
- Nursery Pro (https://nurserypro.augmented-edu.com), Skooly (https://getskooly.com, Arabic+English, QR check-in), hoopooh (https://hoopooh.com, EYFS tagging).

### Bahrain
- EduX Bahrain page (https://dc-edux.com/en/nursery-management-system-bahrain.html): mentions MOE licensing and BHD fees (vendor claim, unverified).
- Bounce (https://hellobounce.com): MENA childcare SaaS, announced Bahrain launch signing Madeleine's Preschool.
- Pragma (https://pragma.bh): local ERP with a nursery offering (fees by terms, staff qualifications).

### Oman
- EduX Oman page (https://dc-edux.com/en/nursery-management-system-oman.html), Tasdeed with OMR pricing, STAK Muscat.

### Cross-country pattern
The same regional players (Bambini, EduX, Tasdeed, Skooly, Bounce) cover multiple Gulf states with localized currency pages. Arabic-first parent apps are the norm; published per-flat-fee monthly pricing (Rawdah KW) exists alongside per-child pricing (Kindaria EG).
