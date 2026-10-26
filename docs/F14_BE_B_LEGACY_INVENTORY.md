# F14-BE-B Legacy Inventory

Generated from branch `phase/f14-be-b-domain-cleanup` before destructive cleanup.

## Identity and domain references

```text
.env.example
.github/workflows/f14-be-b-inventory.yml
.gitignore
DEPLOYMENT.md
README.md
app/Console/Commands/BackendReadiness.php
app/Console/Commands/DispatchNotificationOutbox.php
app/Console/Commands/GenerateSitemap.php
app/Enums/DeliveryMethod.php
app/Filament/Resources/BakeryCategoryResource.php
app/Filament/Resources/BakeryCategoryResource/Pages/CreateBakeryCategory.php
app/Filament/Resources/BakeryCategoryResource/Pages/EditBakeryCategory.php
app/Filament/Resources/BakeryCategoryResource/Pages/ListBakeryCategories.php
app/Filament/Resources/BakeryCityPageResource.php
app/Filament/Resources/BakeryCityPageResource/Pages/ManageBakeryCityPages.php
app/Filament/Resources/BakeryContentPageResource.php
app/Filament/Resources/BakeryContentPageResource/Pages/ManageBakeryContentPages.php
app/Filament/Resources/BakeryFaqResource.php
app/Filament/Resources/BakeryFaqResource/Pages/ManageBakeryFaqs.php
app/Filament/Resources/BakeryGalleryItemResource.php
app/Filament/Resources/BakeryGalleryItemResource/Pages/ManageBakeryGalleryItems.php
app/Filament/Resources/BakeryPostResource.php
app/Filament/Resources/BakeryPostResource/Pages/ManageBakeryPosts.php
app/Filament/Resources/BakeryProductResource.php
app/Filament/Resources/BakeryProductResource/Pages/CreateBakeryProduct.php
app/Filament/Resources/BakeryProductResource/Pages/EditBakeryProduct.php
app/Filament/Resources/BakeryProductResource/Pages/ListBakeryProducts.php
app/Filament/Resources/BlogPostResource.php
app/Filament/Resources/DeliveryZoneResource.php
app/Filament/Resources/ProductResource.php
app/Filament/Resources/SeoScanResource.php
app/Filament/Resources/SitePageResource.php
app/Filament/Resources/WebhookResource.php
app/Http/Controllers/Api/AccountOrderController.php
app/Http/Controllers/Api/CatalogController.php
app/Http/Controllers/Api/CheckoutController.php
app/Http/Controllers/Api/ReviewController.php
app/Http/Controllers/Api/StoreContentController.php
app/Http/Controllers/Api/SystemController.php
app/Http/Controllers/Api/V1/ContactController.php
app/Http/Controllers/Api/V1/RFQController.php
app/Http/Controllers/Api/V1/SettingsController.php
app/Http/Middleware/AttachApiContext.php
app/Http/Middleware/MarkLegacyApi.php
app/Http/Requests/CheckoutRequest.php
app/Http/Resources/BakeryCategoryResource.php
app/Http/Resources/BakeryProductResource.php
app/Http/Resources/BakeryVariantResource.php
app/Http/Resources/OrderItemResource.php
app/Http/Resources/OrderResource.php
app/Http/Resources/ProductResource.php
app/Models/BakeryCategory.php
app/Models/BakeryCityPage.php
app/Models/BakeryContentPage.php
app/Models/BakeryFaq.php
app/Models/BakeryGalleryItem.php
app/Models/BakeryPost.php
app/Models/BakeryProduct.php
app/Models/BakeryProductVariant.php
app/Models/DeliveryZone.php
app/Models/InventoryReservation.php
app/Models/Order.php
app/Models/OrderItem.php
app/Models/Product.php
app/Models/ProductReview.php
app/Providers/AppServiceProvider.php
app/Providers/Filament/AdminPanelProvider.php
app/Providers/TelescopeServiceProvider.php
app/Services/Auth/OtpSender.php
app/Services/Auth/OtpService.php
app/Services/Notifications/NotificationOutboxService.php
app/Services/Notifications/Providers/KavenegarSmsProvider.php
app/Services/Notifications/SmsProviderManager.php
app/Services/Orders/CheckoutService.php
app/Services/Orders/OrderLifecycleService.php
app/Services/Payments/PaymentProviderManager.php
app/Services/Payments/PaymentService.php
app/Services/Payments/Providers/TestingPaymentProvider.php
app/Services/Payments/Providers/ZarinpalPaymentProvider.php
app/Services/Store/DeliveryConfigurationService.php
app/Support/ApiResponse.php
composer.json
config/app.php
config/mail.php
config/lbb.php
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php
database/migrations/2026_07_19_172000_create_checkout_order_tables.php
database/migrations/2026_07_20_000000_create_store_operations_tables.php
database/migrations/2026_07_20_120000_optimize_backend_contract_indexes.php
database/seeders/AdminUserSeeder.php
database/seeders/DatabaseSeeder.php
database/seeders/SettingsSeeder.php
database/seeders/SiteSettingsSeeder.php
database/seeders/LBBStagingSeeder.php
deploy/backend.production.env.example
deploy/bin/deploy-backend.sh
deploy/bin/deploy-production-backend.sh
deploy/bin/preflight-backend-server.sh
deploy/bin/rollback-backend.sh
deploy/bin/smoke-backend-production.sh
deploy/nginx/lbb-api.conf.example
deploy/systemd/lbb-backend-backup.service
deploy/systemd/lbb-backend-backup.timer
deploy/systemd/lbb-backend-queue.service
deploy/systemd/lbb-backend-scheduler.service
deploy/systemd/lbb-backend-scheduler.timer
docs/API_CONTRACT.md
docs/API_ERRORS_AND_PAGINATION.md
docs/BACKEND_AUDIT.md
docs/BACKUP_RESTORE.md
docs/CATALOG_API.md
docs/CUSTOMER_AUTH.md
docs/F14_BE_BASELINE.md
docs/FULL_LAUNCH_ROADMAP.md
docs/LARAVEL_BACKEND_COMPLETE.md
docs/LARAVEL_INTEGRATION.md
docs/OPERATIONS_POLICIES.md
docs/ORDERS_CHECKOUT.md
docs/PAYMENTS.md
docs/PHASE_19_PRODUCTION_DEPLOYMENT.md
docs/QUERY_INDEX_REVIEW.md
docs/SINGLE_SERVER_TOPOLOGY.md
docs/STORE_OPERATIONS.md
docs/openapi.json
routes/api.php
routes/console.php
scripts/audit-backend-foundation.php
scripts/audit-backend-freeze.php
scripts/audit-bakery-catalog.php
scripts/audit-customer-auth.php
scripts/audit-full-launch-roadmap.php
scripts/audit-orders-checkout.php
scripts/audit-payments.php
scripts/audit-phase-18-acceptance.php
scripts/audit-phase19-production-preparation.php
scripts/audit-store-operations.php
scripts/create-backend-release.php
scripts/verify-backend-release.php
src/api/client.ts
src/components/ArticlesMegaMenu.tsx
src/components/Footer.tsx
src/components/MegaMenu.tsx
src/components/MobileNav.tsx
src/components/Navigation.tsx
src/index.css
src/pages/About.tsx
src/pages/Blog.tsx
src/pages/BlogArticle.tsx
src/pages/BrandPage.tsx
src/pages/Brands.tsx
src/pages/CategoryPage.tsx
src/pages/Home.tsx
src/pages/Products.tsx
src/pages/SubcategoryPage.tsx
tests/Feature/BackendContractFreezeTest.php
tests/Feature/BakeryCatalogApiTest.php
tests/Feature/BakeryCatalogFilamentTest.php
tests/Feature/CheckoutOrderTest.php
tests/Feature/CustomerOtpAuthTest.php
tests/Feature/EndToEndAcceptanceTest.php
tests/Feature/ManagedContentFixtureTest.php
tests/Feature/NotificationOutboxTest.php
tests/Feature/OrderFilamentResourceTest.php
tests/Feature/OrderFulfillmentTest.php
tests/Feature/PaymentFilamentResourceTest.php
tests/Feature/PaymentFlowTest.php
tests/Feature/StoreOperationsFilamentTest.php
tests/Feature/StoreOperationsTest.php
tests/Feature/SystemApiTest.php
tests/Unit/BackendFoundationTest.php
vite.config.ts
```

