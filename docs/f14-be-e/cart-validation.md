# Cart Validation

Client sends variant public IDs, quantities and optionally the last-seen unit price. The server resolves published/active Product, Category, Color and Size, current Variant price, current reservations, delivery configuration and totals. Client subtotal, discount, stock, shipping and payment amount are never trusted.

Machine errors include `commerce_invalid_variant`, `commerce_variant_unavailable`, `commerce_product_unavailable`, `commerce_unpublished_product`, `commerce_archived_product`, `commerce_out_of_stock`, `commerce_price_changed` and `commerce_invalid_quantity`.
