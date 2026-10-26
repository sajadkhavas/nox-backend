# Winimi Bakery Backend

Laravel 12 + Filament 3 headless-commerce backend for the Winimi Bakery storefront.

The production frontend lives in `sajadkhavas/cooci`. This repository owns the API, Filament administration, database, catalog, customer authentication, reusable addresses, delivery configuration, checkout, orders, inventory, payments, fulfillment, content, reviews, inquiries and notification outbox.

## Current status

| Area | Status |
|---|---|
| Laravel / Filament foundation | Implemented |
| Bakery catalog and Variants | Implemented in Phase 11 |
| Customer OTP, sessions and profile | Implemented in Phase 12 |
| Checkout, orders and reservations | Implemented in Phase 13 |
| Provider-ready payment lifecycle | Implemented in Phase 14 |
| Complete store operations backend | Implemented in Phase 15 |
| Backend completion and contract freeze | **Ready in Phase 16** |
| Full frontend integration | Phase 17 — next |
| End-to-end completion | Phase 18 |
| Production deployment | Phase 19 |
| Three external activations only | Phase 20 |

Machine-readable status and schema:

```text
GET /api/system/contracts
GET /api/system/openapi
```

The Phase 16 gate reports:

```text
backend_complete=ready
contractVersion=2026-07-20-phase-16
```

## Requirements

- PHP 8.2+
- Composer 2
- SQLite for local development or MySQL 8 / MariaDB 10.6+
- PHP extensions: bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, openssl, pdo, tokenizer, xml and zip

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Admin panel:

```text
http://localhost:8000/admin
```

No production administrator password or external credential is committed.

## Frozen API contract

Phase 16 freezes:

- OpenAPI 3.1 JSON document
- success and error envelopes
- stable machine error codes
- catalog filters and sort values
- pagination shape and limits
- public ULID identifiers
- customer ownership and IDOR behavior
- queue, scheduler, cache, media and backup policies
- production legacy-API boundary

Error responses use:

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "اطلاعات ارسال‌شده معتبر نیست.",
  "errors": {},
  "meta": {
    "requestId": "request-id",
    "apiVersion": "1",
    "contractVersion": "2026-07-20-phase-16"
  }
}
```

Paginated endpoints use one shape:

```json
{
  "page": 1,
  "perPage": 12,
  "total": 24,
  "totalPages": 2,
  "from": 1,
  "to": 12,
  "hasMore": true
}
```

Full details:

- `docs/openapi.json`
- `docs/API_CONTRACT.md`
- `docs/API_ERRORS_AND_PAGINATION.md`

## Validation

Full local validation:

```bash
composer check
php scripts/audit-backend-freeze.php
php artisan backend:readiness --json
```

GitHub Actions validates:

- Composer metadata and security
- all Phase 10–16 architecture audits
- read-only Pint formatting
- fresh database migrations
- deterministic staging seeding
- cached configuration and routes
- command and Filament discovery
- OpenAPI and readiness gates
- all API, domain, authorization and admin tests

CI never modifies source files.

## Implemented APIs

### System

```text
GET /api/system/health
GET /api/system/ready
GET /api/system/meta
GET /api/system/contracts
GET /api/system/openapi
```

### Catalog and reviews

```text
GET  /api/catalog/categories
GET  /api/catalog/products
GET  /api/catalog/products/{slug}
GET  /api/catalog/products/{slug}/reviews
POST /api/account/orders/{orderId}/reviews
```

Catalog price and availability are server-calculated. Public reviews include approved verified-purchase reviews only.

### Customer authentication and addresses

```text
POST   /api/auth/otp/request
POST   /api/auth/otp/verify
GET    /api/auth/me
POST   /api/auth/logout
PATCH  /api/account/profile
GET    /api/account/addresses
POST   /api/account/addresses
PUT    /api/account/addresses/{addressId}
DELETE /api/account/addresses/{addressId}
```

Customers are isolated from administrators. Authentication uses the `customer` guard, HttpOnly sessions and Sanctum CSRF protection. Customer-owned resources return the same 404 contract for missing and cross-customer identifiers.

### Delivery, checkout and orders

```text
GET  /api/delivery/options
POST /api/checkout
GET  /api/account/orders
GET  /api/account/orders/{orderId}
POST /api/account/orders/{orderId}/cancel
```

Delivery zones, fees, minimum order values, free-delivery thresholds, preparation windows and daily limits are managed through Filament. Checkout remains server-authoritative and idempotent.

Expired reservations are released by:

```bash
php artisan inventory:release-expired
```

### Payments

```text
POST /api/orders/{orderId}/payments
POST /api/payments/verify
POST /api/payments/zarinpal/verify
```

Only provider verification marks an order paid and consumes stock. Duplicate callbacks cannot decrement inventory twice. Credentials are server-only and payment remains disabled until Phase 20.

### Store content and inquiries

```text
GET  /api/store/settings
GET  /api/store/pages/{slug}
GET  /api/store/faqs
GET  /api/store/gallery
GET  /api/store/posts
GET  /api/store/posts/{slug}
GET  /api/store/cities/{slug}
POST /api/inquiries
```

The bakery content domain is separate from inherited ToolMaster routes. Inquiries support contact, gift and corporate requests with rate limiting, honeypot and duplicate protection.

## Fulfillment and inventory safety

Controlled transitions:

```text
paid -> confirmed -> preparing -> ready -> dispatched -> delivered
                                  \-> delivered (pickup only)
