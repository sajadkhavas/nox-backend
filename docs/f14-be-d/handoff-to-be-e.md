# Handoff to BE-E

BE-D deliberately stops at read-only discovery truth.

BE-E remains owner of:
- final inventory ledger mutations
- atomic inventory validation/locking at checkout
- cart persistence finalization
- wishlist persistence finalization
- checkout execution
- order creation/final order APIs
- payment initiation/verification
- refund/return/exchange execution
- customer address mutations
- notification operations

BE-E may consume `publicId` for Product/Variant references but must resolve and revalidate the server-side record. Public `availability` is never sufficient authorization to purchase.
