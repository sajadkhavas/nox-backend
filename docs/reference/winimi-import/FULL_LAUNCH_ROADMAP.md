# Winimi Bakery Full Launch Roadmap

Roadmap lock: `2026-07-23-phase-19a`

## Delivery rule

The application must be internally complete, fully integrated, acceptance-tested and deployed before public activation. At the end of the internal roadmap, no implementation work may remain except supplying and validating these three external inputs:

1. payment gateway credentials / Zarinpal Merchant ID
2. eNAMAD badge code
3. SMS provider API key and approved OTP template

The React storefront and Laravel backend remain separate repositories. They communicate through the frozen API contract and run on one production server behind two Nginx virtual hosts.

## Completed foundation

- Phase 1–9.5: responsive modern storefront and business UX
- Phase 10: Laravel/Filament foundation and legacy boundary
- Phase 11: bakery catalog, Variants, price, stock and administration
- Phase 12: customer OTP architecture, secure sessions and profile
- Phase 13: checkout, orders, immutable snapshots and inventory reservations
- Phase 13.5: full-launch roadmap lock
- Phase 14: provider-ready payment backend
- Phase 15: complete store operations backend
- Phase 16: backend completion and contract freeze
- Phase 17: full frontend/backend integration
- Phase 18: coordinated API and browser acceptance
- Phase 19A: production deployment package

## Phase 14 — Provider-ready payment backend — complete

- persistent customer-owned payment attempts
- provider-neutral payment contract
- deterministic non-production testing provider
- disabled-by-default Zarinpal adapter
- idempotent initiation, retry and verification
- duplicate callback replay without a second stock decrement
- server amount and provider verification as the only payment truth

Only the real gateway credential remains external.

## Phase 15 — Complete store operations backend — complete

- customer-owned reusable addresses
- province/city delivery zones and nationwide dry fallback
- Tehran, Karaj and Andisheh chilled-delivery rules
- Filament-managed fees, limits and preparation windows
- content pages, FAQ, gallery, posts and city pages
- verified-purchase reviews and moderation
- persisted contact, gift and corporate inquiries
- notification outbox and order-fulfillment administration
- queue and scheduler commands

## Phase 16 — Backend completion and contract freeze — complete

Status: `backend_complete=ready`

- public contract version remains `2026-07-20-phase-16`
- OpenAPI 3.1 is exposed at `/api/system/openapi`
- stable success/error envelopes and machine codes
- frozen filters, sort values and pagination shape
- ownership and IDOR behavior are frozen
- deterministic `WinimiStagingSeeder`
- backup, restore, queue, cache, session and storage policies
- executable `backend:readiness --json`

The frozen contract is not renamed by later delivery phases.

## Phase 17 — Full frontend/backend integration — complete

Status: `frontend_integrated=ready`

Evidence: `sajadkhavas/cooci#12`.

- one typed API client with request IDs, timeouts, CSRF and 419 retry
- backend catalog, categories, filters, product details and pagination
- OTP session, profile and address CRUD
- Variant-aware cart reconciliation
- server-authoritative delivery and checkout
- idempotent order creation and separate payment initiation
- owned order history, cancellation and callback verification
- content, reviews, inquiries and safe trust slot
- no production static catalog, browser order source or mock authentication

## Phase 18 — End-to-end completion — complete

Status: `end_to_end_verified=ready`

### Backend acceptance

- deterministic staging catalog, content, customer and delivery zones
- frozen contract and exact three-external-input boundary
- OTP request, verification, secure session and logout
- customer address creation and ownership
- nationwide dry checkout
- chilled rejection outside allowed zones
- chilled checkout in Tehran
- idempotent checkout replay and conflict
- testing-provider payment initiation
- verified payment and duplicate callback replay
- stock consumed exactly once
- persisted inquiry, duplicate protection and honeypot

### Browser acceptance

- Chromium desktop and mobile projects
- public catalog rendered from the running Laravel backend
- search/filter route behavior
- protected account redirect
- real OTP testing-provider session through Sanctum cookies
- account route after login and logout invalidation
- public content, 404 and callback-state smoke tests
- browser console and unhandled page-error guard

### Quality gates

- existing frontend audits, lint, TypeScript, production build and performance budget
- existing backend audits, migrations, staging seed, route/config cache, PHPUnit and security audit
- dedicated Phase 18 architecture audits in both repositories
- coordinated CI starts Laravel and Node SSR on the same runner
- no live SMS, payment or eNAMAD dependency

## Phase 19A — Production deployment package — complete

Status: `production_server_package=ready`

Both repositories now provide a testable server package before SSH access:

- deterministic frontend and backend immutable release manifests
- SHA-256 release verification and secret-shaped-content rejection
- atomic current symlinks with retained prior releases
- production deploy and explicit rollback scripts
- frontend Node SSR systemd service bound to loopback
- Laravel PHP-FPM Nginx virtual host
- supervised database queue worker
- one-minute scheduler systemd timer
- daily encrypted backup service/timer
- disabled-provider production environment baseline
- frontend and backend server preflight commands
- public storefront/API smoke checks
- single-server DNS/TLS, migration, persistence, monitoring, restore and rollback runbooks
- CI validation for release creation, verification, Nginx/systemd syntax and deployment simulation

This marker means the repositories are ready to enter the VPS. It does not mean the site is publicly deployed.

## Phase 19B — Live server execution — next

The same Phase 19 continues on the Linux server:

- provision hardened user, filesystem and database
- install Node 22, PHP 8.3/FPM, Composer, Nginx and required extensions
- configure DNS and trusted TLS for storefront/API hosts
- install private frontend runtime env and backend `.env`
- deploy backend, migrate, link persistent media and verify readiness
- deploy frontend SSR and verify internal health
- enable queue worker, scheduler and backup timers across reboot
- run disabled-provider public production smoke
- complete encrypted off-server backup and isolated restore drill
- complete frontend and migration-aware backend rollback drill
- activate monitoring/log shipping and Core Web Vitals 75th-percentile dashboards
- verify Search Console ownership, submit sitemap and inspect representative URLs
- record final evidence and set `production_deployed=ready`

The detailed coordinated runbook is in `sajadkhavas/cooci/docs/PHASE_19_PRODUCTION_DEPLOYMENT.md`; backend-specific steps are in `docs/PHASE_19_PRODUCTION_DEPLOYMENT.md`.

## Phase 20 — External activation only

No feature development is allowed. Actions are limited to:

1. set payment credentials and perform a low-value live payment test
2. insert the eNAMAD badge code and verify its domain behavior
3. set the SMS key/template and verify OTP/order messages

After these three checks, public purchasing may be enabled.

## Definition of internally complete

Internal completion means:

- backend complete and contract-frozen
- frontend integrated for every production dynamic flow
- coordinated API/browser acceptance is green
- no trusted payment state comes from the browser
- no production catalog, authentication or order source depends on static or browser-only data
- production package is reproducible and verified
- live deployment, workers, scheduler, backups, monitoring and rollback work
- payment, eNAMAD and SMS paths already exist and wait only for external values
