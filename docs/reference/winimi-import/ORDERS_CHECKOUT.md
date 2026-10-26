# Winimi Checkout, Orders and Inventory Reservations

Phase 13 implements the order boundary before payment-provider integration.

## Operational default

Checkout is disabled unless explicitly configured:

```env
CHECKOUT_ENABLED=false
DELIVERY_STANDARD_ENABLED=false
DELIVERY_CHILLED_ENABLED=false
DELIVERY_PICKUP_ENABLED=false
```

Production must approve delivery availability and fees before changing these values.

## Endpoints

```text
POST /api/checkout
GET  /api/account/orders
GET  /api/account/orders/{orderId}
POST /api/account/orders/{orderId}/cancel
```

All endpoints require the authenticated `customer` session and an active customer account.

## Checkout request

`POST /api/checkout` requires:

```text
Idempotency-Key: a-unique-random-value-at-least-16-characters
```

The request contains recipient data, a delivery method and only Variant identifiers plus quantities. It must not contain authoritative price fields.

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
      "variantId": "01K...ULID",
      "quantity": 2
    }
  ]
}
```

## Idempotency

The server stores a customer-scoped idempotency key and a SHA-256 hash of the canonical request.

- replaying the same key with the same request returns the existing order
- replaying the same key with a different request returns HTTP 409
- a unique database constraint prevents duplicate orders during concurrent requests

## Server-authoritative totals

The backend locks active Variant rows and calculates:

- current sale or regular unit price
- line totals
- subtotal
- configured delivery fee
- configured packaging fee
- grand total

Client subtotal, total, discount, stock and item-price fields are never accepted.

## Immutable snapshots

Each `order_items` row stores a snapshot of:

- product and Variant public IDs
- product and Variant names
- product code and SKU
- weight and cooling requirement
- unit price
- quantity
- line total

Later catalog edits do not rewrite historical orders.

## Inventory lifecycle

Checkout creates an `active` reservation instead of decrementing physical stock.

```text
active -> consumed
active -> released
active -> expired
```

- `consumed`: Phase 14 verified payment decrements physical stock
- `released`: the customer cancels before payment
- `expired`: payment was not completed before the reservation deadline

The catalog subtracts active, unexpired reservations from publicly available stock.

The scheduled command runs every minute without overlap:

```bash
php artisan inventory:release-expired
```

## Cooling rules

A cart containing any cooling-required product cannot use `standard` delivery. It must use `chilled` delivery or `pickup`.

No delivery method is enabled by default because service areas and fees are business decisions that must be approved before launch.

## Order ownership

Every account query is scoped by the authenticated customer ID. Looking up another customer's order returns 404 rather than revealing its existence.

## Payment boundary

Phase 13 returns:

```json
{
  "payment": {
    "available": false,
    "state": "not-configured"
  }
}
```

No order becomes paid from checkout. Phase 14 will create payment attempts and consume reservations only after server-side provider verification.
