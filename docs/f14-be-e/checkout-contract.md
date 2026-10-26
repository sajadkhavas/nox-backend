# Checkout Contract

`POST /api/v1/cart/validate` validates without mutation.

`POST /api/v1/checkout/quote` persists a short-lived server quote. It does not reserve inventory.

`POST /api/v1/checkout/commit` accepts `quoteId` plus `Idempotency-Key`. It locks Quote/Order/Variants, revalidates current price, publication, availability and delivery fees, creates Order and line snapshots, reserves inventory through the ledger, creates Shipment state and consumes the quote. Any exception rolls back the whole transaction.

Money follows BE-D: integer whole Toman plus explicit `TOMAN` currency.
