# Product Payload

## Listing summary

A listing item includes:

- `publicId`: ULID
- `slug`
- `name`
- `shortDescription`: nullable; only with verified description evidence
- `category`
- `price.from` / `price.to`: nullable integer Toman money objects
- `availability`: boolean
- `stockState`: `in_stock | low_stock | out_of_stock | unavailable`
- `colors`
- `sizes`
- `primaryImage`: nullable
- `seo`

It never includes numeric database IDs, raw inventory quantities, reservation quantities or evidence source references.

## Detail additions

Detail adds:

- `description`
- `publication` (`published`)
- `collections`
- `drops`
- `variants`
- `media`
- `material`
- `fabricComposition`
- `fit`
- `care`
- `sizeGuide`
- `breadcrumbs`

Optional apparel facts are nullable/empty when their evidence is not verified.

## Variant

```json
{
  "publicId": "01...",
  "sku": "SKU-...",
  "color": {},
  "size": {},
  "price": {"amount": 1250000, "currency": "TOMAN"},
  "compareAtPrice": null,
  "availability": true,
  "stockState": "in_stock",
  "isActive": true,
  "mediaPublicIds": []
}
```

Inactive or non-sellable variants are never serialized.
