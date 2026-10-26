# Public API Contract

## Envelope

Successful responses use the existing LBB API envelope:

```json
{
  "success": true,
  "data": {},
  "meta": {
    "requestId": "uuid",
    "apiVersion": "1",
    "contractVersion": "..."
  }
}
```

Paginated responses add:

```json
{
  "meta": {
    "pagination": {
      "page": 1,
      "perPage": 12,
      "total": 42,
      "totalPages": 4,
      "from": 1,
      "to": 12,
      "hasMore": true
    },
    "links": {
      "self": "...",
      "next": "...",
      "previous": null
    }
  }
}
```

Errors use:

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "...",
  "errors": {},
  "meta": {
    "requestId": "uuid",
    "apiVersion": "1"
  }
}
```

Production exception text and stack traces are not part of the contract.

## Versioning

The public catalog contract is `/api/v1`. The version is also returned in `X-API-Version`.

## Read-only boundary

Every endpoint in this document is read-only. Checkout, inventory mutation, order creation, payment and customer data mutation are outside BE-D.
