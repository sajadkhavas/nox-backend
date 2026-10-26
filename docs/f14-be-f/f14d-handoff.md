# F14D handoff — frozen backend contract amendment

Frontend F14D must integrate against the BE-G accepted Wave 3 baseline plus the accepted F14-BE-F1 amendment. The required frontend contract version is `2026-08-09-f14-be-f1`.

## Frontend may rely on

- `/api/v1` paths documented in `docs/openapi.json`.
- Versioned customer session routes under `/api/v1/auth/*`.
- Versioned delivery discovery at `/api/v1/delivery/options`.
- Public catalog data being publication/evidence gated by the backend.
- Price, inventory, cart validation, quotes, totals, orders and payment state being backend authoritative.
- Integer Toman API money objects.
- Authenticated commerce returning 401/403 when no valid customer session exists.
- Checkout commit and payment initiation requiring `Idempotency-Key` (16–120 characters, `[A-Za-z0-9:_-]`).
- Payment capabilities failing closed when a real provider is unavailable.

## Browser session / Sanctum deployment contract

The storefront and API must be first-party hosts under the same top-level domain when using Sanctum SPA cookie authentication (for example `shop.example.com` and `api.example.com`). Production deployment must configure all of the following together:

- `FRONTEND_URL` / `FRONTEND_URLS` with the exact HTTPS storefront origin(s).
- `SANCTUM_STATEFUL_DOMAINS` with the storefront host(s), including ports when applicable.
- `SESSION_DOMAIN` so the session/XSRF cookies support the required sibling subdomains (for example `.example.com`).
- `SESSION_SECURE_COOKIE=true` in HTTPS production.
- `SESSION_HTTP_ONLY=true` for the session cookie.
- CORS `supports_credentials=true` and an explicit allowed storefront origin.

The browser client must call `/sanctum/csrf-cookie` with credentials before state-changing session requests, then URL-decode the `XSRF-TOKEN` cookie and send it as `X-XSRF-TOKEN`. The frontend F14D client implements this explicitly instead of relying on Axios defaults.

## Frontend must not

- Reintroduce frontend-authoritative price or stock.
- Invent endpoints, fields, provider success, tracking data, shipping promises or refund success.
- Treat legacy unversioned compatibility endpoints as the F14D contract.
- Treat a browser payment return as successful until `/api/v1/payments/verify` confirms it.
- Assume `backendComplete`, `frontendIntegrated` or `productionDeployed` before final deployment acceptance says so.

## Integration order

1. Build a typed client from the frozen OpenAPI/contract.
2. Replace prototype catalog adapters route by route.
3. Wire auth/account/order flows against real responses.
4. Keep browser-local cart state limited to user selections; validate price/stock/totals on the server.
5. Wire checkout quote, commit and payment only through backend capabilities.
6. Run frontend regression, SEO, RTL, accessibility, CSRF/session and fail-closed gates before registering F14D acceptance.
