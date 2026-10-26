# ──── Laravel Backend Integration Guide ────
# 
# This document explains how to connect this React frontend
# to your Laravel + Filament backend.
#
# ══════════════════════════════════════════════
# 1. ENVIRONMENT VARIABLES
# ══════════════════════════════════════════════
#
# Add to your .env file:
#   VITE_API_BASE_URL=https://api.toolmaster.com/api/v1
#   VITE_APP_URL=https://toolmaster.com
#
# The frontend currently uses static data from src/data/*.
# Once VITE_API_BASE_URL is set, React Query hooks will
# automatically fetch from your Laravel API instead.
#
# ══════════════════════════════════════════════
# 2. LARAVEL API ROUTES (routes/api.php)
# ══════════════════════════════════════════════
#
# Route::prefix('v1')->group(function () {
#     // Public
#     Route::apiResource('products', ProductController::class)->only(['index', 'show']);
#     Route::get('products/featured', [ProductController::class, 'featured']);
#     Route::get('products/{product}/similar', [ProductController::class, 'similar']);
#     
#     Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
#     Route::get('categories/{category}/products', [CategoryController::class, 'products']);
#     Route::get('categories/{category}/subcategories', [CategoryController::class, 'subcategories']);
#     
#     Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
#     Route::get('brands/{brand}/products', [BrandController::class, 'products']);
#     
#     Route::get('blog', [BlogController::class, 'index']);
#     Route::get('blog/latest', [BlogController::class, 'latest']);
#     Route::get('blog/{post:slug}', [BlogController::class, 'show']);
#     
#     Route::get('search', SearchController::class);
#     Route::get('settings', SettingsController::class);
#     Route::get('sliders', SliderController::class);
#     
#     Route::post('rfq', [RFQController::class, 'store']);
#     Route::get('rfq/{reference}', [RFQController::class, 'show']);
#     Route::post('contact', [ContactController::class, 'store']);
#     Route::post('newsletter', [NewsletterController::class, 'store']);
#     
#     // Auth (Sanctum)
#     Route::prefix('auth')->group(function () {
#         Route::post('login', [AuthController::class, 'login']);
#         Route::post('register', [AuthController::class, 'register']);
#         Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
#         Route::post('reset-password', [AuthController::class, 'resetPassword']);
#         
#         Route::middleware('auth:sanctum')->group(function () {
#             Route::get('user', [AuthController::class, 'user']);
#             Route::post('logout', [AuthController::class, 'logout']);
#         });
#     });
# });
#
# ══════════════════════════════════════════════
# 3. LARAVEL CORS CONFIG (config/cors.php)
# ══════════════════════════════════════════════
#
# 'paths' => ['api/*', 'sanctum/csrf-cookie'],
# 'allowed_origins' => [env('FRONTEND_URL', 'https://toolmaster.com')],
# 'supports_credentials' => true,
#
# ══════════════════════════════════════════════
# 4. DATABASE MIGRATION STRUCTURE
# ══════════════════════════════════════════════
#
# Tables needed:
#   - products (id, name, slug, model, type, category_id, brand_id, ...)
#   - categories (id, name, slug, parent_id, image, sort_order, ...)
#   - brands (id, name, slug, logo, description, ...)
#   - blog_posts (id, title, slug, content, category, author_id, ...)
#   - rfq_requests (id, reference_number, name, email, status, ...)
#   - rfq_items (id, rfq_request_id, product_id, quantity, notes)
#   - contacts (id, name, email, subject, message, ...)
#   - site_settings (id, key, value, group)
#   - sliders (id, title, subtitle, image, link, sort_order, is_active)
#   - seo_metadata (id, seoable_type, seoable_id, meta_title, meta_description, ...)
#   - newsletter_subscribers (id, email, subscribed_at)
#
# ══════════════════════════════════════════════
# 5. FILAMENT ADMIN PANELS
# ══════════════════════════════════════════════
#
# Resources to create in Filament:
#   - ProductResource (with tabs: General, Specs, SEO, Gallery)
#   - CategoryResource (with tree/nested set for parent-child)
#   - BrandResource
#   - BlogPostResource (with rich text editor)
#   - RFQResource (read-only + status update)
#   - ContactResource (read-only)
#   - SliderResource
#   - SiteSettingsPage
#
# ══════════════════════════════════════════════
# 6. FRONTEND API INTEGRATION CHECKLIST
# ══════════════════════════════════════════════
#
# The following files are ready for API integration:
#
# src/api/
#   ├── client.ts     → HTTP client with auth token management
#   ├── types.ts      → TypeScript interfaces matching Laravel API Resources
#   ├── services.ts   → Service functions mapped to API endpoints
#   ├── hooks.ts      → React Query hooks for data fetching
#   └── index.ts      → Barrel export
#
# To switch from static to API data in any page:
#   1. Import the hook: import { useProducts } from '@/api';
#   2. Replace static data: const { data, isLoading } = useProducts({ category: 'gas-generators' });
#   3. Add loading/error states
#
# ══════════════════════════════════════════════
# 7. API RESPONSE FORMAT
# ══════════════════════════════════════════════
#
# Single resource:
#   { "data": { ... }, "message": "..." }
#
# Paginated collection:
#   {
#     "data": [...],
#     "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 },
#     "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
#   }
#
# Error:
#   { "message": "Validation error", "errors": { "email": ["required"] } }
