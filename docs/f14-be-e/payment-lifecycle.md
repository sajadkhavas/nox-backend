# Payment Lifecycle

Existing provider architecture is preserved. Production defaults to disabled. The `testing` provider is not ready in production. Initiation amount comes only from the Order snapshot. Verification resolves a stored authority owned by the customer and checks Order amount before marking paid.

`payment_callback_events` records provider/authority/request fingerprint and processed outcome. Exact callback replays are detected before repeating a completed verification path. Provider payloads continue to be sanitized; secrets/card data are not stored by BE-E.

Refund execution is separate and disabled by default (`PAYMENT_REFUNDS_ENABLED=false`). No admin action can mark a refund completed without a provider-verified path/reference.
