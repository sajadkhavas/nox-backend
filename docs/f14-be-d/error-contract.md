# Error Contract

BE-D reuses the Wave 1 `ApiResponse` and `ApiExceptionRenderer` foundation.

All public API errors have:

- `success: false`
- `code`
- safe `message`
- `errors` (validation details when applicable)
- `meta.requestId`
- `meta.apiVersion`

Expected classes include:
- 401 authentication required
- 403 access denied
- 404 resource not found
- 409 conflict
- 422 validation failed
- 429 rate limited
- 5xx internal error

No raw exception, SQL error, stack trace or internal storage path is a supported production payload.
