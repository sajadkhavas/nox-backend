# Catalog Query Contract

## Products

`GET /api/v1/products`

Supported parameters:

| Parameter | Meaning |
|---|---|
| `q` | deterministic database-backed search on public product name/slug |
| `category` | category slug |
| `collection` | published collection slug; membership evidence must be verified |
| `color` | active color slug |
| `size` | active size code |
| `availability` | `in_stock` or `out_of_stock` |
| `min_price` | integer Toman lower bound |
| `max_price` | integer Toman upper bound |
| `sort` | `newest`, `price_asc`, `price_desc` |
| `page` | 1-based page |
| `per_page` | 1..configured catalog max (48 by default) |

Unknown sort values are validation errors and are never interpolated into SQL ordering.

## Search

`GET /api/v1/search?q=...`

Search is server-side, deterministic, paginated and limited to public product identity fields. Draft/archived products never appear. No external search engine is introduced in this phase.

## Facets

`GET /api/v1/catalog/facets`

Facets are a global snapshot derived from the valid published catalog dataset, not from mock/static frontend lists. It returns available categories, collections with verified public membership, colors, sizes, catalog price bounds, availability values and supported sorts.
