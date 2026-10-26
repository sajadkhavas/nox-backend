<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'type',
        'value',
        'label',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $setting): void {
            if ($setting->type !== 'json' || ! in_array($setting->key, [
                'home.presentation',
                'home.section_copy',
                'home.local_store',
            ], true)) {
                return;
            }

            $incoming = json_decode((string) $setting->value, true);
            $original = json_decode((string) $setting->getOriginal('value'), true);
            if (! is_array($incoming) || ! is_array($original) || array_is_list($incoming)) {
                return;
            }

            $setting->value = json_encode(
                self::preserveMissingObjectKeys($incoming, $original),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        });
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->typedValue() ?? $default;
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    /**
     * Legacy SiteSettings still owns brand/contact/policy copy. When it writes
     * an older-shaped presentation object, keep extension keys introduced by
     * the dedicated Storefront Control Center instead of silently erasing them.
     * Explicit values present in the incoming object always win.
     *
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $original
     * @return array<string, mixed>
     */
    private static function preserveMissingObjectKeys(array $incoming, array $original): array
    {
        foreach ($original as $key => $value) {
            if (! array_key_exists($key, $incoming)) {
                $incoming[$key] = $value;

                continue;
            }

            if (
                is_array($incoming[$key])
                && is_array($value)
                && ! array_is_list($incoming[$key])
                && ! array_is_list($value)
            ) {
                $incoming[$key] = self::preserveMissingObjectKeys($incoming[$key], $value);
            }
        }

        return $incoming;
    }
}
