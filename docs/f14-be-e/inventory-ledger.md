# Inventory Ledger

`inventory_ledger_entries` records variant, order/reservation correlation, event type, on-hand delta, reserved delta and post-mutation on-hand/reserved/available balances. Events include opening balance, reservation create/release/expire, sale, return, exchange in/out and manual correction.

Every service mutation locks the variant row with `lockForUpdate`. Manual correction cannot reduce on-hand below zero or below active reserved quantity. Ledger idempotency keys prevent double consume/restock/adjustment.
