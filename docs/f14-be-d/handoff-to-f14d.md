# Frontend Handoff — F14D / F15

Use only `/api/v1` for new catalog integration.

## Discovery

- `GET /api/v1/products`
  - query params: `q, category, collection, color, size, availability, min_price, max_price, sort, page, per_page`
  - returns product summaries plus pagination metadata
- `GET /api/v1/search?q=...`
  - same paginated product shape
  - stricter rate limit
- `GET /api/v1/catalog/facets`
  - authoritative filter options

## PDP

- `GET /api/v1/products/{slug}`
  - `variants` is the authoritative selectable matrix
  - `availability`/`stockState` are display signals
  - do not calculate compare-at prices
  - do not infer variants
  - do not expose or expect raw stock counts
  - nullable facts must remain absent/null in UI rather than be fabricated

## Taxonomy

- `GET /api/v1/categories`
- `GET /api/v1/categories/{slug}`
- `GET /api/v1/collections`
- `GET /api/v1/collections/{slug}` (includes paginated products)
- `GET /api/v1/drops`
- `GET /api/v1/drops/{slug}`
- `GET /api/v1/colors`
- `GET /api/v1/sizes`

## Money

`{amount: integer, currency: "TOMAN"}`.

## Errors

Read `code`, `message`, optional `errors`, and keep `meta.requestId` for diagnostics. Do not parse framework exception strings.

## SEO

Build SSR metadata from the `seo` DTO. Backend provides data, not HTML tags.
