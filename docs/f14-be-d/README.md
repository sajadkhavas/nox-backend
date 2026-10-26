# F14-BE-D — Public Commerce API & Catalog Contract

This phase exposes the BE-C apparel domain through a versioned, read-only public contract under `/api/v1`.

## Principles

- The backend is authoritative for publication, price, variant validity and availability.
- Public payloads never expose numeric database IDs or raw stock quantities.
- Stored values are not public facts unless the relevant evidence is verified.
- Legacy unversioned catalog routes remain untouched for regression compatibility; new frontend work must use `/api/v1`.
- This phase does not implement checkout, payment, order mutations, cart persistence or inventory ledger mutations.

## Endpoints

- `GET /api/v1/categories`
- `GET /api/v1/categories/{slug}`
- `GET /api/v1/products`
- `GET /api/v1/products/{slug}`
- `GET /api/v1/search`
- `GET /api/v1/collections`
- `GET /api/v1/collections/{slug}`
- `GET /api/v1/drops`
- `GET /api/v1/drops/{slug}`
- `GET /api/v1/colors`
- `GET /api/v1/sizes`
- `GET /api/v1/catalog/facets`

Catalog routes use `public-catalog` throttling. Search has the stricter `public-search` limiter.

## Public identity

All apparel entities use the ULID `public_id` created by BE-C. Numeric primary keys are deliberately absent from the public contract.

## Public evidence boundary

A product only enters the public product dataset when it is active, published, not future-published, belongs to an active/published category, has at least one sellable apparel variant, and has verified evidence for name, media, price, colors, sizes, stock and SKU.

Optional facts are fail-closed:
- description → `description`
- material/fabric composition → `material`
- fit → `fit`
- care → `care`
- sale compare-at price → `previous_price`
- collection membership → `collection_membership`
- size guide/measurements → `size_guide`

See the other documents in this folder for the precise contracts.
