# راهنمای کامل بکند Laravel — ToolMaster
# خط به خط، جزء به جزء، منطبق با ساختار React فرانت

---

## ۱. نصب و راه‌اندازی اولیه

```bash
composer create-project laravel/laravel toolmaster-backend
cd toolmaster-backend

# پکیج‌های اصلی
composer require filament/filament:"^3.2" -W
composer require spatie/laravel-medialibrary:"^11.0"
composer require spatie/laravel-sluggable
composer require spatie/laravel-sitemap
composer require laravel/sanctum
composer require laravel/scout
composer require meilisearch/meilisearch-php
composer require league/flysystem-aws-s3-v3
composer require barryvdh/laravel-dompurify   # یا از purify خودمان استفاده می‌کنیم

# پکیج‌های dev
composer require --dev barryvdh/laravel-debugbar
composer require --dev laravel/telescope
php artisan telescope:install

# نصب Filament
php artisan filament:install --panels

# نصب Shield (مدیریت نقش‌ها در Filament)
composer require bezhansalleh/filament-shield
php artisan shield:install --fresh
```

---

## ۲. تنظیمات .env

```env
APP_NAME="ToolMaster"
APP_ENV=production
APP_URL=https://api.toolmaster.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toolmaster
DB_USERNAME=root
DB_PASSWORD=

# Frontend URL (CORS)
FRONTEND_URL=https://toolmaster.com

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Media Storage (اختیاری: S3)
FILESYSTEM_DISK=public

# Scout / Search
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=info@toolmaster.com
MAIL_FROM_NAME="ToolMaster"
```

---

## ۳. مهاجرت‌های پایگاه داده (Migrations)

### ۳.۱ جدول دسته‌بندی‌ها

```php
// database/migrations/2024_01_01_000001_create_categories_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // نام فارسی
            $table->string('name_en')->nullable();         // نام انگلیسی
            $table->string('slug')->unique();              // gas-generators
            $table->text('description')->nullable();       // توضیح کوتاه
            $table->text('long_description')->nullable();  // محتوای سئو (HTML - 600+ کلمه)
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->text('faq_schema')->nullable();        // JSON FAQPage schema
            $table->text('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('icon')->nullable();            // نام آیکون Lucide
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
```

### ۳.۲ جدول زیرمجموعه‌ها

```php
// database/migrations/2024_01_01_000002_create_subcategories_table.php
Schema::create('subcategories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();                  // hydrogen-gen
    $table->string('full_name_en')->nullable();        // Hydrogen Generator
    $table->text('description')->nullable();
    $table->text('long_description')->nullable();      // 800+ کلمه HTML سئو
    $table->string('meta_title', 60)->nullable();
    $table->string('meta_description', 160)->nullable();
    $table->text('meta_keywords')->nullable();
    $table->text('faq_schema')->nullable();
    $table->string('image')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### ۳.۳ جدول برندها

```php
// database/migrations/2024_01_01_000003_create_brands_table.php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->string('name');                            // Siemens
    $table->string('slug')->unique();                  // siemens
    $table->string('country', 10)->nullable();         // DE
    $table->text('description')->nullable();           // توضیح کوتاه
    $table->text('long_description')->nullable();      // 800+ کلمه HTML
    $table->string('logo')->nullable();
    $table->string('website')->nullable();
    $table->string('meta_title', 60)->nullable();
    $table->string('meta_description', 160)->nullable();
    $table->text('meta_keywords')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### ۳.۴ جدول محصولات (قلب سیستم)

```php
// database/migrations/2024_01_01_000004_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');                             // نام فارسی
    $table->string('model')->nullable();                // مدل دستگاه
    $table->string('slug')->unique();
    $table->foreignId('category_id')->constrained();
    $table->foreignId('subcategory_id')->nullable()->constrained();
    $table->foreignId('brand_id')->nullable()->constrained();
    $table->string('country', 10)->nullable();          // DE, US, UK

    // محتوای اصلی
    $table->text('description');                        // توضیح کوتاه
    $table->longText('long_description')->nullable();   // محتوای سئو 500+ کلمه
    $table->string('excerpt', 300)->nullable();

    // مشخصات فنی (JSON)
    $table->json('specs')->nullable();
    // مثال: {"range":"DN10-2000","accuracy":"±0.2%","protocol":"HART/Modbus","pressure":"0-100 bar"}

    // فیلترهای محصول
    $table->json('usage')->nullable();                  // ["research","industrial"]
    $table->json('applications')->nullable();           // ["GC-MS","LCMS","آنالیز عنصری"]
    $table->string('price_range')->nullable();          // budget|mid|premium

    // وضعیت موجودی
    $table->boolean('in_stock')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->enum('status', ['published','draft','archived'])->default('draft');

    // سئو
    $table->string('meta_title', 60)->nullable();
    $table->string('meta_description', 160)->nullable();
    $table->text('meta_keywords')->nullable();
    $table->string('og_image')->nullable();
    $table->string('schema_type')->default('Product'); // Product|ItemPage

    // آمار
    $table->integer('view_count')->default(0);
    $table->integer('rfq_count')->default(0);

    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    // ایندکس‌ها
    $table->index(['category_id', 'status']);
    $table->index(['brand_id', 'status']);
    $table->index(['price_range', 'in_stock']);
    $table->fullText(['name', 'model', 'description']); // جستجوی متنی
});
```

