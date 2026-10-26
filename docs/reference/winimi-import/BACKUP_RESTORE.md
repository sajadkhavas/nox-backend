# Winimi Backup and Restore Runbook

Contract version: `2026-07-20-phase-16`

A backup is not considered valid until it has been restored into an isolated environment and the acceptance checks pass.

## Scope

Backups cover:

1. production database
2. persistent product/content media
3. private operational exports when present
4. deployment configuration inventory without secret values

Application source code is recovered from the signed GitHub release and is not duplicated inside database archives.

## Objectives

- target RPO: 24 hours before public launch; reduce when order volume requires it
- target RTO: 4 hours
- default retention: 14 daily backups
- at least one copy must be stored outside the application server
- backup storage must be private and encrypted

## Required environment configuration

```env
BACKUP_DISK=local
BACKUP_RETENTION_DAYS=14
BACKUP_ENCRYPTION_PASSWORD=
```

Production should use a remote private disk even though local storage remains the development default. The encryption password is a server secret and must never be committed.

## Create a backup

The project includes Spatie Laravel Backup. The production command is:

```bash
php artisan backup:run --only-db
php artisan backup:run --only-files
php artisan backup:list
```

A deployment or scheduler may use a combined `backup:run` after disk capacity and remote upload behavior have been verified.

Before reporting success, verify:

- command exit code is zero
- archive exists on the configured disk
- archive size is plausible and non-zero
- remote copy is visible from a separate credential/session
- no archive is publicly readable

## Restore drill

Never restore directly over the active production database during a drill.

1. Provision an isolated database and storage directory.
2. Stop workers and scheduler for the isolated environment.
3. Download and decrypt the selected archive.
4. Import the database into the isolated database.
5. Restore media to the isolated persistent disk.
6. Configure a temporary non-public application instance.
7. Run:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --except-vendor
php artisan backend:readiness
```

8. Verify `/api/system/ready`, `/api/system/contracts` and `/api/system/openapi`.
9. Confirm catalog products, variants, orders, payment attempts and content counts are consistent.
10. Confirm payment and SMS remain disabled.
11. Record archive identifier, restore date, operator, duration and result.
12. Destroy the isolated environment and its decrypted files.

## Production disaster restore

A real restore requires an incident owner and a written recovery point decision.

1. Put storefront purchasing into maintenance mode.
2. Stop queue workers and scheduler.
3. Take a final snapshot of the damaged state when safe.
4. Restore database and media from the selected verified archive.
5. Deploy the matching application release.
6. Run migrations only after confirming the backup's migration level.
7. Rebuild caches.
8. Run readiness and smoke tests with providers disabled.
9. Start scheduler and queue workers.
10. Re-enable purchasing only after order and inventory reconciliation.

## Retention and cleanup

- keep 14 daily backups by default
- keep longer monthly archives only when operationally required
- expired backups are removed by the backup package cleanup command
- failed cleanup is an alert, not a silent warning
- decrypted local restore files are deleted immediately after verification

## Audit evidence

Each restore drill records:

- backup archive and checksum
- source release/commit
- database and media restore result
- acceptance command output
- RPO/RTO measured values
- operator and reviewer
- unresolved discrepancies

Phase 19 must complete at least one restore drill before `production_deployed=ready` is reported.
