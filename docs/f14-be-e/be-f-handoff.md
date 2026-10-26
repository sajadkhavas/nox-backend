# BE-F Handoff

BE-F should freeze the whole API/OpenAPI contract across BE-D and BE-E. BE-E intentionally does not perform final whole-Commerce OpenAPI freeze. Freeze `/api/v1` mutable endpoints, machine error codes, order/payment/return/exchange/refund resources, rate limits and idempotency headers. Re-run SQLite full suite plus MySQL commerce/race gates on the BE-F integration head.