## Models

```text
AbTest.php
AbTestResult.php
AbTestVariant.php
ApiKey.php
BakeryCategory.php
BakeryCityPage.php
BakeryContentPage.php
BakeryFaq.php
BakeryGalleryItem.php
BakeryPost.php
BakeryProduct.php
BakeryProductVariant.php
BlogPost.php
Brand.php
Category.php
Contact.php
Coupon.php
Customer.php
CustomerAddress.php
DeliveryZone.php
EmailTemplate.php
Faq.php
FeatureFlag.php
GoogleIndexingLog.php
Inquiry.php
InventoryReservation.php
IpBlacklist.php
MaintenanceSetting.php
NavigationItem.php
NewsletterSubscriber.php
NotificationOutbox.php
NotificationTemplate.php
Order.php
OrderInternalNote.php
OrderItem.php
OrderStatusHistory.php
OtpChallenge.php
PaymentAttempt.php
PerformanceMetric.php
Product.php
ProductReview.php
Redirect.php
Review.php
RfqItem.php
RfqRequest.php
SchemaMarkup.php
SeoMeta.php
SeoScan.php
Setting.php
ShortUrl.php
SitePage.php
SiteSetting.php
Slider.php
StoreSetting.php
Subcategory.php
Tag.php
Translation.php
User.php
Webhook.php
```

