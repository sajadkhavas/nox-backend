# F14-BE-C — LBB Apparel Domain

This phase upgrades the neutral commerce baseline into an apparel-aware domain without freezing the public API or rewriting checkout.

## Scope delivered

- reusable Color and Size entities
- apparel ProductVariant identity (`product + color + size + SKU`)
- server-authoritative stock foundation
- independent Collection and Drop entities
- reusable SizeGuide, MeasurementDefinition, and SizeGuideMeasurement
- material, fabric composition, fit, and care product facts
- evidence states (`missing`, `pending`, `verified`)
- publication states (`draft`, `published`, `archived`)
- color/variant-aware ProductMediaAsset using Spatie Media Library
- Filament administration and variant matrix workflow
- soft-delete/archive-oriented historical integrity

No production catalog values, real garment measurements, real drop dates, or verified demo media are seeded by this phase.
