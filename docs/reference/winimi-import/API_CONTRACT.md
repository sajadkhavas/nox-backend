# Winimi Frontend / Backend API Contract

Contract version: `2026-07-20-phase-16`

Frontend API origin example: `https://api.winimibakery.com`

Machine-readable OpenAPI 3.1 document:

```text
GET /api/system/openapi
```

Machine-readable implementation status:

```text
GET /api/system/contracts
```

## General rules

- Requests and responses use JSON except media files and gateway redirects.
- Frontend requests use `credentials: include`.
- Customer authentication uses a Laravel HttpOnly session cookie, never LocalStorage bearer tokens.
- Mutating first-party requests follow Laravel Sanctum CSRF protection.
- `X-Request-ID` is accepted and returned.
- Public domain identifiers are ULIDs or other non-sequential identifiers.
- Integer application prices are expressed in toman.
- Client totals, inventory, delivery fees, ownership, fulfillment and payment-state claims are never authoritative.
- Errors never expose stack traces, credentials, raw provider payloads, internal notes or unhashed IP addresses.
- The frontend must branch on stable `code` values rather than matching Persian error messages.

## Frozen envelopes

Success:

```json
{
  "success": true,
  "data": {},
  "message": "optional message",
  "meta": {
    "requestId": "uuid",
    "apiVersion": "1",
    "contractVersion": "2026-07-20-phase-16"
  }
}
```

