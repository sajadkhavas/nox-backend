# Variant Availability Contract

The frontend must never infer a valid variant from Product + Color + Size. It consumes the variant matrix returned by the backend.

## Public stock states

- `in_stock`: sellable and available quantity is above the configured low-stock threshold.
- `low_stock`: sellable, positive available quantity at or below the variant threshold.
- `out_of_stock`: sellable identity but available quantity is zero.
- `unavailable`: identity itself is not publicly sellable.

Available quantity is computed from server-owned stock less active, unexpired reservations. The exact number is intentionally not serialized.

Public availability is discovery information only. It is not a reservation and is not a checkout guarantee. BE-E must validate/lock inventory again during checkout.
