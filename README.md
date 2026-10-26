# LBB Backend

Independent Laravel 12 + Filament backend for the LBB apparel commerce platform.

## Current state

- F14-BE-A imported the audited backend baseline without changing Cooci.
- F14-BE-B is removing inherited legacy and food-specific behavior.
- Checkout, payment, SMS and production launch remain disabled until the LBB API contract is frozen.

## Repository boundary

All LBB backend development happens here. The Cooci repositories are read-only references and are not modified by this migration.
