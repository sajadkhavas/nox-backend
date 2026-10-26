# Phase 10 Backend Audit

Date: 2026-07-19
Repository: `sajadkhavas/winimi-bakery-backend`
Target frontend: `sajadkhavas/cooci`

## Executive summary

The repository was a Laravel 11 + Filament 3 application with useful operational infrastructure, but its domain model and public API were created for the previous ToolMaster industrial catalog and RFQ workflow.

A full rewrite is not required. Phase 10 keeps the Laravel, Filament, Sanctum, media, audit-log, backup and monitoring foundations while quarantining the old public API as a legacy compatibility layer. The framework and affected dependencies were upgraded to patched versions before this foundation was accepted.

## Reusable foundations

- Laravel 12 and PHP 8.2+
- Filament 3 administration panel
- Laravel Sanctum
- Spatie Media Library
- role and permission support
- activity logging
- backup, Pulse and Telescope integrations
- existing product, category, blog and media data that can be migrated selectively

## Incompatible legacy domain

The following concepts do not match the Winimi storefront contract and must not be treated as the final commerce model:

- industrial product fields such as model, country, usage, applications and price range
- Brand and Subcategory assumptions
- RFQ and RFQ item workflows
- email/password Bearer-token customer authentication
- exact product availability derived from a single boolean
- public product resources shaped for ToolMaster
- ToolMaster-specific SEO and seller identity
- frontend assets and Vite PWA configuration stored in this backend repository

## Security and reliability findings

### Fixed in Phase 10

- Missing `.env.example`
- hard-coded ToolMaster production origins in CORS
- credentials disabled despite the frontend using `credentials: include`
- no shared request ID or API version response headers
- no machine-readable contract status endpoint
- API exceptions not explicitly forced to JSON
- unsafe unconditional use of cache tags with non-taggable cache drivers
- ToolMaster seller identity in Product structured data
- public legacy endpoints not marked as deprecated
- duplicate inherited migrations for Sanctum and queue tables
- missing Laravel runtime directories required during Composer package discovery
- vulnerable locked versions of Laravel, Filament Forms, Guzzle and PSR-7
- Laravel 11 incompatible authentication-log plugin constraint

### Deferred to later phases

- replace email/password token login with mobile OTP and server-managed sessions
- migrate users to normalized unique Iranian mobile numbers
- implement order ownership policies
- implement transactional inventory and checkout
- implement Zarinpal request and verification flow
- remove or archive old frontend source files after confirming no Filament build dependency
- redesign Filament resources for bakery products, variants and orders

## Migration strategy

1. Keep `/api/v1/*` operational temporarily.
2. Mark every `/api/v1/*` response with deprecation headers.
3. Publish system and contract status under `/api/system/*`.
4. Implement the new frontend contract on unversioned routes already expected by `cooci`, such as `/api/auth/*` and `/api/checkout`.
5. Migrate data table-by-table rather than renaming industrial columns into unrelated bakery meanings.
6. Remove the legacy API only after the new catalog, account, order and payment suites are green.

## Phase ownership

- Phase 10: backend foundation, contract, CORS, environment, CI
- Phase 11: catalog and Filament product domain
- Phase 12: OTP and account sessions
- Phase 13: checkout, orders and inventory
- Phase 14: Zarinpal payments
- Phase 15+: reviews, notifications, reporting and full frontend integration
