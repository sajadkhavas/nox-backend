# Winimi Bakery single-server topology

Status: prepared for Phase 19 deployment.

The frontend and backend remain separate GitHub repositories and separate build artifacts, but both are deployed on one Linux server. One server does not require one repository or one process.

## Public routing

```text
Internet
   |
   v
Nginx on one server
   |-- winimibakery.com      -> static React/Vite build
   |-- www.winimibakery.com  -> redirect to winimibakery.com
   |-- api.winimibakery.com  -> Laravel public/index.php through PHP-FPM
   |-- api.winimibakery.com/admin -> Filament administration
   |-- api.winimibakery.com/storage -> persistent public media
```

The physical server is shared. Nginx uses two virtual hosts so frontend caching and Laravel security rules remain explicit.

## Server directories

```text
/var/www/winimi/frontend/current   # immutable Vite dist release
/var/www/winimi/backend/current    # Laravel release
/var/www/winimi/backend/shared/.env
/var/www/winimi/backend/shared/storage
/var/backups/winimi
```

The backend `.env`, database credentials, Zarinpal Merchant ID, SMS key/template and backup encryption password exist only in the shared server directory. They are never committed and never copied into the frontend build.

## Required services

- Nginx
- PHP 8.3 FPM with the required Laravel extensions
- database service selected in Phase 19
- supervised Laravel queue worker
- one-minute Laravel scheduler trigger
- persistent media storage
- TLS certificates for both public hosts
- log rotation, health checks, backup and rollback

## Cookie and CORS boundary

Production values are configured server-side:

```env
APP_URL=https://api.winimibakery.com
FRONTEND_URL=https://winimibakery.com
FRONTEND_URLS=https://winimibakery.com,https://www.winimibakery.com
SANCTUM_STATEFUL_DOMAINS=winimibakery.com,www.winimibakery.com,api.winimibakery.com
SESSION_DOMAIN=.winimibakery.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

The frontend build uses only the public API origin:

```env
VITE_USE_BACKEND=true
VITE_API_BASE_URL=https://api.winimibakery.com
VITE_ALLOW_DEV_MOCKS=false
```

No payment, SMS, eNAMAD or database secret is a `VITE_*` value.

## Phase boundary

Phase 18 verifies the integration locally and in GitHub Actions using two loopback ports on one runner. Phase 19 installs the same separation on the single production server, configures DNS/TLS, starts workers and performs disabled-provider smoke tests. Phase 20 supplies only the three external activation values.
