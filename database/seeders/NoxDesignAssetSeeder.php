<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\StorefrontMediaAsset;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class NoxDesignAssetSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'nox') {
            throw new \RuntimeException('Run only on the NOX database named nox.');
        }
        $paths = [];
        foreach (glob(__DIR__.'/nox-assets/*.webp') ?: [] as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $title = 'NOX design / '.$key;
            $asset = StorefrontMediaAsset::query()->where('title', $title)->first();
            if (! $asset || ! $asset->isReady()) {
                $temporary = tempnam(sys_get_temp_dir(), 'nox-media-');
                if (! $temporary || ! copy($file, $temporary)) throw new \RuntimeException('Cannot prepare NOX media.');
                try {
                    $asset = StorefrontMediaAsset::createFromUpload(new UploadedFile($temporary, basename($file), 'image/webp', null, true), [
                        'title' => $title, 'alt_text' => 'تصویر اختصاصی فروشگاه پوشاک ناکس؛ '.$key,
                        'usage' => 'home', 'notes' => 'NOX website design assets; original uploads and AI-assisted photo restoration.',
                    ]);
                } finally { if (is_file($temporary)) unlink($temporary); }
            }
            $paths[$key] = $asset->previewPath();
        }
        $visuals = ['hero' => $paths['hero'], 'painting' => $paths['painting'], 'store' => $paths['store'],
            'gallery' => array_map(fn ($i) => $paths['art-'.$i], range(0, 3)), 'categories' => []];
        foreach (['pants','tshirts','sweatshirts','jorts','jackets','face-masks'] as $slug) {
            $visuals['categories'][$slug] = $paths['category-'.$slug];
            Category::query()->where('slug', $slug)->update(['image_path' => $paths['category-'.$slug]]);
        }
        StoreSetting::query()->updateOrCreate(['key' => 'home.nox_visuals'], ['group' => 'home', 'type' => 'json', 'label' => 'تصاویر طراحی ناکس', 'is_public' => true,
            'value' => json_encode($visuals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
        foreach (['home.presentation' => ['heroImagePath' => $paths['hero'], 'heroImageAlt' => 'ویترین فروشگاه پوشاک استریت‌ویر ناکس'], 'home.local_store' => ['imagePath' => $paths['store']]] as $key => $patch) {
            $setting = StoreSetting::query()->firstOrNew(['key' => $key]);
            $current = $setting->exists ? ($setting->typedValue() ?: []) : [];
            $setting->fill(['group' => 'home', 'type' => 'json', 'label' => $key, 'is_public' => true, 'value' => json_encode(array_replace($current, $patch), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)])->save();
        }
        $this->command?->info('NOX design images imported into backend media library. Products, prices and stock unchanged.');
    }
}