## API controllers

```text
AccountAddressController.php
AccountController.php
AccountOrderController.php
CatalogController.php
CheckoutController.php
DeliveryController.php
InquiryController.php
OtpAuthController.php
PaymentController.php
PerformanceMetricController.php
ReviewController.php
StoreContentController.php
SystemController.php
V1/AuthController.php
V1/BlogController.php
V1/BrandController.php
V1/CategoryController.php
V1/ContactController.php
V1/NavigationController.php
V1/NewsletterController.php
V1/PageController.php
V1/ProductController.php
V1/RFQController.php
V1/SchemaController.php
V1/SearchController.php
V1/SeoController.php
V1/SettingsController.php
V1/SliderController.php
```

## Filament resources

```text
AbTestResource.php
ActivityLogResource.php
ApiKeyResource.php
BakeryCategoryResource.php
BakeryCityPageResource.php
BakeryContentPageResource.php
BakeryFaqResource.php
BakeryGalleryItemResource.php
BakeryPostResource.php
BakeryProductResource.php
BlogPostResource.php
BrandResource.php
CategoryResource.php
ContactResource.php
CouponResource.php
CustomerAddressResource.php
CustomerResource.php
DeliveryZoneResource.php
EmailTemplateResource.php
FaqResource.php
FeatureFlagResource.php
GoogleIndexingLogResource.php
InquiryResource.php
IpBlacklistResource.php
MaintenanceSettingResource.php
NavigationItemResource.php
NewsletterSubscriberResource.php
NotificationOutboxResource.php
NotificationTemplateResource.php
OrderResource.php
PaymentAttemptResource.php
PerformanceMetricResource.php
ProductResource.php
ProductReviewResource.php
RedirectResource.php
ReviewResource.php
RfqResource.php
SchemaMarkupResource.php
SeoMetaResource.php
SeoScanResource.php
SettingResource.php
ShortUrlResource.php
SitePageResource.php
SliderResource.php
StoreSettingResource.php
TagResource.php
TranslationResource.php
UserResource.php
WebhookResource.php
```

## Migrations

```text
0001_01_01_000000_create_users_table.php
0001_01_01_000001_create_cache_and_jobs_tables.php
2014_10_12_200000_add_two_factor_columns_to_users_table.php
2018_08_08_100000_create_telescope_entries_table.php
2019_12_14_000001_create_personal_access_tokens_table.php
2023_06_07_000001_create_pulse_tables.php
2024_01_01_000000_create_passkeys_table.php
2024_01_01_000000_create_personal_access_tokens_table.php
2024_01_01_000001_create_categories_table.php
2024_01_01_000002_create_subcategories_table.php
2024_01_01_000003_create_brands_table.php
2024_01_01_000004_create_products_table.php
2024_01_01_000005_create_blog_posts_table.php
2024_01_01_000006_create_rfq_tables.php
2024_01_01_000007_create_site_settings_table.php
2024_01_01_000008_create_sliders_table.php
2024_01_01_000009_create_contacts_and_newsletter_tables.php
2026_05_30_123323_create_site_pages_table.php
2026_05_30_123349_create_navigation_items_table.php
2026_05_31_192135_create_settings_table.php
2026_06_01_105706_create_permission_tables.php
2026_06_01_114654_create_authentication_log_table.php
2026_06_01_115325_create_activity_log_table.php
2026_06_01_115326_add_event_column_to_activity_log_table.php
2026_06_01_115327_add_batch_uuid_column_to_activity_log_table.php
2026_06_01_125824_create_media_table.php
2026_06_02_192911_add_tenant_aware_column_to_media_table.php
2026_06_04_024112_create_redirects_table.php
2026_06_05_160854_create_seo_meta_table.php
2026_06_07_092116_create_schema_markups_table.php
2026_06_07_093020_create_email_templates_table.php
2026_06_07_093709_create_webhooks_table.php
2026_06_07_094257_create_api_keys_table.php
2026_06_07_100631_create_feature_flags_table.php
2026_06_07_101212_create_translations_table.php
2026_06_07_103705_create_filament_exceptions_table.php
2026_06_07_110506_create_pages_table.php
2026_06_07_110507_fix_slug_unique_constraint_on_pages_table.php
2026_06_07_145702_create_performance_metrics_table.php
2026_06_07_145709_create_ab_tests_table.php
2026_06_07_145719_create_ab_test_variants_table.php
2026_06_07_145725_create_ab_test_results_table.php
2026_06_07_172416_create_coupons_table.php
2026_06_07_172418_create_faqs_table.php
2026_06_07_172419_create_reviews_table.php
2026_06_07_172420_create_tags_table.php
2026_06_07_172423_create_short_urls_table.php
2026_06_07_174346_create_seo_scans_table.php
2026_06_07_174349_create_google_indexing_logs_table.php
2026_06_07_175936_create_ip_blacklists_table.php
2026_06_07_175939_create_maintenance_mode_table.php
2026_06_11_113550_fix_seo_meta_description_columns.php
2026_06_11_114324_add_indexes_to_seo_scans.php
2026_06_11_130729_add_profile_fields_to_users_table.php
2026_06_11_132802_add_ip_to_performance_metrics_table.php
2026_06_11_152702_create_jobs_table.php
2026_06_11_154204_create_failed_jobs_table.php
2026_07_19_120000_create_bakery_catalog_tables.php
2026_07_19_163000_create_customer_auth_tables.php
2026_07_19_172000_create_checkout_order_tables.php
2026_07_19_190000_create_payment_attempts_table.php
2026_07_20_000000_create_store_operations_tables.php
2026_07_20_001000_add_public_id_to_order_items.php
2026_07_20_120000_optimize_backend_contract_indexes.php
```

