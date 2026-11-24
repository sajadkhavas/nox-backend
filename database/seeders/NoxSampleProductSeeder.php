<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Illuminate\Support\Str;

class NoxSampleProductSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'nox') {
            throw new RuntimeException('Run this seeder only on the local NOX database named nox.');
        }

        DB::transaction(function (): void {
            $this->call(NoxCategorySeeder::class);

            $samples = [
                'denim' => 'شلوار جین بگ',
                'cotton-pants' => 'شلوار کتان',
                'jorts' => 'جورتس جین',
                'tshirts' => 'تیشرت باکسی',
                'sweatshirts' => 'هودی اورسایز',
                'jackets' => 'کت استریت',
                'face-masks' => 'فیس ماسک',
            ];

            foreach ($samples as $categorySlug => $name) {
                $slug = 'nox-sample-'.$categorySlug;
                $code = strtoupper($slug);

                // Existing drafts (including their uploaded media) survive reruns.
                if (Product::withTrashed()->where('slug', $slug)->orWhere('product_code', $code)->exists()) {
                    continue;
                }

                $category = Category::query()->where('slug', $categorySlug)->firstOrFail();
                $product = new Product([
                    'category_id' => $category->id,
                    'name' => $name.' (نمونه)',
                    'slug' => $slug,
                    'product_code' => $code,
                    'publication_status' => PublicationStatus::Draft,
                    'is_active' => false,
                ]);
                $product->public_id = (string) Str::ulid();
                $product->saveQuietly();
            }
        });
    }
}
