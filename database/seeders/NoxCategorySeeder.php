<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Illuminate\Support\Str;

class NoxCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'nox') {
            throw new RuntimeException('Run this seeder on the local NOX database named nox.');
        }

        DB::transaction(function (): void {
            $pants = $this->ensureCategory('pants', 'شلوار', null, 10, false);
            $this->ensureCategory('denim', 'شلوار جین و بگ', $pants->id, 11);
            $this->ensureCategory('cotton-pants', 'شلوار کتان', $pants->id, 12);
            $this->ensureCategory('jorts', 'جورتس', null, 13);
            $this->ensureCategory('tshirts', 'تیشرت', null, 20);
            $this->ensureCategory('sweatshirts', 'هودی و دورس', null, 30);
            $this->ensureCategory('jackets', 'کت', null, 40);
            $this->ensureCategory('face-masks', 'فیس ماسک', null, 50);
        });
    }

    private function ensureCategory(
        string $slug,
        string $name,
        ?int $parentId,
        int $order,
        bool $showOnHome = true,
    ): Category {
        $existing = Category::withTrashed()->where('slug', $slug)->first();
        if ($existing?->trashed()) {
            throw new RuntimeException("Restore or rename the archived category before onboarding: {$slug}");
        }
        if ($existing) {
            return $existing;
        }

        $category = new Category([
            'slug' => $slug,
            'name' => $name,
            'parent_id' => $parentId,
            'sort_order' => $order,
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
            'show_in_header' => true,
            'show_on_home' => $showOnHome,
            'meta_title' => $name.' | NOX Collection',
        ]);
        $category->public_id = (string) Str::ulid();
        $category->saveQuietly();

        return $category;
    }
}
