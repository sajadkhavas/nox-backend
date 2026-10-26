# Winimi API Errors and Pagination

Contract version: `2026-07-20-phase-16`

This document is part of the frozen backend contract. Phase 17 may consume it but must not redefine it in the frontend.

## Response metadata

Every normal API success and error response contains:

```json
{
  "meta": {
    "requestId": "client-or-server-request-id",
    "apiVersion": "1",
    "contractVersion": "2026-07-20-phase-16"
  }
}
```

The frontend should include a unique `X-Request-ID` when possible and retain the returned value when reporting an error.

## Success envelope

```json
{
  "success": true,
  "data": {},
  "message": "optional human-readable message",
  "meta": {}
}
```

## Error envelope

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "اطلاعات ارسال‌شده معتبر نیست.",
  "errors": {
    "field": ["validation message"]
  },
  "meta": {}
}
```

The UI may display `message`, but branching logic must use `code` and HTTP status rather than matching Persian text.

## Frozen error codes

| Code | Typical HTTP status | Meaning |
|---|---:|---|
| `bad_request` | 400 | The request shape or protocol is invalid. |
| `authentication_required` | 401 | A valid customer session is required. |
| `access_denied` | 403 | The authenticated actor is not authorized. |
| `resource_not_found` | 404 | The public resource does not exist or is not owned by the customer. |
| `legacy_api_disabled` | 404 | A deprecated ToolMaster route is disabled. |
| `conflict` | 409 | Idempotency or current resource state conflicts with the request. |
| `validation_failed` | 422 | Field or business validation failed. |
| `rate_limited` | 429 | The caller exceeded the configured request limit. |
| `service_unavailable` | 503 | A required dependency is temporarily unavailable. |
| `internal_error` | 500 | An unexpected server error occurred. |
| `request_failed` | other 4xx | A non-specialized client request failed. |

Unknown exceptions never expose stack traces, SQL, credentials or provider payloads.

## Authentication and ownership

Customer resources use the `customer` guard and server-side ownership scopes. A resource owned by another customer returns the same `resource_not_found` response as a missing resource. This avoids disclosing that another customer's address, order, payment attempt or review target exists.

## Frozen pagination shape

Paginated endpoints return their item array in `data` and this object in `meta.pagination`:

```json
{
  "page": 1,
  "perPage": 12,
  "total": 24,
  "totalPages": 2,
  "from": 1,
  "to": 12,
  "hasMore": true
}
```

For an empty page, `from` and `to` are `null`.

### Limits

| Surface | Default | Maximum |
|---|---:|---:|
| Catalog and bakery posts | 12 | 48 |
| Account orders and product reviews | 10 | 30 |

`page` is always a positive integer. Values beyond the maximum return `validation_failed`; they are not silently clamped.

## Frozen filters and sorting

### `GET /api/catalog/products`

- `category`: category slug
- `search`: maximum 100 characters
- `featured`: boolean
- `requiresCooling`: boolean
- `inStock`: boolean
- `sort`: `featured`, `newest`, `name`, `price-asc`, `price-desc`
- `page`, `perPage`

The server remains authoritative for current price, active status and reservation-aware stock.

### `GET /api/store/posts`

- `category`: maximum 120 characters
- `search`: maximum 100 characters
- `page`, `perPage`

### `GET /api/catalog/products/{slug}/reviews`

- `page`, `perPage`

Only approved verified-purchase reviews appear publicly.

### `GET /api/account/orders`

- `page`, `perPage`

Only orders owned by the authenticated customer are returned, newest first.
