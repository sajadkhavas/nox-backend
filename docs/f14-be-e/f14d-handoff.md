# F14D Frontend Handoff

Use only `/api/v1` for new commerce integration. Frontend sends variant public ID + quantity; it may send `expectedUnitPriceToman` to detect stale UI price but must never send authoritative subtotal/total/shipping/payment amount.

Flow: validate cart -> create quote -> display quote truth/expiry -> commit quote with a unique `Idempotency-Key` -> initiate payment only if returned payment state is ready. Handle `commerce_price_changed`, `commerce_out_of_stock`, `commerce_quote_expired` and `commerce_reservation_expired` by refreshing server truth.

Account order endpoints expose immutable purchase snapshots and shipment state. Return/Exchange creation is available only for owned delivered orders. Refund endpoints are read-only customer state surfaces; frontend must never infer refund completion from a Return alone.

Persistent cart / wishlist boundary: the accepted Wave 2 backend baseline does not contain a stable persistent-cart or wishlist domain to extend safely. BE-E therefore does not invent storage or API semantics for either feature. Keep frontend cart state client-side until F14D/F16 chooses the persistence contract, then add backend persistence in the owning phase or a reviewed follow-up without changing this Commerce truth model.
