# SEO Handoff

Backend returns source data; frontend SSR owns HTML metadata generation.

Product/category/collection SEO DTOs provide:
- `metaTitle`
- `metaDescription`
- `slug`
- `canonicalPath`
- `publication`
- product `primaryImage`
- product `updatedAt`
- breadcrumbs
- product structured-data source fields

The backend does not emit `<meta>` tags or JSON-LD markup.

Frontend must construct canonical absolute URLs from its canonical origin plus `canonicalPath`. It must not invent price, availability or product publication state.
