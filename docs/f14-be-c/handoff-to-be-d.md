# Handoff to BE-D and BE-E

## BE-D — public catalog / SSR contract

BE-D should:

- expose Color, Size, apparel variants, Collection/Drop, SizeGuide, measurements, media associations, and evidence-safe product facts
- cut public catalog visibility from legacy `is_active` to the publication contract
- define canonical policy inputs and final SEO/public resource shapes
- preserve server-authoritative price/stock
- decide public serialization for media roles and color/variant grouping
- keep unverified facts out of production responses
- freeze the public API only in BE-D

## BE-E — checkout / inventory / historical snapshots

BE-E should:

- implement the final inventory ledger/mutation model
- keep `available_quantity` server-calculated
- extend order snapshots with color/size labels/codes if the checkout contract needs them
- ensure soft-deleted or renamed apparel entities never make historical order lines unreadable
- preserve SKU/product/variant snapshots already present
- avoid client-authoritative price/stock

F14-BE-C intentionally does not rewrite checkout, payment, auth, cart, wishlist, or order APIs.
