# Security

Private commerce routes require `auth:customer` and `customer.active`. Order/Return/Exchange/Refund queries are always ownership-scoped. Client money and stock are untrusted. Quantity limits are validated. Variant lookup uses public ULIDs. Sorting/filter SQL is not introduced by BE-E mutable operations.

Sensitive Filament commerce resources are restricted to `super_admin`; state changes call domain services and require confirmation where destructive. Direct Variant stock update is rejected outside the ledger context. Commerce audit metadata strips common secret/card credential keys.