```

Arbitrary status editing is disabled. Non-pickup dispatch requires a tracking code. Paid cancellation restores consumed inventory exactly once without falsely reporting a completed financial refund.

## Notification outbox

```bash
php artisan notifications:dispatch --limit=100
```

The scheduler runs notification dispatch and expired-reservation release every minute without overlap. Destinations are encrypted at rest.

Providers:

- `disabled`: safe default; rows remain pending
- `testing`: deterministic outside production only
- `kavenegar`: requires server-side credentials

## Staging acceptance data

Staging data is opt-in:

```env
SEED_WINIMI_STAGING=true
```

```bash
php artisan db:seed --class='Database\Seeders\WinimiStagingSeeder'
```

The seeder is idempotent, refuses production execution and creates dry/chilled/gift products, delivery zones and published content without activating payment, SMS or eNAMAD.

## Safe defaults

```env
SMS_PROVIDER=disabled
OTP_EXPOSE_TEST_CODE=false
CHECKOUT_ENABLED=false
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=disabled
ZARINPAL_MERCHANT_ID=
ORDER_SMS_PROVIDER=disabled
KAVENEGAR_API_KEY=
SEED_WINIMI_STAGING=false
```

No payment or SMS credential may use a `VITE_*` variable. eNAMAD code is non-public and returned only when explicitly enabled and supplied.

## Filament operations

The admin panel includes:

- bakery categories and products
- customers and reusable-address inspection
- delivery zones and operating fees
- controlled fulfillment, status history and internal notes
- payment-attempt inspection
- settings and eNAMAD slot
- legal/shipping/homepage pages, FAQ, gallery, blog and city pages
- verified-purchase review moderation
- contact/gift/corporate inquiry tracking
- notification templates and outbox inspection

Payment attempts and outbound notification records cannot be manually created.

## Operations documentation

- `docs/OPERATIONS_POLICIES.md`
- `docs/BACKUP_RESTORE.md`
- `docs/QUERY_INDEX_REVIEW.md`
- `docs/STORE_OPERATIONS.md`
- `docs/PAYMENTS.md`
- `docs/FULL_LAUNCH_ROADMAP.md`

## Frontend integration boundary

The backend is now frozen at `backend_complete=ready`. Phase 17 may connect the frontend to this contract but must not silently redefine response envelopes, error codes, pagination, ownership or payment semantics.

## Production baseline before external activation

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.winimibakery.com
FRONTEND_URLS=https://winimibakery.com,https://www.winimibakery.com
SANCTUM_STATEFUL_DOMAINS=winimibakery.com,www.winimibakery.com
SESSION_DOMAIN=.winimibakery.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
LEGACY_TOOLMASTER_API_ENABLED=false
CHECKOUT_ENABLED=false
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=disabled
ORDER_SMS_PROVIDER=disabled
ZARINPAL_MERCHANT_ID=
KAVENEGAR_API_KEY=
SEED_WINIMI_STAGING=false
```

A production release must run at minimum:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:restart
php artisan backend:readiness --json
```

Production also requires persistent media, supervised workers, one-minute scheduler, private backups with restore verification, logs, monitoring and rollback.

## Locked phase roadmap

- Phase 10: foundation and migration boundary — complete
- Phase 11: bakery catalog and administration — complete
- Phase 12: OTP authentication and customer account — complete
- Phase 13: checkout, orders and inventory reservations — complete
- Phase 13.5: full-launch audit and roadmap lock — complete
- Phase 14: provider-ready payment backend — complete
- Phase 15: complete store operations backend — complete
- Phase 16: backend completion and contract freeze — **complete**
- Phase 17: full frontend/backend integration — **next**
- Phase 18: end-to-end completion
- Phase 19: production server deployment
- Phase 20: external activation only

At the end of Phase 19, the only remaining inputs are payment gateway credentials, the eNAMAD badge code and the SMS provider key/template.
