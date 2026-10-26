# Order Lifecycle

Accepted states remain: awaiting_payment -> paid -> confirmed -> preparing -> ready -> dispatched -> delivered, with pickup skipping dispatched. Cancellation is allowed only by existing transition rules. Expiry applies while awaiting payment.

Order lines snapshot product/variant public identity, name, product code, SKU, color, size, unit price, quantity, line total and currency. Recipient/address and delivery money are also snapshots.

Paid cancellation restocks consumed inventory once through the ledger and creates a Refund Request state; it does not claim money was refunded.
