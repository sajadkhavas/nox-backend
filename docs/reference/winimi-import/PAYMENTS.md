# Winimi Payment Backend

Phase 14 implements a provider-ready, server-authoritative payment lifecycle. Payment remains disabled by default and no live gateway credential is required for development or CI.

## Endpoints

All endpoints require an authenticated active customer session.

```text
POST /api/orders/{orderId}/payments
POST /api/payments/verify
POST /api/payments/zarinpal/verify
```

The Zarinpal-specific verify route is a compatibility alias. Both verification routes use the provider recorded on the payment attempt; callback query parameters never select or override the provider.

## Initiation

Required header:

```text
Idempotency-Key: a-unique-random-value-at-least-16-characters
```

`POST /api/orders/{orderId}/payments`:

1. locks the customer-owned order
2. rejects paid, cancelled, expired or reservation-expired orders
3. replays an exact idempotency key
4. reuses a still-active pending attempt rather than creating parallel redirects
5. snapshots the server order amount
6. calls the configured provider outside the database transaction
7. records only sanitized provider metadata
8. returns a redirect URL and authority

A failed provider call marks the attempt failed but leaves the order and active inventory reservation available for a controlled retry with a new idempotency key.

## Verification

Example request:

```json
{
  "authority": "A000000000000000000000000000000001",
  "status": "OK"
}
```

The callback status is not proof of payment. The backend calls the provider verification API with the recorded authority and exact server-side amount. Only provider verification may mark an order paid.

On verified payment, one database transaction:

1. locks the payment attempt and order
2. verifies the attempt amount still equals the immutable order total
3. locks active inventory reservations and product Variants
4. decrements physical stock
5. marks reservations consumed
6. transitions the order to `paid`
7. records `paid_at`
8. marks the payment attempt verified with its reference ID

A repeated verified callback returns the existing result and does not decrement stock again.

## Attempt states

```text
initiated -> pending -> verified
initiated -> failed
pending -> failed
pending -> cancelled
pending -> expired
```

Failed and cancelled attempts do not consume reservations. A new attempt can be created while the order remains `awaiting_payment` and its reservation is active.

## Providers

### disabled

Safe default. Initiation and verification return service unavailable.

### testing

Available only outside production. It creates deterministic authorities and reference IDs, allowing full checkout/payment/inventory tests without external credentials.

### zarinpal

Provider adapter with separate request and verification calls, timeout handling, provider-code capture and response sanitization. The adapter refuses to run without a server-side Merchant ID.

## Safe configuration

```env
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=disabled
PAYMENT_CALLBACK_URL=https://winimibakery.com/payment/result
PAYMENT_CURRENCY=IRR
PAYMENT_AMOUNT_MULTIPLIER=10
PAYMENT_ATTEMPT_TTL_MINUTES=20
PAYMENT_TIMEOUT_SECONDS=10
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=true
```

Local or CI testing:

```env
PAYMENT_ENABLED=true
PAYMENT_PROVIDER=testing
```

Production before external activation:

```env
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=disabled
ZARINPAL_MERCHANT_ID=
```

The Merchant ID, provider request payloads, card PAN and card hash are never returned by storefront resources. Sensitive provider fields are removed before administrative metadata is persisted.

## Filament

`فروشگاه وینیمی > تلاش‌های پرداخت` provides read-only inspection of attempt status, order, amount, authority, reference ID and sanitized provider metadata. Administrators cannot create, edit or bulk-delete payment attempts.

## Validation

```bash
composer audit:payments
php artisan test --filter=Payment
composer check
```

The phase is internally complete when migrations, architecture audit, API tests, Filament smoke tests and the full backend validation pipeline pass. Live activation remains a Phase 20 external input.