### ۳.۵ جدول مقالات بلاگ

```php
// database/migrations/2024_01_01_000005_create_blog_posts_table.php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt', 300);
    $table->longText('content');                        // HTML کامل مقاله
    $table->string('author')->nullable();
    $table->string('category');                         // راهنمای خرید | مقاله تخصصی
    $table->json('product_categories')->nullable();     // ["flow-meters","gas-detectors"]
    $table->json('product_types')->nullable();          // ["electromagnetic-flow"]
    $table->json('tags')->nullable();
    $table->string('image')->nullable();
    $table->string('read_time')->nullable();            // ۸ دقیقه
    $table->string('meta_title', 60)->nullable();
    $table->string('meta_description', 160)->nullable();
    $table->text('meta_keywords')->nullable();
    $table->enum('status', ['published','draft'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->integer('view_count')->default(0);
    $table->timestamps();
    $table->softDeletes();
});
```

### ۳.۶ جدول RFQ (استعلام قیمت)

```php
// database/migrations/2024_01_01_000006_create_rfq_requests_table.php
Schema::create('rfq_requests', function (Blueprint $table) {
    $table->id();
    $table->string('reference_number')->unique();       // RFQ-2024-0001
    $table->string('name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->string('company')->nullable();
    $table->string('position')->nullable();
    $table->text('notes')->nullable();
    $table->enum('status', ['pending','processing','quoted','closed'])->default('pending');
    $table->string('ip_address')->nullable();
    $table->timestamps();
});

Schema::create('rfq_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rfq_request_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $table->string('product_name');                     // ذخیره نام در صورت حذف محصول
    $table->string('product_model')->nullable();
    $table->integer('quantity')->default(1);
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### ۳.۷ جدول تنظیمات سایت

```php
// database/migrations/2024_01_01_000007_create_site_settings_table.php
Schema::create('site_settings', function (Blueprint $table) {
    $table->id();
    $table->string('group')->default('general');        // general|seo|social|contact|home
    $table->string('key')->unique();                    // home_hero_title
    $table->text('value')->nullable();
    $table->string('type')->default('text');            // text|image|json|boolean|html
    $table->string('label')->nullable();                // برچسب فارسی برای پنل
    $table->timestamps();
});
```

### ۳.۸ جدول اسلایدر صفحه اصلی

```php
// database/migrations/2024_01_01_000008_create_sliders_table.php
Schema::create('sliders', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('subtitle')->nullable();
    $table->text('description')->nullable();
    $table->string('image');
    $table->string('link')->nullable();
    $table->string('button_text')->nullable();
    $table->string('badge')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### ۳.۹ جدول پیام‌های تماس

```php
// database/migrations/2024_01_01_000009_create_contacts_table.php
Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->string('company')->nullable();
    $table->string('subject');
    $table->text('message');
    $table->enum('status', ['unread','read','replied'])->default('unread');
    $table->string('ip_address')->nullable();
    $table->timestamps();
});
```

### ۳.۱۰ جدول خبرنامه

```php
Schema::create('newsletter_subscribers', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->boolean('is_active')->default(true);
    $table->timestamp('subscribed_at')->useCurrent();
    $table->timestamps();
});
```

---

## ۴. مدل‌های Eloquent

### ۴.۱ مدل Category

```php
// app/Models/Category.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasSlug;

    protected $fillable = [
        'name', 'name_en', 'slug', 'description', 'long_description',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image',
        'faq_schema', 'hero_title', 'hero_subtitle', 'icon', 'image',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'faq_schema' => 'array',
        'is_active'  => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name_en')
            ->saveSlugsTo('slug');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Accessor: محتوای سئو کامل با fallback
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?? "خرید و استعلام {$this->name} | تول‌مستر";
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?? $this->description ?? '';
    }

    // تولید JSON-LD BreadcrumbList
    public function getBreadcrumbSchemaAttribute(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => config('app.frontend_url')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'محصولات', 'item' => config('app.frontend_url') . '/products'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $this->name, 'item' => config('app.frontend_url') . '/products/category/' . $this->slug],
            ]
        ];
    }
}
```

### ۴.۲ مدل Product

```php
// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasSlug, SoftDeletes, Searchable;

    protected $fillable = [
        'name', 'model', 'slug', 'category_id', 'subcategory_id', 'brand_id',
        'country', 'description', 'long_description', 'excerpt',
        'specs', 'usage', 'applications', 'price_range',
        'in_stock', 'is_featured', 'status',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'schema_type',
        'view_count', 'rfq_count', 'sort_order',
    ];

    protected $casts = [
        'specs'        => 'array',
        'usage'        => 'array',
        'applications' => 'array',
        'in_stock'     => 'boolean',
        'is_featured'  => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'model'])
            ->saveSlugsTo('slug');
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Scout: فیلدهای قابل جستجو
    public function toSearchableArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'model'       => $this->model,
            'description' => $this->description,
            'brand'       => $this->brand?->name,
            'category'    => $this->category?->name,
        ];
    }

    // JSON-LD Schema برای سئو
    public function getProductSchemaAttribute(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => $this->name,
            'model'    => $this->model,
            'description' => $this->description,
            'brand'    => ['@type' => 'Brand', 'name' => $this->brand?->name],
            'offers'   => [
                '@type'        => 'Offer',
                'availability' => $this->in_stock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'priceCurrency' => 'IRR',
                'seller'       => ['@type' => 'Organization', 'name' => 'ToolMaster'],
            ],
        ];
    }

    // Scope: فیلترها
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByCategory($query, string $slug)
    {
        return $query->whereHas('category', fn($q) => $q->where('slug', $slug));
    }

    public function scopeByBrand($query, string $slug)
    {
        return $query->whereHas('brand', fn($q) => $q->where('slug', $slug));
    }

    public function scopeByType($query, string $subSlug)
    {
        return $query->whereHas('subcategory', fn($q) => $q->where('slug', $subSlug));
    }

    public function scopeFiltered($query, array $filters)
    {
        if (!empty($filters['category']))
            $query->byCategory($filters['category']);

        if (!empty($filters['brand']))
            $query->byBrand($filters['brand']);

        if (!empty($filters['type']))
            $query->byType($filters['type']);

        if (!empty($filters['country']))
            $query->where('country', $filters['country']);

        if (!empty($filters['price_range']))
            $query->where('price_range', $filters['price_range']);

        if (isset($filters['in_stock']))
            $query->where('in_stock', (bool)$filters['in_stock']);

        if (!empty($filters['usage']))
            $query->whereJsonContains('usage', $filters['usage']);

        if (!empty($filters['search']))
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('model', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });

        return $query;
    }
}
```

### ۴.۳ مدل RFQ

```php
// app/Models/RfqRequest.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqRequest extends Model
{
    protected $fillable = [
        'reference_number', 'name', 'email', 'phone',
        'company', 'position', 'notes', 'status', 'ip_address',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->reference_number = static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        $year  = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return "RFQ-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }
}
```

---

## ۵. API Resources (تبدیل داده برای React)

### ۵.۱ ProductResource

```php
// app/Http/Resources/ProductResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->slug,          // React از slug استفاده می‌کند
            'name'         => $this->name,
            'model'        => $this->model,
            'type'         => $this->subcategory?->slug,
            'category'     => $this->category?->slug,
            'brand'        => $this->brand?->name,
            'brandSlug'    => $this->brand?->slug,
            'country'      => $this->country,
            'usage'        => $this->usage ?? [],
            'priceRange'   => $this->price_range,
            'applications' => $this->applications ?? [],
            'inStock'      => $this->in_stock,
            'isFeatured'   => $this->is_featured,
            'description'  => $this->description,
            'longDescription' => $this->long_description,
            'image'        => $this->getFirstMediaUrl('products') ?: $this->og_image,
            'specs'        => $this->specs,

            // سئو
            'seo' => [
                'title'       => $this->meta_title ?? "خرید {$this->name} | تول‌مستر",
                'description' => $this->meta_description ?? $this->excerpt ?? $this->description,
                'keywords'    => $this->meta_keywords,
                'schema'      => $this->product_schema,
            ],
        ];
    }
}
```

### ۵.۲ CategoryResource

```php
// app/Http/Resources/CategoryResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->slug,
            'label'       => $this->name,
            'description' => $this->description,
            'longDescription' => $this->long_description,
            'image'       => $this->image,
            'icon'        => $this->icon,
            'productCount' => $this->products_count ?? 0,
            'subcategories' => SubcategoryResource::collection(
                $this->whenLoaded('subcategories')
            ),
            'seo' => [
                'title'       => $this->seo_title,
                'description' => $this->seo_description,
                'keywords'    => $this->meta_keywords,
                'heroTitle'   => $this->hero_title ?? "خرید {$this->name}",
                'heroSubtitle' => $this->hero_subtitle,
                'schema'      => $this->breadcrumb_schema,
                'faqSchema'   => $this->faq_schema,
            ],
        ];
    }
}
```

---

## ۶. کنترلرهای API

### ۶.۱ ProductController

```php
// app/Http/Controllers/Api/V1/ProductController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    // GET /api/v1/products
    public function index(Request $request)
    {
        $filters = $request->only([
            'category', 'brand', 'type', 'country',
            'price_range', 'in_stock', 'usage', 'search'
        ]);

        $cacheKey = 'products.' . md5(serialize($filters));

        $products = Cache::remember($cacheKey, 300, function () use ($filters, $request) {
            return Product::published()
                ->with(['category', 'subcategory', 'brand'])
                ->withCount('rfqItems as rfq_count')
                ->filtered($filters)
                ->orderBy('sort_order')
                ->orderByDesc('is_featured')
                ->paginate($request->get('per_page', 15));
        });

        return ProductResource::collection($products);
    }

    // GET /api/v1/products/{slug}
    public function show(string $slug)
    {
        $product = Cache::remember("product.{$slug}", 600, function () use ($slug) {
            return Product::with(['category', 'subcategory', 'brand'])
                ->where('slug', $slug)
                ->published()
                ->firstOrFail();
        });

        // افزایش شمارنده بازدید
        Product::where('slug', $slug)->increment('view_count');

        return new ProductResource($product);
    }

    // GET /api/v1/products/featured
    public function featured()
    {
        $products = Cache::remember('products.featured', 600, function () {
            return Product::published()
                ->where('is_featured', true)
                ->with(['category', 'brand'])
                ->orderBy('sort_order')
                ->limit(8)
                ->get();
        });

        return ProductResource::collection($products);
    }

    // GET /api/v1/products/{slug}/similar
    public function similar(string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $similar = Product::published()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with(['category', 'brand'])
            ->limit(4)
            ->get();

        return ProductResource::collection($similar);
    }
}
```

### ۶.۲ RFQController

```php
// app/Http/Controllers/Api/V1/RFQController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\RfqConfirmation;
use App\Mail\RfqNotification;
use App\Models\Product;
use App\Models\RfqRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RFQController extends Controller
{
    // POST /api/v1/rfq
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'email'           => 'required|email',
            'phone'           => 'nullable|string|max:20',
            'company'         => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'required|string',            // product slug
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rfq = RfqRequest::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'company'    => $request->company,
            'notes'      => $request->notes,
            'ip_address' => $request->ip(),
        ]);

        // آیتم‌های RFQ
        foreach ($request->items as $item) {
            $product = Product::where('slug', $item['id'])->first();
            $rfq->items()->create([
                'product_id'    => $product?->id,
                'product_name'  => $product?->name ?? $item['id'],
                'product_model' => $product?->model,
                'quantity'      => $item['quantity'],
                'notes'         => $item['notes'] ?? null,
            ]);

            // افزایش شمارنده RFQ محصول
            $product?->increment('rfq_count');
        }

        // ارسال ایمیل تأییدیه به مشتری
        Mail::to($rfq->email)->send(new RfqConfirmation($rfq));

        // ارسال اعلان به ادمین
        Mail::to(config('mail.admin_email', 'admin@toolmaster.com'))
            ->send(new RfqNotification($rfq));

        return response()->json([
            'message'          => 'درخواست استعلام با موفقیت ثبت شد.',
            'reference_number' => $rfq->reference_number,
        ], 201);
    }

    // GET /api/v1/rfq/{reference}
    public function show(string $reference)
    {
        $rfq = RfqRequest::with('items.product')
            ->where('reference_number', $reference)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'reference'   => $rfq->reference_number,
                'status'      => $rfq->status,
                'name'        => $rfq->name,
                'itemCount'   => $rfq->items->count(),
                'created_at'  => $rfq->created_at->format('Y-m-d'),
            ]
        ]);
    }
}
```

### ۶.۳ SearchController

```php
// app/Http/Controllers/Api/V1/SearchController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    // GET /api/v1/search?q=siemens
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        // جستجو در محصولات
        $products = Product::published()
            ->with(['category', 'brand'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('model', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        // جستجو در مقالات
        $articles = BlogPost::where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->limit(3)
            ->get(['id', 'title', 'slug', 'category', 'image']);

        // جستجو در دسته‌بندی‌ها
        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get(['id', 'name', 'slug', 'icon']);

        return response()->json([
            'results' => [
                'products'   => ProductResource::collection($products),
                'articles'   => $articles,
                'categories' => $categories,
            ]
        ]);
    }
}
```

### ۶.۴ SettingsController

```php
// app/Http/Controllers/Api/V1/SettingsController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __invoke()
    {
        $settings = Cache::remember('site_settings', 3600, function () {
            return SiteSetting::all()->groupBy('group')->map(function ($group) {
                return $group->pluck('value', 'key');
            });
        });

        return response()->json(['data' => $settings]);
    }
}
```

---

## ۷. Routes (مسیرهای API)

```php
// routes/api.php
<?php

use App\Http\Controllers\Api\V1\{
    ProductController,
    CategoryController,
    BrandController,
    BlogController,
    RFQController,
    ContactController,
    SearchController,
    SettingsController,
    SliderController,
    NewsletterController,
};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ──── Products ────
    Route::get('products',                    [ProductController::class, 'index']);
    Route::get('products/featured',           [ProductController::class, 'featured']);
    Route::get('products/{slug}',             [ProductController::class, 'show']);
    Route::get('products/{slug}/similar',     [ProductController::class, 'similar']);

    // ──── Categories ────
    Route::get('categories',                  [CategoryController::class, 'index']);
    Route::get('categories/{slug}',           [CategoryController::class, 'show']);
    Route::get('categories/{slug}/products',  [CategoryController::class, 'products']);

    // ──── Brands ────
    Route::get('brands',                      [BrandController::class, 'index']);
    Route::get('brands/{slug}',               [BrandController::class, 'show']);
    Route::get('brands/{slug}/products',      [BrandController::class, 'products']);

    // ──── Blog ────
    Route::get('blog',                        [BlogController::class, 'index']);
    Route::get('blog/latest',                 [BlogController::class, 'latest']);
    Route::get('blog/{slug}',                 [BlogController::class, 'show']);

    // ──── Search ────
    Route::get('search',                      SearchController::class);

    // ──── Site-wide ────
    Route::get('settings',                    SettingsController::class);
    Route::get('sliders',                     SliderController::class);

    // ──── Forms ────
    Route::post('rfq',                        [RFQController::class, 'store']);
    Route::get('rfq/{reference}',             [RFQController::class, 'show']);
    Route::post('contact',                    [ContactController::class, 'store']);
    Route::post('newsletter',                 [NewsletterController::class, 'store']);
});
```

---

## ۸. CORS

```php
// config/cors.php
return [
    'paths'                => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'      => ['*'],
    'allowed_origins'      => [env('FRONTEND_URL', 'https://toolmaster.com')],
    'allowed_origins_patterns' => [],
    'allowed_headers'      => ['*'],
    'exposed_headers'      => [],
    'max_age'              => 86400,
    'supports_credentials' => false,
];
```

---

## ۹. پنل Filament (Admin Panel)

### ۹.۱ ProductResource در Filament

```php
// app/Filament/Resources/ProductResource.php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\{
    Tabs, TextInput, Textarea, RichEditor, Select,
    Toggle, FileUpload, KeyValue, Section, Grid, Placeholder
};
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, BadgeColumn, ImageColumn, IconColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction};

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'محصولات';
    protected static ?string $modelLabel      = 'محصول';
    protected static ?string $navigationGroup = 'محتوا';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Tabs::make('محصول')->tabs([

                // ─── تب ۱: اطلاعات پایه ───
                Tabs\Tab::make('اطلاعات پایه')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('نام محصول (فارسی)')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('model')
                            ->label('مدل')
                            ->maxLength(100),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('category_id')
                            ->label('دسته‌بندی')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('subcategory_id')
                            ->label('زیرمجموعه')
                            ->relationship('subcategory', 'name')
                            ->searchable(),
                        Select::make('brand_id')
                            ->label('برند')
                            ->relationship('brand', 'name')
                            ->searchable(),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('country')
                            ->label('کشور سازنده')
                            ->options([
                                'DE' => 'آلمان', 'US' => 'آمریکا', 'UK' => 'انگلستان',
                                'JP' => 'ژاپن', 'CH' => 'سوئیس', 'NL' => 'هلند',
                            ]),
                        Select::make('price_range')
                            ->label('رنج قیمتی')
                            ->options(['budget' => 'ارزان', 'mid' => 'متوسط', 'premium' => 'گران']),
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(['published' => 'منتشر شده', 'draft' => 'پیش‌نویس', 'archived' => 'آرشیو'])
                            ->default('draft'),
                    ]),
                    Textarea::make('description')
                        ->label('توضیحات کوتاه')
                        ->rows(3)
                        ->required(),
                    RichEditor::make('long_description')
                        ->label('محتوای کامل (سئو) — حداقل ۵۰۰ کلمه')
                        ->toolbarButtons(['bold','italic','h2','h3','bulletList','orderedList','link'])
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Toggle::make('in_stock')->label('موجود در انبار')->default(true),
                        Toggle::make('is_featured')->label('محصول ویژه'),
                    ]),
                ])->columns(1),

                // ─── تب ۲: مشخصات فنی ───
                Tabs\Tab::make('مشخصات فنی')->schema([
                    KeyValue::make('specs')
                        ->label('مشخصات فنی (کلید: مقدار)')
                        ->addButtonLabel('افزودن مشخصه')
                        ->keyLabel('نام مشخصه (مثال: accuracy)')
                        ->valueLabel('مقدار (مثال: ±0.2%)')
                        ->columnSpanFull(),
                    Select::make('usage')
                        ->label('نوع کاربرد')
                        ->multiple()
                        ->options(['educational' => 'آموزشی', 'research' => 'پژوهشی', 'industrial' => 'صنعتی']),
                    TextInput::make('applications')
                        ->label('کاربردها (با کاما جدا کنید)')
                        ->helperText('مثال: GC-MS,LCMS,آنالیز عنصری'),
                ])->columns(1),

                // ─── تب ۳: تصاویر ───
                Tabs\Tab::make('تصاویر و مدیا')->schema([
                    FileUpload::make('images')
                        ->label('تصاویر محصول')
                        ->image()
                        ->multiple()
                        ->maxFiles(10)
                        ->imageResizeMode('cover')
                        ->imageResizeTargetWidth(1200)
                        ->imageResizeTargetHeight(900)
                        ->directory('products'),
                ])->columns(1),

                // ─── تب ۴: سئو ───
                Tabs\Tab::make('سئو پیشرفته')->schema([
                    TextInput::make('meta_title')
                        ->label('عنوان متا (حداکثر ۶۰ کاراکتر)')
                        ->maxLength(60)
                        ->helperText('مثال: خرید فلوکنترلر جرمی Bronkhorst | تول‌مستر'),
                    Textarea::make('meta_description')
                        ->label('توضیحات متا (حداکثر ۱۶۰ کاراکتر)')
                        ->maxLength(160)
                        ->rows(2),
                    TextInput::make('meta_keywords')
                        ->label('کلمات کلیدی (با کاما جدا)')
                        ->helperText('مثال: فلوکنترلر,MFC,Bronkhorst,قیمت'),
                    Select::make('schema_type')
                        ->label('نوع Schema')
                        ->options(['Product' => 'Product', 'ItemPage' => 'ItemPage'])
                        ->default('Product'),
                ])->columns(1),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                ImageColumn::make('og_image')->label('تصویر')->circular(),
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('model')->label('مدل')->searchable(),
                TextColumn::make('category.name')->label('دسته')->badge(),
                TextColumn::make('brand.name')->label('برند'),
                BadgeColumn::make('status')->label('وضعیت')
                    ->colors(['success' => 'published', 'warning' => 'draft', 'danger' => 'archived']),
                IconColumn::make('in_stock')->label('موجودی')->boolean(),
                TextColumn::make('view_count')->label('بازدید')->sortable(),
                TextColumn::make('rfq_count')->label('استعلام')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['published' => 'منتشر', 'draft' => 'پیش‌نویس']),
                SelectFilter::make('category_id')->relationship('category', 'name')->label('دسته'),
                SelectFilter::make('brand_id')->relationship('brand', 'name')->label('برند'),
                TernaryFilter::make('in_stock')->label('موجودی'),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
```

### ۹.۲ SiteSettingsResource در Filament

```php
// app/Filament/Pages/SiteSettings.php
<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\{Tabs, TextInput, Textarea, FileUpload, Toggle, RichEditor};
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SiteSettings extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'تنظیمات سایت';
    protected static ?string $title           = 'تنظیمات سایت';
    protected static ?string $navigationGroup = 'تنظیمات';
    protected static string $view = 'filament.pages.site-settings';

    public array $settings = [];

    public function mount(): void
    {
        $this->settings = SiteSetting::all()->pluck('value', 'key')->toArray();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make()->tabs([

                // ─── صفحه اصلی ───
                Tabs\Tab::make('صفحه اصلی')->schema([
                    TextInput::make('settings.home_hero_title')
                        ->label('عنوان اصلی هیرو'),
                    Textarea::make('settings.home_hero_subtitle')
                        ->label('زیرعنوان هیرو')
                        ->rows(2),
                    TextInput::make('settings.home_hero_badge')
                        ->label('بج هیرو (مثال: ارائه‌دهنده رسمی)'),
                    TextInput::make('settings.home_hero_button_primary')
                        ->label('متن دکمه اصلی'),
                    TextInput::make('settings.home_hero_button_secondary')
                        ->label('متن دکمه ثانویه'),
                    TextInput::make('settings.home_stats_products')
                        ->label('آمار: تعداد محصولات'),
                    TextInput::make('settings.home_stats_brands')
                        ->label('آمار: تعداد برندها'),
                    TextInput::make('settings.home_stats_customers')
                        ->label('آمار: تعداد مشتریان'),
                    RichEditor::make('settings.home_seo_content')
                        ->label('محتوای سئو صفحه اصلی'),
                ]),

                // ─── اطلاعات عمومی ───
                Tabs\Tab::make('اطلاعات شرکت')->schema([
                    TextInput::make('settings.site_name')->label('نام سایت'),
                    TextInput::make('settings.site_phone')->label('تلفن'),
                    TextInput::make('settings.site_email')->label('ایمیل'),
                    TextInput::make('settings.site_address')->label('آدرس'),
                    FileUpload::make('settings.site_logo')->label('لوگو')->image(),
                    FileUpload::make('settings.site_favicon')->label('Favicon')->image(),
                ]),

                // ─── شبکه‌های اجتماعی ───
                Tabs\Tab::make('شبکه‌های اجتماعی')->schema([
                    TextInput::make('settings.social_instagram')->label('اینستاگرام')->url(),
                    TextInput::make('settings.social_linkedin')->label('لینکدین')->url(),
                    TextInput::make('settings.social_telegram')->label('تلگرام')->url(),
                    TextInput::make('settings.social_whatsapp')->label('واتساپ'),
                ]),

                // ─── سئو سایت‌وایده ───
                Tabs\Tab::make('سئو سراسری')->schema([
                    TextInput::make('settings.seo_site_title')->label('عنوان کلی سایت (60 کاراکتر)'),
                    Textarea::make('settings.seo_site_description')->label('توضیحات کلی (160 کاراکتر)')->rows(2),
                    TextInput::make('settings.seo_google_analytics')->label('Google Analytics ID'),
                    TextInput::make('settings.seo_google_search_console')->label('Google Search Console کد'),
                    Textarea::make('settings.seo_custom_head_scripts')->label('اسکریپت‌های هد سفارشی')->rows(4),
                ]),
            ])->columnSpanFull(),
        ])->statePath('settings');
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // پاک کردن کش
        \Illuminate\Support\Facades\Cache::forget('site_settings');

        Notification::make()->title('تنظیمات با موفقیت ذخیره شد')->success()->send();
    }
}
```

---

## ۱۰. Sitemap خودکار

```php
// app/Console/Commands/GenerateSitemap.php
<?php

namespace App\Console\Commands;

use App\Models\{Category, Brand, Product, BlogPost, Subcategory};
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate XML sitemap';

    public function handle(): void
    {
        $frontend = config('app.frontend_url', 'https://toolmaster.com');
        $sitemap  = Sitemap::create();

        // صفحات ثابت
        $sitemap->add(Url::create($frontend)->setPriority(1.0)->setChangeFrequency('daily'));
        $sitemap->add(Url::create("{$frontend}/products")->setPriority(0.9));
        $sitemap->add(Url::create("{$frontend}/brands")->setPriority(0.8));
        $sitemap->add(Url::create("{$frontend}/blog")->setPriority(0.8));
        $sitemap->add(Url::create("{$frontend}/about")->setPriority(0.6));
        $sitemap->add(Url::create("{$frontend}/contact")->setPriority(0.6));

        // دسته‌بندی‌ها
        Category::active()->each(function ($cat) use ($sitemap, $frontend) {
            $sitemap->add(
                Url::create("{$frontend}/products/category/{$cat->slug}")
                    ->setPriority(0.85)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($cat->updated_at)
            );
        });

        // زیرمجموعه‌ها
        Subcategory::active()->each(function ($sub) use ($sitemap, $frontend) {
            $sitemap->add(
                Url::create("{$frontend}/products/category/{$sub->category->slug}/{$sub->slug}")
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
            );
        });

        // برندها
        Brand::active()->each(function ($brand) use ($sitemap, $frontend) {
            $sitemap->add(
                Url::create("{$frontend}/brands/{$brand->slug}")
                    ->setPriority(0.75)
                    ->setChangeFrequency('weekly')
            );
        });

        // محصولات
        Product::published()->each(function ($product) use ($sitemap, $frontend) {
            $sitemap->add(
                Url::create("{$frontend}/products/{$product->slug}")
                    ->setPriority(0.7)
                    ->setChangeFrequency('monthly')
                    ->setLastModificationDate($product->updated_at)
            );
        });

        // مقالات بلاگ
        BlogPost::published()->each(function ($post) use ($sitemap, $frontend) {
            $sitemap->add(
                Url::create("{$frontend}/blog/{$post->slug}")
                    ->setPriority(0.65)
                    ->setChangeFrequency('monthly')
                    ->setLastModificationDate($post->updated_at)
            );
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated successfully!');
    }
}

// زمانبندی خودکار در app/Console/Kernel.php:
// $schedule->command('sitemap:generate')->daily();
```

---

## ۱۱. ایمیل‌ها

```php
// app/Mail/RfqConfirmation.php
<?php

namespace App\Mail;

use App\Models\RfqRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};

class RfqConfirmation extends Mailable
{
    public function __construct(public RfqRequest $rfq) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "تأیید استعلام قیمت - {$this->rfq->reference_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rfq-confirmation');
    }
}
```

```blade
{{-- resources/views/emails/rfq-confirmation.blade.php --}}
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<body style="font-family: Tahoma, sans-serif; direction: rtl;">
  <h2>درخواست استعلام شما ثبت شد</h2>
  <p>{{ $rfq->name }} عزیز،</p>
  <p>درخواست استعلام قیمت شما با شماره پیگیری <strong>{{ $rfq->reference_number }}</strong> ثبت شد.</p>
  <p>کارشناسان ما ظرف ۲۴ ساعت کاری با شما تماس خواهند گرفت.</p>
  <hr>
  <p>تول‌مستر — تجهیزات ابزار دقیق صنعتی</p>
</body>
</html>
```

---

## ۱۲. Seeder داده‌های اولیه

```php
// database/seeders/CategorySeeder.php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'    => 'ژنراتورهای گاز',
                'name_en' => 'gas-generators',
                'slug'    => 'gas-generators',
                'icon'    => 'Zap',
                'sort_order' => 1,
                'subcategories' => [
                    ['name' => 'ژنراتور هیدروژن', 'slug' => 'hydrogen-gen', 'full_name_en' => 'Hydrogen Generator'],
                    ['name' => 'ژنراتور نیتروژن', 'slug' => 'nitrogen-gen', 'full_name_en' => 'Nitrogen Generator'],
                    ['name' => 'ژنراتور هوای خشک', 'slug' => 'dry-air-gen', 'full_name_en' => 'Dry Air Generator'],
                ],
            ],
            [
                'name'    => 'پمپ‌های آزمایشگاهی',
                'name_en' => 'lab-pumps',
                'slug'    => 'lab-pumps',
                'icon'    => 'Droplets',
                'sort_order' => 2,
                'subcategories' => [
                    ['name' => 'پمپ خلاء روتاری', 'slug' => 'vacuum-pump', 'full_name_en' => 'Rotary Vacuum Pump'],
                    ['name' => 'پمپ پریستالتیک', 'slug' => 'peristaltic-pump', 'full_name_en' => 'Peristaltic Pump'],
                    ['name' => 'پمپ دیافراگمی', 'slug' => 'diaphragm-pump', 'full_name_en' => 'Diaphragm Pump'],
                ],
            ],
            [
                'name'    => 'دتکتورهای گاز',
                'name_en' => 'gas-detectors',
                'slug'    => 'gas-detectors',
                'icon'    => 'AlertTriangle',
                'sort_order' => 3,
                'subcategories' => [
                    ['name' => 'دتکتور گاز سمی', 'slug' => 'toxic-detector', 'full_name_en' => 'Toxic Gas Detector'],
                    ['name' => 'دتکتور گاز قابل اشتعال', 'slug' => 'flammable-detector', 'full_name_en' => 'Flammable Gas Detector'],
                    ['name' => 'دتکتور چند گازی', 'slug' => 'multi-gas', 'full_name_en' => 'Multi-Gas Detector'],
                ],
            ],
            [
                'name'    => 'فلومتر و فلوکنترلر',
                'name_en' => 'flow-meters',
                'slug'    => 'flow-meters',
                'icon'    => 'Gauge',
                'sort_order' => 4,
                'subcategories' => [
                    ['name' => 'فلومتر الکترومغناطیسی', 'slug' => 'electromagnetic-flow', 'full_name_en' => 'Electromagnetic Flow Meter'],
                    ['name' => 'فلوکنترلر جرمی', 'slug' => 'mass-flow-controller', 'full_name_en' => 'Mass Flow Controller'],
                    ['name' => 'فلومتر اولتراسونیک', 'slug' => 'ultrasonic-flow', 'full_name_en' => 'Ultrasonic Flow Meter'],
                ],
            ],
            [
                'name'    => 'تجهیزات PLC',
                'name_en' => 'plc-equipment',
                'slug'    => 'plc-equipment',
                'icon'    => 'Cpu',
                'sort_order' => 5,
                'subcategories' => [
                    ['name' => 'ماژول CPU', 'slug' => 'plc-cpu', 'full_name_en' => 'PLC CPU Module'],
                    ['name' => 'ماژول ورودی/خروجی', 'slug' => 'plc-io', 'full_name_en' => 'PLC I/O Module'],
                    ['name' => 'پنل HMI', 'slug' => 'hmi-panel', 'full_name_en' => 'HMI Touch Panel'],
                ],
            ],
            [
                'name'    => 'کالیبراسیون و لوازم جانبی',
                'name_en' => 'calibration',
                'slug'    => 'calibration',
                'icon'    => 'Settings',
                'sort_order' => 6,
                'subcategories' => [],
            ],
        ];

        foreach ($categories as $data) {
            $subcats = $data['subcategories'];
            unset($data['subcategories']);

            $cat = Category::create($data);

            foreach ($subcats as $sub) {
                $cat->subcategories()->create($sub);
            }
        }
    }
}
```

---

## ۱۳. خلاصه ساختار فایل‌ها

```
toolmaster-backend/
├── app/
│   ├── Console/Commands/GenerateSitemap.php
│   ├── Filament/
│   │   ├── Pages/SiteSettings.php
│   │   ├── Resources/
│   │   │   ├── ProductResource.php
│   │   │   ├── CategoryResource.php
│   │   │   ├── BrandResource.php
│   │   │   ├── BlogPostResource.php
│   │   │   ├── RfqResource.php
│   │   │   ├── ContactResource.php
│   │   │   └── SliderResource.php
│   │   └── Widgets/
│   │       ├── StatsOverview.php    ← آمار RFQ، محصولات، پیام‌های خوانده نشده
│   │       └── LatestRfqWidget.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── BrandController.php
│   │   │   ├── BlogController.php
│   │   │   ├── RFQController.php
│   │   │   ├── ContactController.php
│   │   │   ├── SearchController.php
│   │   │   ├── SettingsController.php
│   │   │   └── SliderController.php
│   │   └── Resources/
│   │       ├── ProductResource.php
│   │       ├── CategoryResource.php
│   │       └── BrandResource.php
│   ├── Mail/
│   │   ├── RfqConfirmation.php
│   │   └── RfqNotification.php
│   └── Models/
│       ├── Category.php
│       ├── Subcategory.php
│       ├── Brand.php
│       ├── Product.php
│       ├── BlogPost.php
│       ├── RfqRequest.php
│       ├── RfqItem.php
│       ├── SiteSetting.php
│       ├── Slider.php
│       └── Contact.php
├── database/
│   ├── migrations/       ← همه مهاجرت‌ها
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── BrandSeeder.php
│       ├── ProductSeeder.php
│       └── SiteSettingsSeeder.php
├── resources/views/
│   └── emails/
│       ├── rfq-confirmation.blade.php
│       └── rfq-notification.blade.php
└── routes/
    └── api.php
```

---

## ۱۴. ترتیب پیاده‌سازی (Roadmap)

| مرحله | کار | اولویت |
|-------|-----|--------|
| ۱ | نصب Laravel + Filament + Sanctum | 🔴 فوری |
| ۲ | اجرای همه migrations | 🔴 فوری |
| ۳ | CategorySeeder + BrandSeeder | 🔴 فوری |
| ۴ | CategoryController + ProductController | 🔴 فوری |
| ۵ | وصل کردن React به API | 🔴 فوری |
| ۶ | Filament ProductResource با تب‌های SEO | 🟠 مهم |
| ۷ | SiteSettings Page در Filament | 🟠 مهم |
| ۸ | RFQController + ایمیل تأییدیه | 🟠 مهم |
| ۹ | SliderResource + مدیریت صفحه اصلی | 🟡 متوسط |
| ۱۰ | BlogPostResource + ویرایشگر Rich Text | 🟡 متوسط |
| ۱۱ | SearchController + Scout + Meilisearch | 🟡 متوسط |
| ۱۲ | Sitemap خودکار | 🟢 بعداً |
| ۱۳ | Filament Shield (مدیریت نقش‌ها) | 🟢 بعداً |
