# Winimi Query and Index Review

Review version: `2026-07-20-phase-16`

This review covers the storefront and daily operations queries that must remain stable during frontend integration.

## Catalog

### Product listing

Primary filters:

- `bakery_products.is_active`
- `bakery_products.is_featured`
- `bakery_products.requires_cooling`
- category ownership
- product sort order

Reviewed indexes:

- `bakery_products_listing_index`
- `bakery_products_featured_contract_index`
- `bakery_products_cooling_contract_index`
- `bakery_variants_listing_index`
- `bakery_variants_stock_contract_index`

Catalog resources eager-load category, media and active variants. Reservation-aware stock remains a server-side subquery and is never computed from browser state.

## Customer orders

Customer lists filter by customer and order by `placed_at`. Operations filter by status and payment status.

Reviewed indexes:

- `orders_customer_created_index`
- `orders_customer_status_placed_index`
- `orders_operations_status_index`

Order list and detail eager-load the relations required by their API resources. Internal notes are not loaded into customer responses.

## Payment attempts

Reviewed access paths:

- owned attempt history
- active/pending attempt reuse
- order/status lookup
- expiration processing

Reviewed indexes:

- `payment_attempt_customer_created_index`
- `payment_attempt_order_status_index`
- `payment_attempt_customer_status_index`
- `payment_attempt_expiry_status_index`

Provider verification still locks the attempt, order, variants and reservations transactionally.

## Inventory reservations

Availability uses:

- `inventory_reservation_availability_index`
- unique order/variant reservation key

Physical stock is decremented only after verified payment. Admin cancellation after payment restores consumed stock exactly once through the `restocked` state.

## Content

Published posts use `status`, `published_at` and ID ordering through `bakery_posts_public_contract_index`. Published reviews use `product_reviews_public_index`. Customer moderation lookups use `product_reviews_customer_status_index`.

## Operations

Inquiry inbox filtering uses `inquiries_operations_contract_index`. Notification dispatch already uses `notification_outbox_dispatch_index` and locks selected rows before sending.

## Query safety rules

- customer-owned data is always filtered before public ID lookup
- public routes never use sequential IDs as external identifiers
- API resources must not trigger unbounded relation loading
- list endpoints have enforced pagination limits
- text search is limited in length before `%LIKE%` queries are applied
- provider payloads and private operational data are never selected into storefront resources
- new frontend filters require a contract and index review before addition

## Production verification

Phase 19 repeats this review against the production database engine using representative staging data and `EXPLAIN` for the highest-volume catalog, order and notification queries. An engine-specific index change may be added only when it does not alter the Phase 16 API contract.
