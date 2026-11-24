<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\StorefrontMediaAsset;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Owner-approved six-category shop content. Does not publish or price products. */
class NoxShopCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'nox') {
            throw new \RuntimeException('Run only on the NOX database named nox.');
        }
        $catalog = json_decode(file_get_contents(__DIR__.'/nox-assets/catalog-content.json'), true, 512, JSON_THROW_ON_ERROR);
        $asset = StorefrontMediaAsset::query()->where('title', 'NOX shop / storefront-v1')->first();
        if (! $asset || ! $asset->isReady()) {
            $temporary = tempnam(sys_get_temp_dir(), 'nox-shop-');
            if (! $temporary || ! copy(__DIR__.'/nox-assets/shop-front.webp', $temporary)) {
                throw new \RuntimeException('Cannot prepare NOX shop image.');
            }
            try {
                $asset = StorefrontMediaAsset::createFromUpload(new UploadedFile($temporary, 'shop-front.webp', 'image/webp', null, true), [
                    'title' => 'NOX shop / storefront-v1',
                    'alt_text' => 'نمای ورودی فروشگاه پوشاک ناکس با تابلو و مانکن‌های استریت‌ویر',
                    'usage' => 'home', 'notes' => 'Client photograph, AI-assisted lighting and reflection retouch; WebP 1086x1448.',
                ]);
            } finally {
                if (is_file($temporary)) unlink($temporary);
            }
        }
        $shopPath = $asset->previewPath();
        if (! $shopPath) throw new \RuntimeException('Shop preview is not ready.');

        DB::transaction(function () use ($catalog, $shopPath): void {
            $visuals = StoreSetting::value('home.nox_visuals', []);
            $visuals['shop'] = $shopPath;
            $existingCatalog = StoreSetting::value('home.nox_catalog', []);
            $finalCatalog = array_replace($catalog, $existingCatalog);
            $finalCatalog['categories'] = array_replace($catalog['categories'], $existingCatalog['categories'] ?? []);
            $ids = [];
            foreach ($catalog['categories'] as $slug => $copy) {
                $category = Category::query()->where('slug', $slug)->first();
                if (! $category && $slug === 'hoodies') {
                    $category = Category::query()->where('slug', 'sweatshirts')->first();
                }
                $category ??= new Category;
                if (! $category->exists) $category->public_id = (string) Str::ulid();
                $imageKey = $slug === 'hoodies' ? 'sweatshirts' : $slug;
                $path = $visuals['categories'][$slug] ?? $visuals['categories'][$imageKey] ?? $category->image_path;
                $category->fill([
                    'slug' => $slug, 'name' => $copy['name'], 'parent_id' => null,
                    'description' => $finalCatalog['categories'][$slug]['intro'],
                    'image_path' => $path, 'sort_order' => count($ids) * 10 + 10,
                    'publication_status' => PublicationStatus::Published, 'is_active' => true,
                    'show_in_header' => true, 'show_on_home' => true,
                    'meta_title' => 'خرید '.$copy['name'].' ناکس | NOX Collection',
                    'meta_description' => 'خرید '.$copy['name'].' ناکس؛ مشاهده مدل‌ها، تصاویر و راهنمای سایز، مقایسه قیمت و موجودی و انتخاب پوشاک برای استایل شخصی شما.',
                ]);
                $category->saveQuietly();
                $ids[$slug] = $category->id;
                $visuals['categories'][$slug] = $path;
                $finalCatalog['categories'][$slug]['image'] ??= $path;
            }
            // Preserve legacy category records and products; merge their product membership into pants.
            $legacy = Category::query()->whereIn('slug', ['denim', 'cotton-pants'])->get();
            foreach ($legacy as $category) {
                Product::query()->where('category_id', $category->id)->update(['category_id' => $ids['pants']]);
                $category->fill(['is_active' => false, 'show_in_header' => false, 'show_on_home' => false])->saveQuietly();
            }
            $this->put('home.nox_visuals', $visuals, 'تصاویر طراحی ناکس');
            $this->put('home.nox_catalog', $finalCatalog, 'متن فروشگاه و راهنمای دسته‌های ناکس');
            $pages = StoreSetting::value('page.presentation', []);
            $pages['shop'] = array_replace($pages['shop'] ?? [], ['title' => $finalCatalog['shopTitle'], 'lede' => $finalCatalog['shopIntro'], 'metaTitle' => 'فروشگاه پوشاک استریت‌ویر ناکس | NOX Collection', 'metaDescription' => 'خرید پوشاک استریت‌ویر ناکس؛ تیشرت، هودی، شلوار، جورتس، جکت و فیس ماسک. مشاهده تصاویر، راهنمای سایز، قیمت و موجودی محصولات.', 'socialImagePath' => $shopPath]);
            StoreSetting::query()->updateOrCreate(['key'=>'page.presentation'], ['group'=>'page','type'=>'json','label'=>'صفحات و سئو','is_public'=>true,'value'=>json_encode($pages, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]); 
            $home = StoreSetting::value('home.presentation', []);
            $home['categoryOrder'] = array_keys($catalog['categories']);
            $this->put('home.presentation', $home, 'چیدمان صفحه اصلی');
        });
        $this->command?->info('NOX shop image, editable category guides and six categories ready. Product publication, prices and stock unchanged.');
    }

    private function put(string $key, array $value, string $label): void
    {
        StoreSetting::query()->updateOrCreate(['key' => $key], [
            'group' => 'home', 'type' => 'json', 'label' => $label, 'is_public' => true,
            'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
    }
}
