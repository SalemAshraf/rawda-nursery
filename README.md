# Baraim — Nursery Management SaaS

**Status:** v1 prototype. Functional and test-covered, not production-hardened.
**Brand:** "Baraim" (براعم) is provisional and under review after same-category name collisions surfaced; the repo slug `rawda-nursery` is unchanged for now.

Baraim is a multi-tenant web application that runs a nursery's day: children,
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

42 tests / 138 assertions, all passing on MySQL 8. They cover tenant
isolation, pickup authorization boundaries, attendance rules, role gates,
the billing state machine, health-data visibility, and audit logging.

## Documentation

- [`docs/market-study.md`](docs/market-study.md) — Egypt + all six Gulf
  states, vendor-sourced with links.
- [`docs/plan.md`](docs/plan.md) — phased product plan and open decisions.
- [`docs/database.md`](docs/database.md) — schema design and data rules.

## Roadmap (not in v1)

Guardian portal/app, online payments (Paymob/Fawry for Egypt; ZATCA e-invoicing
for Saudi needs official verification first), announcements and messaging,
enrolment funnel, Excel import, multi-branch, Arabic-first UI option.

## License

Proprietary. Private repository; all rights reserved.
