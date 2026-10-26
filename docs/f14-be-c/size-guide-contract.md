# Size guide contract

Size values are not hardcoded on Product.

## Entities

- `SizeGuide`: reusable guide and unit (`cm` or `in`)
- `Size`: store-defined size entity
- `MeasurementDefinition`: admin-defined extensible dimension key/label
- `SizeGuideMeasurement`: numeric value for `(guide, size, measurement definition)`

The schema does not require every garment category to have chest, shoulder, sleeve, waist, hip, inseam, or any other predefined dimension. Administrators define the dimensions that actually apply.

No real garment measurements are seeded or guessed.
