# Rawda (working name)

Multi-tenant nursery management SaaS for Egypt and the Gulf. Each nursery is a
tenant; every record is tenant-scoped server-side.

"Rawda" (روضة, kindergarten) is a provisional name. Change it before launch.

## What v1 does

- Tenant registration: a nursery signs up with its timezone and currency and
  gets an owner account.
- Roles: owner (everything, including staff accounts), manager (records and
  billing), teacher (attendance, daily logs, incident reports).
- Children register with classrooms, guardians, allergies and restricted
  health notes.
- Authorized pickup: a child can only be checked out to an active pickup
  person authorized for that specific child. This is a recorded authorization,
  not identity verification; staff must still verify identity at the door.
- Attendance: same-day check-in/check-out on the nursery's calendar (tenant
  timezone, not UTC).
- Daily logs: meals, naps, mood, activities, notes per child per day.
- Incidents with severity (minor/moderate/severe) and a staff-recorded
  guardian acknowledgement (no guardian login in v1).
- Billing: tuition plans, draft → sent → paid/void invoices, manually recorded
  payments (cash/transfer/wallet reference), outstanding and overdue tracking.
  No online payment collection in v1.
- Audit log of every mutating action, including health-data changes.

Out of scope for v1 (see docs/plan.md): parent app, photo sharing, messaging,
online payments, bus tracking, enrolment funnel, multi-branch.

## Stack

- PHP 8.3+ / Laravel 13, Inertia + Vue 3, Tailwind.
- MySQL 8 in production and in the test suite (feature tests run against a
  real MySQL 8 server, not SQLite).

## Development setup

1. Install PHP 8.3+ with Composer, Node 22+, and MySQL 8.
2. `cp .env.example .env`, set `DB_*` for your MySQL, `php artisan key:generate`.
3. `composer install`, `npm install`, `npm run build` (or `npm run dev`).
4. `php artisan migrate`, then `php artisan serve`.

## Tests

Feature tests expect MySQL on `127.0.0.1:33306` (see phpunit.xml) and a
`rawda_test` database:

```
php artisan test
```

42 tests cover tenant isolation, pickup authorization boundaries, attendance
rules, role permissions, billing state transitions, health-data visibility and
audit logging.

## Data and safety rules baked in

- All tenant-owned queries are filtered by the authenticated user's tenant via
  a global scope; cross-tenant ids resolve to 404.
- Health notes are visible to owner/manager only; allergies stay visible to
  all staff because they are safety-critical.
- Pickup persons are deactivated, never deleted, so past attendance rows keep
  their meaning.
- Use synthetic data only in development.
