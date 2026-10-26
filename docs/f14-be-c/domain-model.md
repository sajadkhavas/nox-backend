# Domain model

## Sellable apparel identity

A sellable apparel variant is:

`Product + Color + Size + SKU + price + stock`

The database enforces uniqueness for `(product_id, color_id, size_id)` when the apparel keys are present, and SKU remains globally unique.

Existing neutral variants remain migratable because color/size are nullable at schema level. A product that is intentionally moved into `published` state is guarded so its active apparel variants must have active Color/Size entities and a SKU.

## Collection vs Drop

LBB uses two entities.

- **Collection** is durable merchandising taxonomy/editorial grouping. It has no required operational dates.
- **Drop** is a launch/campaign grouping. `starts_at` and `ends_at` are optional because dates are only stored when operationally meaningful.

Both have publication state, featured state, ordering, slug, description, and SEO foundation fields.

## Deletion policy

- Product: existing SoftDeletes retained.
- ProductVariant: SoftDeletes added.
- Color / Size / Collection / Drop / SizeGuide / ProductMediaAsset: SoftDeletes.
- Foreign keys use `restrictOnDelete` where historical references should not silently disappear.
- Order items already snapshot product name, variant name, public IDs, product code, SKU, and unit price; BE-E owns any larger order snapshot extension for color/size labels.

Hard deletion of referenced apparel domain rows is intentionally not a normal Filament workflow.

## Inventory

`stock_quantity` is the current stock-on-hand storage carried from the neutral baseline.

Domain aliases:

- `stock_on_hand = stock_quantity`
- `reserved_quantity = active inventory reservations`
- `available_quantity = max(0, stock_on_hand - reserved_quantity)`

Clients are not authoritative for price or stock. Inventory ledger mutation is deferred to BE-E.

## SEO foundation

Product and Category keep slug/meta fields and gain publication state. Collection and Drop include slug, meta title, meta description, and publication state. Canonical/sitemap/public SSR policy remains BE-D/F20 work.
