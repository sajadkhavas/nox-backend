# F14-BE-E Commerce Operations

Baseline: `integration/backend-wave2-reviewed@30ea1db3524f0d274509f47caf38644f6c013aa5`.

This phase adds production-oriented transactional commerce on top of the accepted BE-D public catalog. New mutable customer commerce APIs live under `/api/v1`. The backend remains authoritative for price, stock, availability, checkout totals, reservation state, payment state, shipment state, returns, exchanges and refund state. Payment/refund execution is fail-closed unless a real configured provider is ready.
