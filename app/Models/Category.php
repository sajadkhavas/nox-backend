<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'icon_path',
        'meta_title',
        'meta_description',
        'publication_status',
        'is_active',
        'show_in_header',
        'show_on_home',
        'sort_order',
    ];

    protected $casts = [
        'publication_status' => PublicationStatus::class,
        'is_active' => 'boolean',
        'show_in_header' => 'boolean',
        'show_on_home' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            $category->public_id ??= (string) Str::ulid();
            $category->assertValidParent();
        });

        static::updating(function (self $category): void {
            if ($category->isDirty('parent_id')) {
                $category->assertValidParent();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', PublicationStatus::Published->value);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $cursorId = $this->parent_id;
        $visited = [];

        while ($cursorId !== null && $depth < 3) {
            if (isset($visited[$cursorId])) {
                break;
            }

            $visited[$cursorId] = true;
            $depth++;
            $cursorId = self::query()->whereKey($cursorId)->value('parent_id');
        }

        return $depth;
    }

    private function assertValidParent(): void
    {
        if ($this->parent_id === null) {
            return;
        }

        if ($this->exists && (int) $this->parent_id === (int) $this->getKey()) {
            throw new \DomainException('A category cannot be its own parent.');
        }

        $cursorId = (int) $this->parent_id;
        $visited = [];
        $depth = 1;

        while ($cursorId > 0) {
            if ($depth > 2) {
                throw new \DomainException('Category hierarchy is limited to three levels.');
            }

            if ($this->exists && $cursorId === (int) $this->getKey()) {
                throw new \DomainException('Category hierarchy cannot contain a cycle.');
            }

            if (isset($visited[$cursorId])) {
                throw new \DomainException('Category hierarchy cannot contain a cycle.');
            }

            $visited[$cursorId] = true;
            $parent = self::query()->select(['id', 'parent_id'])->find($cursorId);

            if ($parent === null || $parent->parent_id === null) {
                return;
            }

            $cursorId = (int) $parent->parent_id;
            $depth++;
        }
    }
}
