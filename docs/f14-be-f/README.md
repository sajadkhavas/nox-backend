# F14-BE-F — API / OpenAPI Freeze

Contract version: `2026-08-09-f14-be-f`

This phase freezes the backend contract that the frontend may integrate against. The canonical machine-readable artifact is `docs/openapi.json`, served by `GET /api/system/openapi` with an ETag.

## Frozen integration surface

- Public read catalog under `/api/v1`: categories, products, collections, drops, colors, sizes, facets and search.
- Authenticated commerce under `/api/v1`: cart validation, quote, checkout commit, customer orders, payment initiation/verification, returns, exchanges and refunds.
- Integer Toman money at the API boundary.
- Server-authoritative price, availability, reservations, order totals and payment state.
- `Idempotency-Key` is mandatory for checkout commit and payment initiation.
- Authenticated commerce is customer-session protected and rate limited.
- Machine commerce errors remain stable for F14D integration.

## Explicitly not claimed

- Legacy unversioned compatibility routes are not the frozen F14D contract.
- Frontend integration has not happened yet.
- Production deployment has not happened yet.
- Payment/refund/SMS success is never simulated: providers remain fail-closed without real configuration and credentials.
- BE-G final backend acceptance is still required before Wave 3 is closed.

## Change policy

After this freeze, any breaking `/api/v1` change requires a new contract version and a new reviewed freeze. F14D must consume this contract rather than inventing backend fields or endpoints.
