# F14-BE-G — Backend Final Acceptance

Frozen API contract: `2026-08-09-f14-be-f`

F14-BE-G is the final engineering acceptance of the LBB backend Wave 3. It does not introduce a new API version and must not alter the F14-BE-F OpenAPI artifact.

## Accepted backend scope

- Neutral LBB Laravel/Filament foundation.
- Apparel domain: categories, products, colors, sizes, variants, collections, drops, size guides, media and evidence/publication controls.
- Versioned public `/api/v1` catalog and discovery API.
- Customer authentication/account boundary.
- Server-authoritative cart validation, quote, inventory reservation, checkout/order operations and idempotency.
- Payment initiation/verification contract with fail-closed provider behavior.
- Returns, exchanges, refunds and operational/admin surfaces implemented by the backend phases.
- Frozen OpenAPI/contract handoff for F14D.
- SQLite and MySQL validation, including the real two-process oversell race gate.

## Immutable freeze boundary

`docs/openapi.json` remains the F14-BE-F artifact with version `2026-08-09-f14-be-f`. BE-G only accepts the backend implementation against that contract. Any breaking `/api/v1` change after acceptance requires a new reviewed contract version; it must not be silently introduced during frontend integration.

## Truth boundary after acceptance

`backend_complete=true` means the planned backend engineering phases through BE-G have passed their acceptance gates. It does **not** mean the storefront is already wired to the backend or that the system is deployed to production.

The following remain explicitly false until separately completed and registered:

- `frontend_integrated=false`
- `production_deployed=false`

Payment, refund and SMS execution remain fail-closed unless real provider configuration and credentials are supplied in the deployment environment. No production transaction success is simulated.

## Downstream handoff

After this BE-G head is revalidated on `integration/backend-wave3-reviewed`, frontend phase F14D may consume the frozen `2026-08-09-f14-be-f` contract. F14D must not invent backend fields/endpoints or reintroduce frontend-authoritative price, stock, order or payment state.
