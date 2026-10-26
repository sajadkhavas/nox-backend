<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentPage extends Model
{
    protected $fillable = [
        'type',
        'slug',
        'title',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
