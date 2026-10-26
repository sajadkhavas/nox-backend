# Winimi Backend Operations Policies

Contract version: `2026-07-20-phase-16`

These policies are frozen before frontend integration. Production implementation is completed in Phase 19 without changing the storefront API contract.

## Queue

Production must use a durable queue connection. The database driver is the minimum supported baseline; Redis may replace it during deployment when persistence, monitoring and restart behavior are verified.

Required worker behavior:

```bash
php artisan queue:work --sleep=2 --tries=3 --timeout=90 --max-time=3600
```

- workers are supervised and restarted after every release
- failed jobs are retained for investigation
- deploys run `php artisan queue:restart`
- provider credentials are never serialized into job payloads
- notification destinations remain encrypted at rest

## Scheduler

A single scheduler process runs every minute:

```cron
* * * * * cd /var/www/winimi-bakery-backend && php artisan schedule:run >> /dev/null 2>&1
```

Required scheduled commands:

- `inventory:release-expired`
- `notifications:dispatch --limit=100`

Both tasks use non-overlapping execution. A second scheduler instance must not be introduced unless distributed locks are configured and tested.

## Cache

- production runs `php artisan config:cache` and `php artisan route:cache`
- cache keys use the `winimi` prefix
- cached data never contains payment credentials, OTP codes or raw provider payloads
- store setting changes must become visible without requiring a deploy
- switching from database cache to Redis requires a smoke test for sessions, rate limits and scheduler locks

## Customer sessions

- authentication uses the isolated `customer` guard
- browser sessions use HttpOnly, Secure and encrypted cookies in production
- mutating requests use Sanctum CSRF protection
- session data is never stored in LocalStorage
- `SESSION_DOMAIN=.winimibakery.com` is used only after frontend and API HTTPS domains are active

## Media and storage

- product and content media use `MEDIA_DISK`
- production media must be stored on a persistent disk or object storage
- public product images may be publicly readable
- exports, backups, provider payloads and operational files remain private
- media URLs stored in public content must be HTTPS in production
- temporary upload files have a defined cleanup schedule
- no production release may rely on an ephemeral container filesystem

## Logs and privacy

Application logs may contain request IDs, public ULIDs and failure classifications. They must not contain:

- Zarinpal Merchant ID
- Kavenegar API key
- OTP codes
- full card data or card hashes
- raw provider request or verification payloads
- full notification destinations
- session cookies or CSRF tokens

Production uses structured logs where available, daily rotation and access limited to authorized operators.

## Legacy API

`/api/v1/*` is an inherited ToolMaster compatibility surface.

- it is excluded from the Winimi OpenAPI document
- new Winimi functionality may never be added to it
- production defaults to disabled
- when disabled it returns HTTP 404 with `code=legacy_api_disabled`
- the storefront is prohibited from depending on it

## Release cache sequence

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan queue:restart
```

The release is rolled back when migrations, readiness checks or smoke tests fail. External payment and SMS providers remain disabled until Phase 20.
