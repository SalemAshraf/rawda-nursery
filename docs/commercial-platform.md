# Commercial platform and first-run setup

Verified locally on November 22, 2026. Product name is provisional.

## Safe upgrade

Back up the existing database. Preserve `.env`, existing database and storage. Run `composer install`, `npm ci`, `npm run build`, and `php artisan migrate`. Do not run fresh/reset migrations or seed an existing nursery.

Forward migrations create commercial tables and add setup-progress fields. Existing nursery records are not rewritten. A nursery without a subscription row retains its previous access; no default expiry is applied. Existing nurseries are not forced into setup. New registration creates a pending subscription and a resumable first-run guide. Pending, suspended, past-due and expired subscriptions are read-only for operational writes, while account security, owner setup metadata and subscription requests remain available.

## Sales workflow

- `/` is an Arabic/English product landing page. Its contact form stores a sales inquiry after explicit consent and validation. It sends no email or SMS. Requests are rate-limited.
- No default prices, plans, free trials or payment methods are seeded. Until an operator publishes plans, the landing page asks for a proposal. A blank plan price means quote on request.
- `/subscription` is owner-only. Owners can request a plan or activation without being charged or changing their existing plan.
- `/platform/login` uses a separate administrator table and authentication guard. Nursery owner accounts cannot enter it.
- A server operator can create an administrator with `php artisan platform:admin owner@example.com --name="Platform owner"`. The command uses hidden password prompts, refuses an existing email, and never promotes a nursery account. No real administrator is created by migrations or seeds.
- `/platform` provides paginated nursery metadata, plan editing, sales follow-up stages, explicit manual subscription state changes and an administrative action history. It does not expose child names, guardian lists, care records or health notes.
- Subscription updates require an explicit confirmation. Dates use server UTC. A blank period end means no expiry; operators must deliberately choose it. Activation records an administrative decision, not proof of payment. No gateway, bank charge, tax invoice, refund or automated collection is implemented.
- Platform prices use exact two-decimal parsing and reject unsupported three-decimal currencies rather than pretending to support their precision.

## Setup guide

New owners first see `/setup`: nursery details, classrooms/capacity, first child/guardian, authorized pickup, then a start review. The current step is saved on the server. Continue later leaves records and progress intact. The guide can always be revisited from the owner menu. Finishing requires an actual classroom and active child, authorized pickup for every active child, and a safety review; it then stops intercepting the dashboard. This is not a compliance or medical certification.

Existing invoice currency cannot be reinterpreted through setup. Existing attendance/care timezones are also protected. Pending accounts may prepare their nursery metadata, then request activation before adding operational records.

## Route/security review

All operational GET routes were reviewed: dashboards, directories/detail pages, attendance/care/billing/admissions/reports/audit/staff indexes, setup, subscriptions and platform views read state only. The signed email-verification GET is an intentional account-security mutation outside operational subscription restrictions. Password recovery/change, verification and logout remain outside that gate. Platform authentication is independent of the nursery guard. Tests cover nursery-owner rejection from platform, published-plan boundaries, owner-only subscription/setup access, cross-tenant request isolation, legacy access, pending/expired/suspended writes, exact plan amounts, explicit activation confirmation and no payment creation.

## Still deliberately absent

Parent accounts/apps, automatic family messaging, online payment, recurring collection, medication workflows, school-bus tracking, legal/curriculum certification and multi-branch management. These must not be implied by sales copy.
