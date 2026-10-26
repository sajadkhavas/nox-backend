# Variant contract

## Invariants

1. SKU is globally unique.
2. Product/color/size combination is unique when color and size are present.
3. A published product cannot have an active apparel variant without color, size, and SKU.
4. Inactive Color or Size makes a variant non-sellable.
5. Price and stock are server-owned.
6. `previous_price_toman` is derived from `regular_price_toman` only when a valid lower `sale_price_toman` exists.
7. Historical order snapshots are not rewritten by variant rename/deactivation.

## Variant Matrix

The Filament edit action takes explicit:

- Color IDs
- Size IDs
- SKU prefix
- initial regular price
- initial stock

SKU is generated only from the **administrator-supplied prefix** plus stable Color/Size codes; it is never silently generated from the Product name.

Matrix rules:

- existing combinations are preserved exactly
- existing stock/price/SKU are never reset by matrix generation
- soft-deleted combinations are skipped and are not automatically restored
- SKU collisions abort the transaction
- matrix generation only creates missing combinations
- generated variants start inactive, so verification/activation remains explicit

Deleting a Color/Size selection from a later matrix run does not delete old variants.
