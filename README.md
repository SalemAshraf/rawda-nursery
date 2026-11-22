# Nursery — Nursery Management SaaS

**Status:** Product upgrade in progress. Operational workspace implemented and tested, not a production-readiness claim.
**Brand:** Nursery, selected November 22, 2026. Repository slug `rawda-nursery` is unchanged.

Nursery is a multi-tenant web application that runs a nursery's day: children,
guardians, attendance with authorized pickup, daily logs, incident reports,
tuition billing, and a full audit trail. It is built for private nurseries and
preschools in Egypt and the Gulf, with per-tenant timezone and currency.

Every tenant's data is isolated server-side: all tenant-owned queries pass
through a global scope, and cross-tenant IDs resolve to 404.

## Features (implemented)

| Area | What it does |
| --- | --- |
| Tenancy & roles | Nursery signs up with its own timezone and currency; roles are owner, manager, teacher, each with scoped permissions. |
| Children & guardians | Child profiles with classrooms, guardians, allergies, and restricted health notes. |
| Authorized pickup | A child can be checked out only to an active pickup person authorized for that specific child. Deactivated persons keep history. |
| Attendance | Same-day check-in/check-out on the nursery's own calendar (tenant timezone, not UTC), with who-picked-up recorded. |
| Daily logs | Meals, naps, mood, activities and notes per child per day. |
| Incidents | Severity levels (minor/moderate/severe) and a staff-recorded guardian acknowledgement. |
| Billing | Tuition plans, draft → sent → paid/void invoices, manually recorded payments (cash/transfer/wallet reference), outstanding and overdue tracking. |
| Audit log | Every mutating action is recorded, including health-data changes. |

## Deliberate safety decisions

- **Pickup is recorded authorization, not identity verification.** Staff must
  still verify identity at the door; the app never claims otherwise.
- **Health notes** are visible to owner/manager only; **allergies** stay
  visible to all staff because they are safety-critical.
- **No guardian login in v1**, so incident acknowledgement is recorded by
  staff and labeled as such in the UI.
- **No online payments in v1**; payments are recorded manually.
- Development uses **synthetic demo data only** (`DemoSeeder`); no real child
  or guardian data belongs in this repository.

## Stack

- PHP 8.3+ / Laravel 13, Inertia.js + Vue 3, Tailwind CSS
- MySQL 8 — in production **and** in the test suite (feature tests run against
  a real MySQL 8 server, not SQLite)

## Getting started

Requirements: PHP 8.3+ with Composer, Node 22+, MySQL 8.

```bash
cp .env.example .env          # set DB_* for your MySQL
php artisan key:generate
composer install
npm install && npm run build  # or: npm run dev
php artisan migrate
php artisan db:seed           # optional synthetic demo data
php artisan serve
```

## Testing

Feature tests expect MySQL on `127.0.0.1:33306` and a `rawda_test` database
(see `phpunit.xml`):

```bash
php artisan test
```

80 tests / 507 assertions passed in the latest upgrade verification on MariaDB 10.6.23 (PHP 8.4). The original MySQL 8 setup remains supported, but this upgrade was not re-run on MySQL 8. They cover tenant
isolation, pickup authorization boundaries, attendance rules, role gates,
the billing state machine, health-data visibility, and audit logging.

## Operational workspace upgrade

- Live daily overview with on-site, collected and not-arrived children, care-log gaps, pickup-list gaps, classroom capacity and incident follow-ups.
- Search/filter attendance by name, room and state. Allergy details are visible at handoff. Checkout still requires an active authorized pickup person; physical identity verification remains the staff's responsibility.
- Classroom capacity editing with over-capacity warnings. Capacity is nursery-entered operational data, not a claim of legal staffing or licensing compliance.
- Care logs persist all entered fields and selected dates, show unsaved changes and validation errors, and record saves in activity history.
- Child editing preserves the current classroom and allows reassignment within the same nursery.
- Admissions now tracks inquiries, nursery visits, waitlists and staff-recorded follow-ups. Confirmed enrollment creates one child profile and primary guardian, without pickup permission, fees or outgoing messages. Retries of the same inquiry return the same child.
- Managers have date-ranged care/collection reports, daily ledgers, method breakdowns and current classroom enrollment. Date-ranged activity and current open fees are labeled separately to avoid misleading totals.
- Payment recording rejects overpayments and invalid input leaves draft invoices unchanged. Invoice transitions and attendance handoffs use row-lock transactions. This is manual recordkeeping, not a payment processor. Amounts use this existing ledger's two-decimal storage, not full ISO currency precision; three-decimal Gulf currencies need a separately reviewed data migration before production use. The parser now rejects extra decimals, exponent syntax and values outside the database limit instead of silently rounding.
- Managers can inspect tenant-scoped, paginated activity history. Teachers never receive financial dashboard totals or general medical notes.
- Responsive child cards on phone, mobile navigation, Arabic/English workspace and bundled Cairo font. No font CDN requests.

The operational workspace has no schema changes. The admissions increment adds only the `admissions` table through a forward migration; run `php artisan migrate` on an existing install after backing up its database. For an existing install, keep its `.env` and database, run `composer install`, `npm ci`, and `npm run build` after switching to the reviewed upgrade code. Do not reset or seed an existing nursery database.

## Commercial platform and setup upgrade

The public landing, sales-inquiry inbox, separate platform administrator guard, configurable plan catalog and manual subscriptions are implemented. Prices/trials remain unconfigured. Existing nurseries keep their access; new accounts request manual activation. A resumable first-run nursery guide now leads new owners through details, classrooms, child/guardian and pickup setup, then gets out of the dashboard. No production deployment or live charging is included.

See [`docs/commercial-platform.md`](docs/commercial-platform.md) for safe forward migrations, operator setup, access behavior and explicit limits, and [`docs/competitor-priorities-2026-11.md`](docs/competitor-priorities-2026-11.md) for current feature evidence and remaining gaps.

## Documentation

- [`docs/market-study.md`](docs/market-study.md) — Egypt + all six Gulf
  states, vendor-sourced with links.
- [`docs/plan.md`](docs/plan.md) — phased product plan and open decisions.
- [`docs/database.md`](docs/database.md) — schema design and data rules.

## Roadmap (not in v1)

Guardian portal/app, online payments (Paymob/Fawry for Egypt; ZATCA e-invoicing
for Saudi needs official verification first), announcements and messaging,
Excel import, multi-branch. The operational workspace now has an Arabic-first RTL interface with an English switch; account/profile/password screens are localized too. Server validation messages still depend on the Laravel locale.

## License

Proprietary. Private repository; all rights reserved.
