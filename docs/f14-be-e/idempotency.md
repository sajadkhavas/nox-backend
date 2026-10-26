# Idempotency

Checkout Commit, payment initiation, Return and Exchange requests require `Idempotency-Key`. Same key + same canonical payload replays the original result; same key + different payload conflicts. Inventory mutation operations carry internal unique idempotency keys. Payment callback fingerprints prevent exact callback replay from repeating a processed transition.
