# Phase 19 — Backend Production Deployment

Preparation marker: `production_server_package=ready`

Final live marker: `production_deployed=ready`

This repository supplies the immutable Laravel release, API Nginx virtual host, private environment baseline, PHP-FPM integration, queue worker, one-minute scheduler, daily backup timer, server preflight, smoke checks and rollback controls used by the coordinated single-server runbook in `sajadkhavas/cooci`.

Do not report `production_deployed=ready` from repository CI alone. The marker requires live DNS/TLS, database and media persistence, successful backup restore drill, public health checks and rollback evidence.

## Release creation

Create a production dependency tree before packaging:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php scripts/create-backend-release.php . .release
php scripts/verify-backend-release.php <release-directory>
```

The release excludes `.env`, tests, Git metadata, deployment tooling, runtime logs, temporary caches, SQLite files and persistent storage. It includes the optimized `vendor` tree and a SHA-256 manifest bound to contract `2026-07-20-phase-16`.

## Shared production state

Only these paths persist across releases:

```text
/var/www/winimi/backend/shared/.env
/var/www/winimi/backend/shared/storage/
/var/www/winimi/backups/
```

The private `.env` must be owner `root:winimi` or `winimi:www-data`, mode `0640` or stricter. Product/content media live in shared storage and are exposed through Laravel's `public/storage` link.

## Disabled-provider environment

Copy `deploy/backend.production.env.example` to the shared `.env`, then generate:

- `APP_KEY`
- database name/user/password
- backup encryption password
- remote private backup disk credentials

Before Phase 20, keep exactly these disabled/empty:

```env
CHECKOUT_ENABLED=false
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=disabled
ZARINPAL_MERCHANT_ID=
SMS_PROVIDER=disabled
ORDER_SMS_PROVIDER=disabled
OTP_EXPOSE_TEST_CODE=false
KAVENEGAR_API_KEY=
ENAMAD_ENABLED=false
ENAMAD_BADGE_CODE=
SEED_WINIMI_STAGING=false
```

Database and backup secrets are server-generated operational credentials; they are not part of the three external Phase 20 provider inputs.

## Services

Install under `/etc/systemd/system/`:

- `winimi-backend-queue.service`
- `winimi-backend-scheduler.service`
- `winimi-backend-scheduler.timer`
- `winimi-backend-backup.service`
- `winimi-backend-backup.timer`

Then:

```bash
sudo systemd-analyze verify /etc/systemd/system/winimi-backend-*.service /etc/systemd/system/winimi-backend-*.timer
sudo systemctl daemon-reload
sudo systemctl enable --now winimi-backend-queue.service
sudo systemctl enable --now winimi-backend-scheduler.timer
```

Enable the backup timer only after the backup disk, encryption and off-server copy have been verified.

## First deployment

```bash
deploy/bin/preflight-backend-server.sh
deploy/bin/deploy-production-backend.sh <verified-release-directory>
```

The deployment:

1. verifies every release file and SHA-256
2. links the private `.env` and persistent storage
3. clears stale caches
4. runs migrations with `--force`
5. recreates storage link and optimized caches
6. runs `backend:readiness --json`
7. atomically switches `current`
8. restarts PHP-FPM and queue worker
9. starts/verifies scheduler timer
10. checks the production readiness endpoint
11. restarts long-lived queue workers gracefully

A post-activation failure restores the prior symlink. Database migrations are never automatically reversed.

## Nginx

Install `deploy/nginx/winimi-api.conf.example` after issuing the API certificate. Adjust only the PHP-FPM socket when the server package uses a different 8.3 socket path.

```bash
sudo nginx -t
sudo systemctl reload nginx
```

The API virtual host serves `/public`, forwards PHP only to PHP-FPM, caches public media conservatively and blocks dotfiles, `.env`, Composer metadata and Artisan from direct HTTP access.

## Production preflight and smoke

```bash
deploy/bin/preflight-backend-server.sh
deploy/bin/smoke-backend-production.sh
```

Preflight validates PHP 8.3/extensions, systemd units, Nginx, filesystem, private env permissions, secure cookies, provider-disabled settings, empty external credentials and free disk space.

Smoke validates API readiness, frozen contract, system metadata, migration status, backend readiness, routes, schedule discovery and failed-job command access without revealing secrets.

## Backup and restore drill

Follow `docs/BACKUP_RESTORE.md`.

Before final deployment status:

1. run database and media backup
2. verify encrypted archive and remote private copy
3. record SHA-256
4. restore into an isolated database/storage target
5. run migration status, routes and readiness
6. compare catalog, order, payment-attempt and content counts
7. verify payment/SMS remain disabled
8. record measured RPO/RTO
9. destroy decrypted drill material

A backup without a successful isolated restore drill is not accepted.

## Rollback

Rollback requires explicit operator confirmation:

```bash
BACKEND_ROLLBACK_CONFIRMED=true \
BACKEND_RESTART_COMMAND='sudo systemctl restart php8.3-fpm.service && sudo systemctl restart winimi-backend-queue.service' \
BACKEND_HEALTH_URL='https://api.winimibakery.com/api/system/ready' \
deploy/bin/rollback-backend.sh /var/www/winimi/backend <release-id>
```

Before running, confirm the target code is compatible with the current database migration level or choose a verified database restore point. The rollback script changes the release symlink and caches, not database state.

## Final live evidence

The final record must include:

- backend release ID and Git commit
- manifest checksum verification
- migration status
- PHP-FPM, worker and scheduler status
- API Nginx config test
- public certificate names and expiry
- readiness/contracts responses
- backup checksum and restore-drill result
- rollback-drill result
- unresolved alerts or warnings

Only after the coordinated storefront and backend evidence is complete may the roadmap status become `production_deployed=ready`.
