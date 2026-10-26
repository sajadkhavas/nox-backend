# F14-BE-B Cleanup Status

## Completed in B1

- Removed the complete `/api/v1` inherited route surface and controllers.
- Removed the legacy API middleware and configuration flag.
- Removed inherited legacy models, Filament resources and schema migrations.
- Removed the unrelated React/Vite storefront source from the backend repository.
- Quarantined imported contracts, audits and deployment documentation under reference folders.
- Replaced active imported configuration with fail-closed `config/lbb.php`.
- Reset Composer checks to an LBB foundation audit.

## Remaining in B2

- Rename Bakery catalog/content classes and tables to neutral LBB names.
- Remove food-only fields and chilled delivery behavior.
- Remove city SEO pages.
- Rebuild catalog and Filament tests around apparel requirements.


## Completed in B2.1

- Renamed active catalog/content models, resources, policies, factories and tests from bakery-specific names to neutral commerce names.
- Renamed active database tables from `bakery_*` to neutral table names.
- Removed the city SEO page model, endpoint, resource and migration.
- Removed stale generic API resources left by the deleted ToolMaster controllers.
- Removed legacy migrations that competed for the neutral catalog table names.
- Assigned duplicated `message_templates` schema ownership to the newest migration.
- Replaced bakery cache namespaces, index names and inherited storefront labels in active runtime code.
- Renamed nested Filament page classes and test files that used plural Bakery names.
- Excluded generated SQLite state from identity auditing and commits.
- Extended the foundation audit to reject active Bakery identity references.


## Completed in B2.2

- Removed ingredients, allergens, shelf-life, storage and product cooling fields.
- Removed weight as a generic variant identity.
- Removed chilled delivery from enums, zones, API options, checkout and order snapshots.
- Preserved standard delivery and pickup with fail-closed defaults.
- Preserved server pricing, inventory locks, reservations, idempotency, oversell protection and ownership boundaries.
- Preserved server-side payment verification and exactly-once stock consumption.
- Added a neutral production-blocked content fixture without inventing real store facts.
- Updated the contract version to `2026-08-06-f14-be-b2`.
- Deferred color, size, collection and apparel variant structure explicitly to F14-BE-C.


## B2 contract state

- Domain cleanup gate: ready.
- Generic catalog baseline: neutral-baseline-ready.
- Apparel domain: not-started; owned by F14-BE-C.
- Backend freeze: not-ready and fail-closed by design.
- Production readiness is not claimed by this phase.
