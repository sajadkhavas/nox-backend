# Winimi Store Operations Backend

Phase 15 completes the daily store-operations backend while keeping payment, eNAMAD and production SMS safely disabled until their external values are supplied.

## Customer addresses

Authenticated active customers can manage reusable addresses:

```text
GET    /api/account/addresses
POST   /api/account/addresses
PUT    /api/account/addresses/{addressId}
DELETE /api/account/addresses/{addressId}
```

Addresses use public ULIDs and are always scoped to the authenticated customer. The first active address becomes default. Setting another address as default clears the previous default inside the server-side model boundary. Checkout may receive `addressId`; the server resolves the owned active address and snapshots its recipient data into the order.

## Delivery zones and checkout quote

```text
GET /api/delivery/options?province=تهران&city=تهران&subtotalToman=200000&requiresCooling=false
```

Delivery zones are managed in Filament and may define:

- province/city matching with wildcard fallback
- standard, chilled and pickup availability
- delivery and packaging fees
- minimum order value
- free-delivery threshold
- preparation min/max days
- daily order capacity
- resolution priority and active state

The most specific active city/province zone wins. Checkout calculates product totals first, then resolves the zone and snapshots `delivery_zone_id`, delivery fee, packaging fee and preparation window. Legacy environment fee values remain only as a safe migration/testing fallback when no delivery-zone record matches.

## Fulfillment state machine

```text
awaiting_payment -> cancelled
paid -> confirmed -> preparing -> ready -> dispatched -> delivered
                                      \-> delivered (pickup only)
paid/confirmed/preparing/ready -> cancelled
```

Rules:

- direct arbitrary status editing is disabled
- non-pickup delivery requires a tracking code before `dispatched`
- non-pickup orders cannot become delivered directly from ready
- pickup orders never enter dispatched and may become delivered from ready
- every transition is row-locked, recorded in status history and timestamped
- internal notes are visible only in Filament and never exposed through storefront resources

### Cancellation and inventory

Unpaid cancellation releases active reservations without changing physical stock.

Cancellation after verified payment restores every consumed reservation exactly once, changes it to `restocked`, records `restocked_at` and increments physical stock under row locks. The order becomes cancelled, but `payment_status` remains paid until an actual financial refund is completed outside this phase. This prevents the system from falsely claiming that money has been returned.

## Content and trust

Public bakery content is independent from the inherited ToolMaster `/api/v1` domain:

```text
GET /api/store/settings
GET /api/store/pages/{slug}
GET /api/store/faqs
GET /api/store/gallery
GET /api/store/posts
GET /api/store/posts/{slug}
GET /api/store/cities/{slug}
```

Filament manages public contact/social settings, homepage and legal/shipping pages, FAQ, gallery, bakery blog and city pages.

### eNAMAD boundary

`trust.enamad_badge_code` is non-public storage. The API returns the badge code only when:

1. `trust.enamad_enabled` is true, and
2. a non-empty badge code has been supplied.

Before Phase 20 the slot remains disabled and returns `badgeCode: null`.

## Verified-purchase reviews

```text
GET  /api/catalog/products/{slug}/reviews
POST /api/account/orders/{orderId}/reviews
```

A review is accepted only when the order belongs to the authenticated customer, the order is delivered, and the referenced order item belongs to that order. One review per customer/order-item is allowed. New reviews are pending and appear publicly only after admin approval.

## Contact, gift and corporate inquiries

```text
POST /api/inquiries
```

Supported types are `contact`, `gift` and `corporate`. The endpoint has rate limiting, a honeypot, duplicate-message protection, normalized mobile numbers and HMAC-hashed IP storage. Raw IP addresses are never persisted.

## Notification outbox

Order notifications are transactionally queued in `notification_outbox` during payment and fulfillment transitions. The scheduler runs:

```bash
php artisan notifications:dispatch --limit=100
```

every minute without overlap.

Providers:

- `disabled`: safe default; pending records remain untouched
- `testing`: deterministic and allowed only outside production
- `kavenegar`: requires server-side `KAVENEGAR_API_KEY`

The destination is encrypted at rest. Stale processing records are recovered. Provider failures are retried with delay up to the configured maximum. Filament masks destinations and allows a failed record to be requeued, but administrators cannot manually create outbound messages.

Safe defaults:

```env
ORDER_SMS_PROVIDER=disabled
KAVENEGAR_API_KEY=
NOTIFICATION_MAX_ATTEMPTS=5
NOTIFICATION_RETRY_SECONDS=60
```

No Kavenegar credential or destination is exposed through storefront API resources or `VITE_*` variables.

## Filament operations

The admin panel includes:

- delivery zones
- store settings
- customer-address inspection
- controlled order fulfillment and internal notes
- bakery pages, FAQ, gallery, blog and city pages
- verified-purchase review moderation
- contact/gift/corporate inquiry tracking
- notification templates and outbox inspection

## Validation

```bash
composer audit:operations
php artisan test --filter=StoreOperations
php artisan test --filter=OrderFulfillment
php artisan test --filter=NotificationOutbox
composer check
```

Phase 15 is internally complete only after fresh migrations, route/config cache, Filament discovery, Pint, all regression tests and dependency security checks pass.
