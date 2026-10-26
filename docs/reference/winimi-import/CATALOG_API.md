# Winimi Bakery Catalog API

Phase 11 status: `implemented`

Base path: `/api/catalog`

All responses use the standard Winimi envelope and include `X-Request-ID` and `X-API-Version` headers.

## Domain boundary

The bakery catalog uses independent tables:

- `bakery_categories`
- `bakery_products`
- `bakery_product_variants`

The inherited ToolMaster `categories`, `products`, `brands`, `subcategories` and RFQ tables are not reused as bakery commerce truth.

## `GET /api/catalog/categories`

Returns active categories ordered by `sort_order` and name. `productCount` contains only products that are active, belong to an active category and have at least one active Variant.

## `GET /api/catalog/products`

Supported query parameters:

| Parameter | Values |
|---|---|
| `category` | category slug |
| `search` | maximum 100 characters |
| `featured` | `1`, `0`, `true`, `false` |
| `requiresCooling` | boolean |
| `inStock` | boolean |
| `sort` | `featured`, `newest`, `name`, `price-asc`, `price-desc` |
| `page` | integer >= 1 |
| `perPage` | 1 to 48 |

The endpoint never accepts price or availability from the client. Price and stock are derived from active Variants.

Example product fields:

```json
{
  "id": "01K...",
  "slug": "walnut-chocolate-cookie",
  "name": "کوکی شکلاتی گردویی",
  "productCode": "WIN-COOKIE-002",
  "category": "کوکی‌ها",
  "categorySlug": "cookies",
  "priceToman": 120000,
  "regularPriceToman": 150000,
  "salePriceToman": 120000,
  "stock": 5,
  "available": true,
  "requiresCooling": false,
  "contentVerified": false,
  "mediaVerified": false,
  "inventoryVerified": true,
  "variants": []
}
```

## `GET /api/catalog/products/{slug}`

Returns one active product with active Variants and media. Inactive products, inactive categories and products without active Variants return HTTP 404.

## Verification rules

- Inventory is authoritative because it is read from the backend Variant records.
- Ingredients, allergens, shelf life and storage instructions are returned only when `content_verified` is true.
- Media URLs are returned with the `verified` flag derived from `media_verified`.
- A product can be active while out of stock; `available` is then false.
- A product cannot appear publicly without at least one active Variant.

## Price rules

- `regular_price_toman` must be greater than zero.
- `sale_price_toman` is optional.
- A sale price must be greater than zero and lower than the regular price.
- Product listing price is the lowest current price among active Variants.
- The default Variant controls the top-level regular and sale price fields.

## Filament administration

The admin panel includes two new resources under `فروشگاه وینیمی`:

- `دسته‌های بیکری`
- `محصولات بیکری`

Product Variants are edited inside the product form. The legacy industrial resources remain temporarily available for data migration but are not the source of the public bakery catalog API.
