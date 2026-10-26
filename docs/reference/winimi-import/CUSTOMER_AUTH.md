# Winimi Customer Authentication

Contract version: `2026-07-19-phase-12`

## Architecture

Customer authentication is isolated from Filament administrators:

- administrators remain in `users` with the `web` guard
- storefront customers live in `customers`
- storefront sessions use the `customer` session guard
- stateful requests use Laravel Sanctum's first-party middleware
- no customer Bearer token is stored in LocalStorage

## Endpoints

```text
POST  /api/auth/otp/request
POST  /api/auth/otp/verify
GET   /api/auth/me
POST  /api/auth/logout
PATCH /api/account/profile
```

Frontend requests must use `credentials: include`. In production the browser first obtains the CSRF cookie through Sanctum before state-changing requests.

## Request OTP

```json
{
  "mobile": "09123456789"
}
```

Accepted Iranian mobile formats include Persian/Arabic digits and `+98`, `0098`, `98` or leading-zero forms. The normalized stored customer mobile is always `09xxxxxxxxx`.

Response:

```json
{
  "success": true,
  "data": {
    "challengeId": "01K...ULID",
    "expiresIn": 120,
    "retryAfter": 60
  }
}
```

The endpoint never reveals whether a customer already exists.

## Verify OTP

```json
{
  "mobile": "09123456789",
  "challengeId": "01K...ULID",
  "code": "123456"
}
```

On success the server:

1. locks the challenge row
2. checks mobile binding, expiry, consumption and maximum attempts
3. checks the hashed code
4. creates or updates the customer
5. consumes the challenge
6. logs in with the `customer` guard
7. rotates the session ID

## OTP storage rules

- the code is stored only with Laravel `Hash`
- the challenge mobile payload is encrypted at rest
- a separate keyed mobile hash supports lookup and rate limiting
- IP and User-Agent values are stored only as keyed hashes
- a challenge is one-time and expires quickly
- failed attempts are persisted
- requesting a new challenge consumes earlier active challenges
- expired and consumed challenges are pruned hourly

## Providers

`SMS_PROVIDER=disabled` is the safe default.

Available drivers:

- `disabled`: request returns HTTP 503 and no challenge remains
- `testing`: local/testing only; sends no external SMS
- `kavenegar`: uses the server-only API key and verification template

The testing code is returned only when all conditions are true:

```env
APP_ENV=local
SMS_PROVIDER=testing
OTP_EXPOSE_TEST_CODE=true
```

Production must use:

```env
APP_ENV=production
APP_DEBUG=false
OTP_EXPOSE_TEST_CODE=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

## Rate limits

OTP request limits are keyed independently by IP and normalized mobile. OTP verification limits are keyed by IP and challenge ID. The service also enforces its own resend cooldown and maximum-attempt count.

## Customer profile

Authenticated customers may update:

```json
{
  "fullName": "نام مشتری",
  "email": "customer@example.com",
  "marketingConsent": true
}
```

The mobile number cannot be changed through the profile endpoint or Filament. A future mobile-change flow must require verification of both the current and replacement numbers.

## Filament administration

The `مشتریان` resource allows administrators to:

- inspect public ID, verified mobile, profile and timestamps
- update full name and email
- enable or disable an account

It intentionally does not allow creating customers, editing mobile numbers, deleting accounts in bulk or changing marketing consent on behalf of the customer.