Error:

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "اطلاعات ارسال‌شده معتبر نیست.",
  "errors": {
    "field": ["validation message"]
  },
  "meta": {
    "requestId": "uuid",
    "apiVersion": "1",
    "contractVersion": "2026-07-20-phase-16"
  }
}
```

Stable codes and HTTP mappings are documented in `docs/API_ERRORS_AND_PAGINATION.md`.

## Frozen pagination

Paginated endpoints place items in `data` and use:

```json
{
  "meta": {
    "pagination": {
      "page": 1,
      "perPage": 12,
      "total": 24,
      "totalPages": 2,
      "from": 1,
      "to": 12,
      "hasMore": true
    }
  }
}
```

Catalog and posts default to 12, maximum 48. Account orders and reviews default to 10, maximum 30. Invalid values return `validation_failed`; they are not silently clamped.

## System

```text
GET /api/system/health
GET /api/system/ready
GET /api/system/meta
GET /api/system/contracts
GET /api/system/openapi
```

`GET /api/system/contracts` reports `backend_complete=ready`. The OpenAPI document excludes inherited `/api/v1` routes.

## Catalog

```text
GET /api/catalog/categories
GET /api/catalog/products
GET /api/catalog/products/{slug}
GET /api/catalog/products/{slug}/reviews
```

Frozen product filters:

- `category`
- `search`, maximum 100 characters
- `featured`
- `requiresCooling`
- `inStock`
- `sort`: `featured`, `newest`, `name`, `price-asc`, `price-desc`
- `page`, `perPage`

Products use server-calculated Variant prices and reservation-aware stock. Public reviews include approved verified-purchase reviews only.

## Customer authentication and account

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

OTP challenges are short-lived, one-time, attempt-limited, hashed and rate-limited. Address identifiers are customer-owned public ULIDs. Missing and cross-customer resources both return HTTP 404 with `code=resource_not_found`.

Address request:

```json
{
  "title": "خانه",
  "recipientName": "نام گیرنده",
  "mobile": "09123456789",
  "province": "تهران",
  "city": "تهران",
  "address": "آدرس کامل",
  "postalCode": "1234567890",
  "isDefault": true
}
```

## Delivery and checkout

```text
GET  /api/delivery/options
POST /api/checkout
GET  /api/account/orders
GET  /api/account/orders/{orderId}
POST /api/account/orders/{orderId}/cancel
```

Delivery options accept optional `province`, `city`, `subtotalToman` and `requiresCooling`. The response is informational; checkout recalculates the matched zone and every fee.

Checkout requires:

```text
Idempotency-Key: a-unique-random-value-at-least-16-characters
```

Saved-address checkout:

```json
{
  "addressId": "01K...ADDRESS",
  "deliveryMethod": "standard",
  "items": [
    {
      "variantId": "01K...VARIANT",
      "quantity": 2
    }
  ]
}
```

One-time recipient snapshot:

```json
{
  "customer": {
    "fullName": "نام گیرنده",
    "mobile": "09123456789",
    "province": "تهران",
    "city": "تهران",
    "address": "آدرس کامل",
    "postalCode": "1234567890",
    "notes": "اختیاری"
  },
  "deliveryMethod": "standard",
  "items": [
    {
      "variantId": "01K...VARIANT",
      "quantity": 2
    }
  ]
}
```

The request must never include authoritative item price, subtotal, delivery fee, packaging fee, discount or grand total.

The server:

- locks referenced Variants in stable order
- validates active category/product/Variant state
- subtracts active unexpired reservations
- calculates item totals and all fees
- resolves the most specific delivery zone
- enforces cooling, minimum order and daily capacity
- snapshots zone and preparation range
- creates immutable items and active reservations
- replays only an exact customer-scoped idempotent request

Order resources expose public fulfillment timestamps, delivery-zone summary and timeline, never internal admin notes.

## Payments

```text
POST /api/orders/{orderId}/payments
POST /api/payments/verify
POST /api/payments/zarinpal/verify
```

Payment initiation requires `Idempotency-Key`. Checkout never starts a gateway redirect. Callback status is a hint only; the backend verifies the recorded provider, authority and immutable server amount.

Only verified payment may atomically:

- mark the attempt verified
- mark the order paid
- consume reservations
- decrement physical stock
- queue `order.paid`

Duplicate verified callbacks never consume inventory twice. Payment credentials remain server-only and disabled until Phase 20.

## Store content and trust

```text
GET /api/store/settings
GET /api/store/pages/{slug}
GET /api/store/faqs
GET /api/store/gallery
GET /api/store/posts
GET /api/store/posts/{slug}
GET /api/store/cities/{slug}
```

Posts support `category`, `search`, `page` and `perPage`. FAQ supports `category`. Only active or published bakery content is returned.

The eNAMAD badge code is returned only when its admin setting is enabled and a non-empty external badge code exists; otherwise `badgeCode` is null.

## Verified-purchase reviews

```text
GET  /api/catalog/products/{slug}/reviews
POST /api/account/orders/{orderId}/reviews
```

Review request:

```json
{
  "orderItemId": "01K...ORDER_ITEM",
  "rating": 5,
  "title": "عالی",
  "body": "محصول تازه و باکیفیت بود."
}
```

The order must belong to the customer and be delivered. The public order-item ULID must belong to that order. One review per customer/order item is accepted. New reviews stay pending until moderation.

## Contact, gift and corporate inquiries

```text
POST /api/inquiries
```

```json
{
  "type": "corporate",
  "fullName": "شرکت نمونه",
  "mobile": "09123456789",
  "email": "buyer@example.com",
  "subject": "سفارش سازمانی",
  "message": "متن درخواست",
  "metadata": {
    "quantity": 100
  },
  "website": ""
}
```

Supported types are `contact`, `gift` and `corporate`. The route has rate limiting, honeypot validation, duplicate protection, normalized mobile data and HMAC-hashed IP storage.

## Fulfillment operations

Customer-facing state values:

```text
awaiting_payment
paid
confirmed
preparing
ready
dispatched
delivered
cancelled
expired
```

Admin transitions are controlled by the backend state machine. Pickup moves from ready directly to delivered. Other methods require dispatched with a tracking code before delivered.

Cancellation before payment releases reservations. Cancellation after payment restores consumed stock exactly once and marks reservations `restocked`. It does not claim that a financial refund occurred.

## Notification outbox

Order events are queued transactionally and dispatched by `notifications:dispatch`. Destinations are encrypted at rest.

Providers:

- `disabled`: leaves rows pending
- `testing`: outside production only
- `kavenegar`: requires a server-side key

No SMS credential or raw provider response is exposed to the storefront.

## Legacy API policy

`/api/v1/*` is inherited ToolMaster compatibility only:

- excluded from OpenAPI
- prohibited for new Winimi functionality
- disabled by default in production
- when disabled, returns HTTP 404 with `code=legacy_api_disabled`
- the Phase 17 frontend must never depend on it

## Contract change policy

Phase 17 may implement a typed client against this contract. Any backend change to paths, required fields, stable error codes, pagination, sort values, ownership behavior, payment semantics or public identifiers requires a new explicit contract version and regression review; it must not be introduced silently during frontend work.