## Food-specific field references

```text
.github/workflows/f14-be-b-inventory.yml:38:            git grep -Il -E 'LBB|WINIMI|lbb|Bakery|BAKERY|bakery|ToolMaster|TOOLMASTER|toolmaster|requires_cooling|ingredients|allergens|chilled' -- ':!docs/reference/**' ':!docs/F14_BE_B_LEGACY_INVENTORY.md' | sort || true
.github/workflows/f14-be-b-inventory.yml:68:            git grep -n -E 'requires_cooling|ingredients|allergens|shelf_life|storage_instructions|weight_grams|preparation_time_days|chilled' -- ':!docs/reference/**' || true
app/Enums/DeliveryMethod.php:8:    case Chilled = 'chilled';
app/Filament/Resources/BakeryProductResource.php:78:                                    Forms\Components\Toggle::make('requires_cooling')
app/Filament/Resources/BakeryProductResource.php:81:                                    Forms\Components\TextInput::make('preparation_time_days')
app/Filament/Resources/BakeryProductResource.php:111:                                    Forms\Components\TextInput::make('weight_grams')
app/Filament/Resources/BakeryProductResource.php:158:                            Forms\Components\TagsInput::make('ingredients')
app/Filament/Resources/BakeryProductResource.php:161:                            Forms\Components\TagsInput::make('allergens')
app/Filament/Resources/BakeryProductResource.php:164:                            Forms\Components\TextInput::make('shelf_life')
app/Filament/Resources/BakeryProductResource.php:167:                            Forms\Components\Textarea::make('storage_instructions')
app/Filament/Resources/BakeryProductResource.php:244:                Tables\Columns\IconColumn::make('requires_cooling')
app/Filament/Resources/BakeryProductResource.php:266:                Tables\Filters\TernaryFilter::make('requires_cooling')->label('نیازمند سرما'),
app/Filament/Resources/DeliveryZoneResource.php:44:                    Forms\Components\Toggle::make('chilled_enabled')->label('ارسال سرد'),
app/Filament/Resources/DeliveryZoneResource.php:45:                    Forms\Components\TextInput::make('chilled_fee_toman')->label('هزینه سرد')->numeric()->default(0)->suffix(' تومان'),
app/Filament/Resources/DeliveryZoneResource.php:69:                Tables\Columns\IconColumn::make('chilled_enabled')->label('سرد')->boolean(),
app/Filament/Resources/OrderResource.php:87:                    Forms\Components\TextInput::make('preparation_time_days')->label('حداقل روز آماده‌سازی')->disabled(),
app/Http/Controllers/Api/CatalogController.php:68:            $query->where('requires_cooling', $requiresCooling);
app/Http/Resources/BakeryProductResource.php:43:            'weightGrams' => $defaultVariant?->weight_grams,
app/Http/Resources/BakeryProductResource.php:44:            'weight' => $defaultVariant?->weight_grams
app/Http/Resources/BakeryProductResource.php:45:                ? number_format($defaultVariant->weight_grams).' گرم'
app/Http/Resources/BakeryProductResource.php:49:            'requiresCooling' => (bool) $this->requires_cooling,
app/Http/Resources/BakeryProductResource.php:50:            'shippingScope' => $this->requires_cooling ? 'tehran-karaj' : 'nationwide',
app/Http/Resources/BakeryProductResource.php:51:            'shippingNote' => $this->requires_cooling
app/Http/Resources/BakeryProductResource.php:54:            'ingredients' => $contentVerified ? ($this->ingredients ?? []) : [],
app/Http/Resources/BakeryProductResource.php:55:            'allergens' => $contentVerified ? ($this->allergens ?? []) : [],
app/Http/Resources/BakeryProductResource.php:56:            'shelfLife' => $contentVerified ? $this->shelf_life : null,
app/Http/Resources/BakeryProductResource.php:57:            'storageTips' => $contentVerified ? $this->storage_instructions : null,
app/Http/Resources/BakeryProductResource.php:58:            'preparationTimeDays' => $this->preparation_time_days,
app/Http/Resources/BakeryProductResource.php:60:                $this->requires_cooling ? 'نیازمند نگهداری سرد' : null,
app/Http/Resources/BakeryVariantResource.php:16:            'weightGrams' => $this->weight_grams,
app/Http/Resources/BakeryVariantResource.php:17:            'weight' => $this->weight_grams
app/Http/Resources/BakeryVariantResource.php:18:                ? number_format($this->weight_grams).' گرم'
app/Http/Resources/OrderItemResource.php:20:            'weightGrams' => $this->weight_grams,
app/Http/Resources/OrderItemResource.php:21:            'requiresCooling' => $this->requires_cooling,
app/Http/Resources/OrderResource.php:23:                'requiresCooling' => $this->requires_cooling,
app/Http/Resources/OrderResource.php:40:            'preparationTimeDays' => $this->preparation_time_days,
app/Http/Resources/OrderResource.php:42:                'minDays' => $this->preparation_time_days,
app/Http/Resources/OrderResource.php:43:                'maxDays' => max($this->preparation_time_days, $this->preparation_max_days),
app/Models/BakeryProduct.php:31:        'ingredients',
app/Models/BakeryProduct.php:32:        'allergens',
app/Models/BakeryProduct.php:33:        'shelf_life',
app/Models/BakeryProduct.php:34:        'storage_instructions',
app/Models/BakeryProduct.php:35:        'preparation_time_days',
app/Models/BakeryProduct.php:36:        'requires_cooling',
app/Models/BakeryProduct.php:47:        'ingredients' => 'array',
app/Models/BakeryProduct.php:48:        'allergens' => 'array',
app/Models/BakeryProduct.php:49:        'preparation_time_days' => 'integer',
app/Models/BakeryProduct.php:50:        'requires_cooling' => 'boolean',
app/Models/BakeryProduct.php:92:                'requires_cooling',
app/Models/BakeryProductVariant.php:18:        'weight_grams',
app/Models/BakeryProductVariant.php:29:        'weight_grams' => 'integer',
app/Models/DeliveryZone.php:17:        'chilled_enabled',
app/Models/DeliveryZone.php:20:        'chilled_fee_toman',
app/Models/DeliveryZone.php:43:            'chilled_enabled' => 'boolean',
app/Models/DeliveryZone.php:46:            'chilled_fee_toman' => 'integer',
app/Models/Order.php:25:        'requires_cooling',
app/Models/Order.php:32:        'preparation_time_days',
app/Models/Order.php:67:            'requires_cooling' => 'boolean',
app/Models/Order.php:74:            'preparation_time_days' => 'integer',
app/Models/OrderItem.php:21:        'weight_grams',
app/Models/OrderItem.php:22:        'requires_cooling',
app/Models/OrderItem.php:38:            'weight_grams' => 'integer',
app/Models/OrderItem.php:39:            'requires_cooling' => 'boolean',
app/Services/Orders/CheckoutService.php:108:            fn (BakeryProductVariant $variant): bool => (bool) $variant->product?->requires_cooling,
app/Services/Orders/CheckoutService.php:157:                (int) ($product->preparation_time_days ?? 0),
app/Services/Orders/CheckoutService.php:169:                'weight_grams' => $variant->weight_grams,
app/Services/Orders/CheckoutService.php:170:                'requires_cooling' => (bool) $product->requires_cooling,
app/Services/Orders/CheckoutService.php:199:            'requires_cooling' => $requiresCooling,
app/Services/Orders/CheckoutService.php:206:            'preparation_time_days' => $preparationMinDays,
config/lbb.php:57:            'chilled' => [
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:37:            $table->json('ingredients')->nullable();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:38:            $table->json('allergens')->nullable();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:39:            $table->string('shelf_life', 220)->nullable();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:40:            $table->text('storage_instructions')->nullable();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:41:            $table->unsignedSmallInteger('preparation_time_days')->nullable();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:42:            $table->boolean('requires_cooling')->default(false)->index();
database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php:64:            $table->unsignedInteger('weight_grams')->nullable();
database/migrations/2026_07_19_172000_create_checkout_order_tables.php:23:            $table->boolean('requires_cooling')->default(false)->index();
database/migrations/2026_07_19_172000_create_checkout_order_tables.php:30:            $table->unsignedSmallInteger('preparation_time_days')->default(0);
database/migrations/2026_07_19_172000_create_checkout_order_tables.php:67:            $table->unsignedInteger('weight_grams')->nullable();
database/migrations/2026_07_19_172000_create_checkout_order_tables.php:68:            $table->boolean('requires_cooling')->default(false);
database/migrations/2026_07_20_000000_create_store_operations_tables.php:37:            $table->boolean('chilled_enabled')->default(false);
database/migrations/2026_07_20_000000_create_store_operations_tables.php:40:            $table->unsignedBigInteger('chilled_fee_toman')->default(0);
database/migrations/2026_07_20_000000_create_store_operations_tables.php:215:            $table->unsignedSmallInteger('preparation_max_days')->default(0)->after('preparation_time_days');
database/migrations/2026_07_20_120000_optimize_backend_contract_indexes.php:17:                ['requires_cooling', 'is_active'],
database/seeders/LBBStagingSeeder.php:49:                    'slug' => 'staging-chilled-cake',
database/seeders/LBBStagingSeeder.php:84:                        'ingredients' => json_encode(['داده تست'], JSON_UNESCAPED_UNICODE),
database/seeders/LBBStagingSeeder.php:85:                        'allergens' => json_encode([], JSON_UNESCAPED_UNICODE),
database/seeders/LBBStagingSeeder.php:86:                        'preparation_time_days' => 1,
database/seeders/LBBStagingSeeder.php:87:                        'requires_cooling' => $product['cooling'],
database/seeders/LBBStagingSeeder.php:119:                ['name' => 'تهران تست', 'province' => 'تهران', 'city' => 'تهران', 'standard' => true, 'chilled' => true, 'pickup' => true, 'priority' => 10],
database/seeders/LBBStagingSeeder.php:120:                ['name' => 'کرج تست', 'province' => 'البرز', 'city' => 'کرج', 'standard' => true, 'chilled' => true, 'pickup' => false, 'priority' => 20],
database/seeders/LBBStagingSeeder.php:121:                ['name' => 'اندیشه تست', 'province' => 'تهران', 'city' => 'اندیشه', 'standard' => true, 'chilled' => true, 'pickup' => false, 'priority' => 20],
database/seeders/LBBStagingSeeder.php:122:                ['name' => 'ارسال خشک سراسری تست', 'province' => null, 'city' => null, 'standard' => true, 'chilled' => false, 'pickup' => false, 'priority' => 900],
database/seeders/LBBStagingSeeder.php:133:                        'chilled_enabled' => $zone['chilled'],
database/seeders/LBBStagingSeeder.php:136:                        'chilled_fee_toman' => 85000,
docs/CATALOG_API.md:70:- Ingredients, allergens, shelf life and storage instructions are returned only when `content_verified` is true.
docs/FULL_LAUNCH_ROADMAP.md:46:- Tehran, Karaj and Andisheh chilled-delivery rules
docs/FULL_LAUNCH_ROADMAP.md:96:- chilled rejection outside allowed zones
docs/FULL_LAUNCH_ROADMAP.md:97:- chilled checkout in Tehran
docs/ORDERS_CHECKOUT.md:119:A cart containing any cooling-required product cannot use `standard` delivery. It must use `chilled` delivery or `pickup`.
docs/QUERY_INDEX_REVIEW.md:15:- `bakery_products.requires_cooling`
docs/STORE_OPERATIONS.md:27:- standard, chilled and pickup availability
docs/openapi.json:106:      "CheckoutInput": {"type": "object", "required": ["deliveryMethod", "items"], "properties": {"addressId": {"type": ["string", "null"], "minLength": 26, "maxLength": 26}, "customer": {"type": ["object", "null"], "additionalProperties": true}, "deliveryMethod": {"type": "string", "enum": ["standard", "chilled", "pickup"]}, "items": {"type": "array", "minItems": 1, "maxItems": 50, "items": {"type": "object", "required": ["variantId", "quantity"], "properties": {"variantId": {"type": "string", "minLength": 26, "maxLength": 26}, "quantity": {"type": "integer", "minimum": 1, "maximum": 20}}}}}}
scripts/audit-backend-freeze.php:154:$requireText('database/seeders/LBBStagingSeeder.php', 'staging-chilled-cake', 'chilled staging product');
scripts/audit-orders-checkout.php:93:    'test_cooling_products_require_chilled_delivery_or_pickup',
scripts/audit-phase-18-acceptance.php:50:$require('acceptance', 'phase18-chilled-rejected', 'chilled-zone rejection');
Binary file src/assets/slider-1.png matches
Binary file src/assets/slider-2.png matches
Binary file src/assets/slider-3.png matches
tests/Feature/BackendContractFreezeTest.php:80:                'requires_cooling' => false,
tests/Feature/BackendContractFreezeTest.php:134:            'requires_cooling' => false,
tests/Feature/BackendContractFreezeTest.php:141:            'preparation_time_days' => 1,
tests/Feature/BakeryCatalogApiTest.php:68:            'ingredients' => ['آرد', 'گردو'],
tests/Feature/BakeryCatalogApiTest.php:69:            'allergens' => ['گلوتن', 'گردو'],
tests/Feature/BakeryCatalogApiTest.php:71:            'requires_cooling' => false,
tests/Feature/BakeryCatalogApiTest.php:102:            ->assertJsonPath('data.0.ingredients', [])
tests/Feature/BakeryCatalogApiTest.php:103:            ->assertJsonPath('data.0.allergens', [])
tests/Feature/BakeryCatalogApiTest.php:121:            'ingredients' => ['پنیر خامه‌ای', 'بیسکویت'],
tests/Feature/BakeryCatalogApiTest.php:122:            'allergens' => ['لبنیات', 'گلوتن'],
tests/Feature/BakeryCatalogApiTest.php:123:            'shelf_life' => 'طبق برچسب بسته‌بندی',
tests/Feature/BakeryCatalogApiTest.php:124:            'storage_instructions' => 'در یخچال نگهداری شود.',
tests/Feature/BakeryCatalogApiTest.php:126:            'requires_cooling' => true,
tests/Feature/BakeryCatalogApiTest.php:146:            ->assertJsonPath('data.ingredients.0', 'پنیر خامه‌ای')
tests/Feature/BakeryCatalogApiTest.php:147:            ->assertJsonPath('data.allergens.0', 'لبنیات')
tests/Feature/CheckoutOrderTest.php:37:            'lbb.checkout.delivery_methods.chilled' => ['enabled' => true, 'fee_toman' => 90_000],
tests/Feature/CheckoutOrderTest.php:60:            'preparation_time_days' => 2,
tests/Feature/CheckoutOrderTest.php:61:            'requires_cooling' => false,
tests/Feature/CheckoutOrderTest.php:151:    public function test_cooling_products_require_chilled_delivery_or_pickup(): void
tests/Feature/CheckoutOrderTest.php:153:        $this->variant->product()->update(['requires_cooling' => true]);
tests/Feature/CheckoutOrderTest.php:159:        $this->checkout('checkout-key-000006', 1, 'chilled')
tests/Feature/CheckoutOrderTest.php:161:            ->assertJsonPath('data.order.delivery.method', 'chilled')
tests/Feature/EndToEndAcceptanceTest.php:54:            ->assertJsonFragment(['slug' => 'staging-chilled-cake']);
tests/Feature/EndToEndAcceptanceTest.php:107:        $chilledVariant = BakeryProductVariant::query()
tests/Feature/EndToEndAcceptanceTest.php:163:            'deliveryMethod' => 'chilled',
tests/Feature/EndToEndAcceptanceTest.php:165:                'variantId' => $chilledVariant->public_id,
tests/Feature/EndToEndAcceptanceTest.php:169:            'Idempotency-Key' => 'phase18-chilled-rejected-0001',
tests/Feature/EndToEndAcceptanceTest.php:174:            'deliveryMethod' => 'chilled',
tests/Feature/EndToEndAcceptanceTest.php:176:                'variantId' => $chilledVariant->public_id,
tests/Feature/EndToEndAcceptanceTest.php:180:            'Idempotency-Key' => 'phase18-chilled-tehran-0001',
tests/Feature/EndToEndAcceptanceTest.php:182:            ->assertJsonPath('data.order.delivery.method', 'chilled')
tests/Feature/NotificationOutboxTest.php:73:            'requires_cooling' => false,
tests/Feature/NotificationOutboxTest.php:80:            'preparation_time_days' => 1,
tests/Feature/OrderFilamentResourceTest.php:46:            'requires_cooling' => false,
tests/Feature/OrderFilamentResourceTest.php:53:            'preparation_time_days' => 1,
tests/Feature/OrderFulfillmentTest.php:68:            'preparation_time_days' => 1,
tests/Feature/OrderFulfillmentTest.php:69:            'requires_cooling' => false,
tests/Feature/OrderFulfillmentTest.php:199:            'requires_cooling' => false,
tests/Feature/OrderFulfillmentTest.php:206:            'preparation_time_days' => 1,
tests/Feature/OrderFulfillmentTest.php:226:            'requires_cooling' => false,
tests/Feature/PaymentFilamentResourceTest.php:48:            'requires_cooling' => false,
tests/Feature/PaymentFilamentResourceTest.php:55:            'preparation_time_days' => 1,
tests/Feature/PaymentFlowTest.php:39:            'lbb.checkout.delivery_methods.chilled' => ['enabled' => true, 'fee_toman' => 90_000],
tests/Feature/PaymentFlowTest.php:67:            'preparation_time_days' => 2,
tests/Feature/PaymentFlowTest.php:68:            'requires_cooling' => false,
tests/Feature/StoreOperationsFilamentTest.php:73:            'requires_cooling' => false,
tests/Feature/StoreOperationsFilamentTest.php:80:            'preparation_time_days' => 1,
tests/Feature/StoreOperationsTest.php:48:            'lbb.checkout.delivery_methods.chilled' => ['enabled' => false, 'fee_toman' => 0],
tests/Feature/StoreOperationsTest.php:70:            'preparation_time_days' => 2,
tests/Feature/StoreOperationsTest.php:71:            'requires_cooling' => false,
tests/Feature/StoreOperationsTest.php:157:            'preparation_time_days' => 2,
tests/Feature/StoreOperationsTest.php:324:            'requires_cooling' => false,
tests/Feature/StoreOperationsTest.php:331:            'preparation_time_days' => 2,
tests/Feature/StoreOperationsTest.php:349:            'requires_cooling' => false,
```

