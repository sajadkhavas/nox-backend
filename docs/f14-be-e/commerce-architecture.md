# Commerce Architecture

Flow: `CartValidationService -> CheckoutQuoteService -> CommerceCheckoutService -> InventoryLedgerService -> Order`. Quote is non-mutating. Commit revalidates server truth under transaction/row locks, creates immutable snapshots, then reserves stock. Payment verification consumes reservations through the same ledger. Order fulfillment drives Shipment state. Return, Exchange and Refund are separate domains.

`product_variants.stock_quantity` is retained as the materialized on-hand balance for compatibility and fast reads, but updates after creation are rejected unless executed inside the Inventory Ledger mutation context.
