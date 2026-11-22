# Family workspace increment

This is an additive implementation increment, not completion of every competitor-PDF feature.

## Included

- Separate family accounts and browser guard. Tenant-scoped bearer API with 30-day, revocable Sanctum tokens.
- Staff-approved, hashed, single-use invitations expiring after 24 hours. Existing accounts must use their current password. An invitation never resets a password.
- Explicit child/guardian grants. Guardian linkage and email are checked on every child read. Billing and pickup permissions are separate. Revocation stops child access and invalidates outstanding pickup codes.
- Family web portal and Flutter client: linked children, published care and incidents, attendance, issued invoices, recorded-payment receipts, private account threads, announcements, tasks and planned absence notices.
- Historical care and incidents remain private by default. Editing a care entry hides it until management reviews and shares it again. Family incident acknowledgments are distinct from the pre-existing staff/offline acknowledgment field.
- One-use, two-minute pickup QR. Generation never creates pickup authorization. Staff review the current approved person and must confirm physical identity before checkout. Checkout locks and rechecks authorization, child status and current attendance. Camera starts only on request; paste fallback is available.
- Arabic/English layouts. Server-readable landing metadata, canonical and language alternate URLs, software structured data without fabricated ratings or prices, sitemap and private-page noindex/no-store headers.

## API

Base: `/api/v1/family`. JSON requests require `Accept: application/json`. All endpoints other than login/activate require `Authorization: Bearer <token>`.

| Method | Path | Request |
| --- | --- | --- |
| POST | `/login` | nursery_code, email, password, optional device_name |
| POST | `/activate` | token, email, password, password_confirmation, optional device_name |
| POST | `/logout` | Empty object; revokes this token only |
| GET | `/me` | Account, nursery/timezone, linked children and current announcements |
| GET | `/children/{id}` | Optional from/to YYYY-MM-DD, at most 93 days |
| POST | `/children/{id}/messages` | body, maximum 4000 characters |
| POST | `/children/{id}/incidents/{incident}/acknowledge` | confirmed=true |
| POST | `/children/{id}/tasks/{task}/complete` | Empty object |
| POST | `/children/{id}/absences` | starts_on, ends_on, optional reason, maximum 500 characters |
| POST | `/children/{id}/pickup-token` | Empty object; token, expires_at, identity_check_required |

Dates are YYYY-MM-DD. Attendance clock strings use the nursery's configured timezone, not the device timezone. Money strings are formatted server-side from integer minor units. A payment receipt reports a recorded payment, not independent bank confirmation.

Errors: 401 missing/expired token or disabled account; 403 wrong identity/ability or missing pickup permission; 404 inaccessible child/record; 422 validation or expired/used pickup; 423 nursery read-only; 429 throttled. General API and per-action rate limits use distinct keys. Reads never consume the smaller pickup counter.

Family browser paths start at `/family/login`. Staff management is `/families`; QR verification is `/pickup-scan`. Scanning/review is read-only. Only the explicit checkout POST records a handover.

## Flutter

Source is `mobile/family`, generated for Android, iOS and web. Qualification used official Flutter 3.50.1 / Dart 3.14.0. Dependencies are locked in pubspec.lock. Licensed Cairo fonts and the approved Nursery bear are bundled.

```
cd mobile/family
flutter pub get
flutter analyze
flutter test
flutter build web --no-wasm-dry-run --no-web-resources-cdn -O 1
```

Default API origin is https://nursery.mancydigitals.com. Override with `--dart-define=API_BASE_URL=https://your-authorized-server`. Local HTTP requires the explicit development-only `ALLOW_LOCAL_HTTP=true` define and is restricted to localhost, 127.0.0.1 or emulator 10.0.2.2. Do not distribute a local-validation build.

Native tokens use platform secure storage. The browser client keeps its bearer only in memory; refreshing requires sign-in. There is no offline child-record cache or automatic polling. A 401 clears the token and leaves child screens. If remote logout fails, local logout still clears the token and shows that server revocation was not confirmed.

Android release network permission and backup exclusion are configured. Android/iOS source targets are present, but native packaging, real-device camera/secure-storage qualification, signing identities and store publication remain separate unfinished checks. No store account or external service account was changed.

## Qualification

- PHP 8.3 with real MariaDB: 112 tests, 828 assertions passing before final formatting.
- Flutter analysis: no issues. Five unit/widget tests passing. Compiled web build successful.
- Real local synthetic API, not response mocks for UI proof: bearer login, linked-child reads, private message POST, fees/receipt and QR generation exercised in Arabic/English at 360px.
- Family web care/messages/fees/planning plus staff family/scanner pages checked in Arabic/English at 360, 768 and 1440px, no document overflow or JavaScript page errors.
- All browser records used for qualification are synthetic copies. Original preview data, production records and .env remain unchanged.

## Safe deployment

Use the central host writer. Back up database and files, retain existing .env/storage and run only forward `php artisan migrate --force`. Never run migrate:fresh or seeders on an existing nursery. Deploy the complete Vite public/build output and the changed PHP/Vue/config/routes together. Confirm HTTPS and correct APP_URL for invitations, canonical URLs and sitemap. New records remain unpublished until management opts in.

Do not automatically email invitations, expose historical notes or enable external payment/notification delivery. Live family testing requires specifically authorized accounts and records.

## Still open from the broader assignment

Recurring invoicing and reminders; downloadable/full receipt workflows; provider outbox and consent/delivery state; Paymob/Fawry merchant integration and reconciliation; bulk classroom care/attendance; richer absence changes/cancellation; medication consent and administration records without dosage advice; branches, scheduling and transport. Native Android/iOS builds, actual devices and any app-store publication also remain open. Other products and the Reviv articles are outside this repository's scope.
