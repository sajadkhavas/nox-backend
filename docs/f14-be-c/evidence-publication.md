# Evidence and publication

## States

Evidence:

- `missing`
- `pending`
- `verified`

Publication:

- `draft`
- `published`
- `archived`

`existence of a value != verification of that value`.

## Evidence record

`ProductEvidence` stores:

- product
- fact key
- evidence state
- source reference
- reviewed timestamp
- reviewer

Supported fact keys:

`name, media, price, previous_price, colors, sizes, stock, description, material, care, fit, sku, collection_membership`

Media assets also carry their own verification state because an individual image can be pending/missing even when other media are verified.

## Publication guard

Moving a Product into `published` requires:

- at least one active apparel variant with active Color, active Size, SKU, and positive regular price
- at least one verified ProductMediaAsset
- verified evidence for the core facts: name, media, price, colors, sizes, stock, SKU
- verified evidence for optional facts when those values are present
- previous-price evidence when a valid sale price exists
- collection-membership evidence when the product belongs to a Collection

A published product cannot mutate evidence-tracked textual facts in-place. It must first return to draft, then be edited/reviewed, then re-published. This prevents stale evidence from being reused after content changes.

The legacy `is_active` catalog flag remains temporarily available only for neutral API compatibility. BE-D owns the public API cutover to `publication_status`.