## Legacy API references

```text
.env.example:34:LEGACY_TOOLMASTER_API_ENABLED=false
.github/workflows/f14-be-b-inventory.yml:74:            git grep -n -E 'api\.legacy|LEGACY_TOOLMASTER|Legacy ToolMaster|Controllers\\Api\\V1' -- ':!docs/reference/**' || true
app/Http/Controllers/Api/V1/AuthController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/BlogController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/BrandController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/CategoryController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/ContactController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/NavigationController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/NewsletterController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/PageController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/ProductController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/RFQController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/SchemaController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/SearchController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/SeoController.php:2:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/SettingsController.php:3:namespace App\Http\Controllers\Api\V1;
app/Http/Controllers/Api/V1/SliderController.php:3:namespace App\Http\Controllers\Api\V1;
bootstrap/app.php:33:            'api.legacy' => MarkLegacyApi::class,
config/lbb.php:153:        'enabled' => $boolean('LEGACY_TOOLMASTER_API_ENABLED', $legacyDefault),
deploy/backend.production.env.example:37:LEGACY_TOOLMASTER_API_ENABLED=false
deploy/bin/preflight-backend-server.sh:62:    require_env LEGACY_TOOLMASTER_API_ENABLED false
docs/LARAVEL_BACKEND_COMPLETE.md:707:namespace App\Http\Controllers\Api\V1;
docs/LARAVEL_BACKEND_COMPLETE.md:794:namespace App\Http\Controllers\Api\V1;
docs/LARAVEL_BACKEND_COMPLETE.md:889:namespace App\Http\Controllers\Api\V1;
docs/LARAVEL_BACKEND_COMPLETE.md:952:namespace App\Http\Controllers\Api\V1;
docs/LARAVEL_BACKEND_COMPLETE.md:981:use App\Http\Controllers\Api\V1\{
routes/api.php:16:use App\Http\Controllers\Api\V1\AuthController;
routes/api.php:17:use App\Http\Controllers\Api\V1\BlogController;
routes/api.php:18:use App\Http\Controllers\Api\V1\BrandController;
routes/api.php:19:use App\Http\Controllers\Api\V1\CategoryController;
routes/api.php:20:use App\Http\Controllers\Api\V1\ContactController;
routes/api.php:21:use App\Http\Controllers\Api\V1\NavigationController;
routes/api.php:22:use App\Http\Controllers\Api\V1\NewsletterController;
routes/api.php:23:use App\Http\Controllers\Api\V1\PageController;
routes/api.php:24:use App\Http\Controllers\Api\V1\ProductController;
routes/api.php:25:use App\Http\Controllers\Api\V1\RFQController;
routes/api.php:26:use App\Http\Controllers\Api\V1\SchemaController;
routes/api.php:27:use App\Http\Controllers\Api\V1\SearchController;
routes/api.php:28:use App\Http\Controllers\Api\V1\SeoController;
routes/api.php:29:use App\Http\Controllers\Api\V1\SettingsController;
routes/api.php:30:use App\Http\Controllers\Api\V1\SliderController;
routes/api.php:104:| Legacy ToolMaster API
routes/api.php:112:Route::prefix('v1')->middleware('api.legacy')->group(function () {
scripts/audit-backend-foundation.php:49:$requireText('routes/api.php', "middleware('api.legacy')", 'legacy route boundary');
```
