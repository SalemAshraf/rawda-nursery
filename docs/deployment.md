# Deployment

Nursery uses PHP 8.3+, a MySQL-compatible database and prebuilt frontend assets. The lock targets PHP 8.3 and resolves Symfony 7.4; the earlier Symfony 8.1 resolution required PHP 8.4.1 and must not replace this lock. Never suppress Composer platform checks to make a release boot.

## Build away from a shared host

```sh
composer install --no-dev --prefer-dist --classmap-authoritative --no-interaction
npm ci
npm run build
```

Use the intended PHP runtime and run `composer check-platform-reqs --no-dev`. Package application source, production vendor and `public/build`. Exclude `.env`, credentials, databases, logs, session/view/cache payloads, `.git`, Node dependencies, tests and development-only instructions. Retain the versioned demo command, not a populated demo database. Include required third-party license notices.

## Isolated installation

Use an app-specific directory, database, database user and host-only session cookie. Point the document root at this application's `public/` directory. Do not change account-wide PHP, Composer or sibling applications.

Start from `.env.production.example`, set the exact HTTPS origin and isolated DB credentials, and generate a unique application key on the target. `APP_DEBUG` must be false. Keep mail in log mode until outgoing email is configured and approved. Make `storage` and `bootstrap/cache` writable by this application's runtime only as needed.

After confirming the correct new installation or backed-up existing installation:

```sh
php artisan key:generate  # fresh installation only, never rotate an existing key casually
php artisan package:discover
php artisan migrate --force
php artisan config:cache
php artisan view:cache
```

Never run `migrate:fresh`, reset or blanket seeds on existing records. Preserve the existing `.env`, key, DB and storage during updates. Remove stale release-specific cache payloads before package discovery when changing dependencies, especially if a previous environment contained development providers.

No application queue worker or cron task is required for implemented nursery workflows. `/up` is the health route. Sales inquiries persist only and do not trigger email or SMS.

## HTTPS and browser verification

Verify HTTPS asset/redirect origins, login, logout, sessions, CSRF-protected forms and tenant isolation on the real host. Do not fix a proxy mismatch by trusting every forwarded header without understanding the host. No unconditional proxy or forced-scheme override is built into the app.

For the Claude connector, `APP_URL` must match the exact intended HTTPS origin. Verify discovery, the bearer challenge, dynamic registration, the owner consent round-trip, PKCE exchange, a permitted read and revocation on the actual account. The machine endpoints `/mcp`, `/oauth/register`, `/oauth/token` and `/oauth/revoke` are excluded from browser CSRF and rate-limited. The browser authorization decision and owner settings remain CSRF-protected. Do not disable hosting security globally to bypass a failed connection.

An owner first enables AI with password confirmation and an explicit aggregate-sharing acknowledgment. AI remains off by default, including in synthetic demos. Nursery's four tools expose aggregate counts/totals only, never child/guardian identity, health, pickup, incident or free-text records. A successful local test does not prove Claude account compatibility on a new host.

## Initial access and commercial state

`/register` creates a pending nursery, not an active/free trial. `/login` is for nursery users. `/platform/login` is a separate administrator guard. No administrator, prices or trials are created by migrations.

After selecting the real operator identity, create a separate platform administrator using `php artisan platform:admin APPROVED_EMAIL --name=APPROVED_NAME`; passwords are entered in hidden prompts. Do not infer that identity from synthetic demo addresses.

For the requested demonstration experience, the additive command below creates an isolated, labeled synthetic nursery. Use a credential provisioned through a secure password workflow, never a fixed password copied from source, a chat message or shell history:

```sh
php artisan nursery:demo --confirm-synthetic --email=owner@nursery.example.test --password-file=/protected/path/password
```

The file must be private, readable only to its owner and contain a password of at least 12 characters. Delete it immediately after use. The command refuses an existing demo/login, leaves other nurseries unchanged, sends no messages and creates no real platform administrator. It deliberately gives only the synthetic nursery active demonstration access with no price or payment claim. Verify that the resulting login, demo banner and representative screens work before sharing its secure login link.

Current two-decimal ledger limits remain. A manual active subscription records an administrative decision, not proof of payment.
