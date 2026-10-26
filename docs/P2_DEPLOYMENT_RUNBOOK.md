# LBB P2 — Backend Production Deployment / Rollback Runbook

Status: **PREPARED / NO PRODUCTION MUTATION YET**

This runbook records prerequisites and an immutable-release deployment contract. It does not authorize production activation by itself.

## Source of truth

- Deploy only an explicitly frozen `BACKEND_RELEASE_SHA` from GitHub.
- Never edit an active release in place.
- Preserve an explicit rollback target before activation.
- The production API hostname is resolved at the server activation gate; do not invent it in source documentation.

## Required production configuration

Production secrets/settings live outside the repository. At minimum verify before activation:

- `APP_ENV=production`
- `APP_DEBUG=false`
- strong `APP_KEY`
- real `APP_URL`
- MySQL production credentials
- durable cache/queue/session configuration
- `SESSION_SECURE_COOKIE=true` when served over HTTPS
- exact frontend origin(s) for `FRONTEND_URL(S)` and Sanctum stateful domains
- payment/SMS/Web Push credentials only when those providers are deliberately activated
- backup destination/retention and encryption policy

Provider activation is fail-closed. `CHECKOUT_ENABLED`, `PAYMENT_ENABLED`, SMS providers and Web Push must not be enabled until their own production acceptance gate has real credentials and a verified callback/origin contract.

## Immutable filesystem model

Recommended server layout:

```text
/var/www/lbb/backend/
  releases/<BACKEND_RELEASE_SHA>/
  shared/.env
  shared/storage/app/
  shared/storage/logs/
  current -> releases/<ACTIVE_SHA>
```

`storage/framework` and bootstrap caches remain release/runtime-specific. User uploads and durable local application files must not be lost when the `current` symlink changes.

The web server document root must point at Laravel's `public` directory under `current`, never the repository root.

## Candidate preparation contract

For a new inactive release:

1. materialize the exact frozen GitHub SHA into a new release directory;
2. link the shared `.env` and required durable storage paths;
3. install locked production dependencies with Composer, without updating the lock file;
4. verify PHP extensions/platform requirements;
5. run application boot/readiness against production-like configuration without switching `current`;
6. inspect migration status and back up the production database before any migration mutation;
7. run `php artisan optimize` for the candidate after configuration is final;
8. ensure the public storage link is correct where local public media is used.

## Activation gate

Activation is a separate explicit server step. Before switching `current` verify:

- candidate SHA identity;
- database backup/restore evidence;
- migration plan and rollback compatibility;
- API health/readiness;
- queue worker configuration;
- scheduler configuration;
- HTTP/TLS/reverse-proxy configuration;
- filesystem permissions without granting source write access to the web process.

After the release is activated:

1. run required migrations with `php artisan migrate --force` only after backup and migration review;
2. gracefully restart long-lived queue workers (`php artisan queue:restart`) under a process monitor;
3. interrupt any in-progress sub-minute scheduler process after deployment when applicable (`php artisan schedule:interrupt`);
4. verify the scheduler's single server cron invokes `php artisan schedule:run` every minute;
5. perform API/readiness/auth/catalog smoke tests;
6. inspect application/queue/web-server logs;
7. verify PID/process stability and no repeated failed jobs.

## Rollback contract

A code rollback switches `current` back to the previously accepted release and restarts long-lived workers. Database rollback is **not** assumed to be safe merely because code rollback is safe. Migration compatibility must be reviewed before activation; destructive migrations require a separate data-safe rollback plan or forward fix.

Never run automatic destructive database rollback commands during an incident unless the exact migration/data consequences have been reviewed.

## Official-framework alignment

This runbook follows current Laravel guidance that production should be served from `public`, deployment should cache/optimize framework artifacts, queue workers are long-lived and must be restarted after code deployment, and the scheduler is driven by a single once-per-minute `schedule:run` entry. Server execution remains gated until the final GitHub work is complete.
