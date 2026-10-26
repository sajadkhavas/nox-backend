# LBB P3 — Backend/Admin/API Control Surface Matrix

Status: **IMPLEMENTED / ACCEPTANCE CANDIDATE**

## Locked identity

- P3 backend START/base: `4156b201c104f4cdff7148341ca3705b1289779f`
- Historical P2 `BACKEND_RELEASE_SHA`: `dd35070ddb168833d30adabde957b86b56da0542`
- P3 storefront contract: `2026-09-06-p3-storefront-v1`
- Last implementation exact-head Gate before this documentation sync: `34045191915` — **SUCCESS**
- Production/server mutation: **NO**

## Governing rule

Every merchant-editable storefront business/content/configuration/data surface must have an explicit Backend owner, a usable Admin/Filament surface where applicable, a versioned API contract and a matching Frontend live consumer. Developer-owned presentation structure remains in the Frontend repository.

## Final matrix

| Storefront surface | Backend owner | Admin | API contract | P3 result |
| --- | --- | --- | --- | --- |
| Products / variants / media / price / stock | Catalog + inventory domains | Existing resources | Existing `/api/v1/products*` and catalog APIs | **PASS** |
| Categories / category SEO | Category domain | Existing Category resource | Existing `/api/v1/categories*` | **PASS** |
| Collections | Collection domain | Existing Collection resource | Existing `/api/v1/collections*` | **PASS** |
| Journal | `Post` | Existing Post resource | `/api/v1/storefront/journal` + `/{slug}` | **PASS** |
| Lookbook | `GalleryItem` | Existing GalleryItem resource | `/api/v1/storefront/lookbook` | **PASS** |
| FAQ | `Faq` | Existing Faq resource | `/api/v1/storefront/faqs` | **PASS** |
| Safe static pages / page SEO | `ContentPage` | Existing ContentPage resource | `/api/v1/storefront/pages/{slug}` | **PASS** |
| Brand/contact/social/global SEO | Typed public `StoreSetting` | Existing StoreSetting resource | `/api/v1/storefront/bootstrap` | **PASS** |
| Announcement bar / navigation | Typed public `StoreSetting` JSON | Existing StoreSetting resource | `/api/v1/storefront/bootstrap` | **PASS** |
| Homepage / Hero / Brand Intro | Typed public `StoreSetting` + catalog | Existing StoreSetting/Product resources | bootstrap + existing product API | **PASS** |
| Shipping/business rules | DeliveryZone + StoreSetting | Existing resources | Existing `/api/v1/delivery/options` and commerce APIs | **PASS — activation deferred to P4** |
| Auth/account/cart/checkout/orders/returns | Customer/commerce domains | Existing operational resources | Existing `/api/v1` | **PASS — production activation deferred to P4** |
| Inventory/payment/notifications | Existing operational domains | Existing Admin/operational controls | Existing commerce APIs | **PASS — production activation deferred to P4** |

## Additive P3 storefront contract

P3 adds the following versioned public endpoints without breaking the frozen P2 catalog/auth/commerce contracts:

- `/api/v1/storefront/bootstrap`
- `/api/v1/storefront/pages/{slug}`
- `/api/v1/storefront/faqs`
- `/api/v1/storefront/lookbook`
- `/api/v1/storefront/journal`
- `/api/v1/storefront/journal/{slug}`

The bootstrap exposes only settings explicitly marked public. Private `StoreSetting` values remain excluded. Contract responses carry `2026-09-06-p3-storefront-v1` and the Frontend rejects a mismatched contract version.

## Structured public settings

P3 registers typed public settings for:

- brand identity and approved copy;
- public contact/social/location labels;
- announcement messages;
- shop/editorial/service/brand navigation;
- homepage presentation and Hero product selection;
- versioned first-visit Brand Intro;
- global SEO defaults.

Existing Filament resources are reused; P3 does not create duplicate Admin domains.

## Truth and safety gates

- Only verified LBB business truth is registered in the P3 settings migration.
- Safe `about` and `contact` content pages are registered for the live content contract.
- Terms, Privacy and Returns legal copy is not fabricated or force-published.
- Historical inherited `SiteDataSeeder` content for another business is not treated as a live LBB source.
- Private settings are not exposed by the public bootstrap.
- Frontend-authoritative price/stock remains forbidden.
- P3 does not deploy to Production and does not activate payment or real commerce.

## Quality coverage

The P3 Storefront Integration workflow verifies:

- Composer metadata/security;
- SQLite fresh migration and full suite with zero skips;
- PHP syntax, routes and readiness;
- OpenAPI contract/version/safety flags;
- P3 storefront-control and frozen-contract tests;
- Pint on the P3 PHP delta;
- foundation and secret-safety checks;
- MySQL 8.4 migration and P3/commerce/auth/Web Push regression;
- real two-process oversell race.

After this documentation sync, the new exact-head workflow run must also be **SUCCESS** before the source PR is merged.
