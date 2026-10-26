<?php

namespace App\Support;

use App\Models\StorefrontMediaAsset;

final class StorefrontMediaOptions
{
    /** @return array<int, string> */
    public static function ids(): array
    {
        return StorefrontMediaAsset::query()->where('status', 'ready')->with('media')
            ->latest('updated_at')->limit(500)->get()
            ->filter(fn (StorefrontMediaAsset $asset): bool => $asset->isReady())
            ->mapWithKeys(fn (StorefrontMediaAsset $asset): array => [$asset->getKey() => $asset->title.' — #'.$asset->getKey()])
            ->all();
    }

    /** @return array<string, string> */
    public static function urls(?string $current = null): array
    {
        return self::options('previewUrl', $current);
    }

    /** @return array<string, string> */
    public static function paths(?string $current = null): array
    {
        return self::options('previewPath', $current);
    }

    /** @return array<string, string> */
    private static function options(string $method, ?string $current): array
    {
        $options = [];
        StorefrontMediaAsset::query()->where('status', 'ready')->with('media')
            ->latest('updated_at')->limit(500)->get()
            ->each(function (StorefrontMediaAsset $asset) use (&$options, $method): void {
                try {
                    $value = $asset->{$method}();
                    if ($value) {
                        $options[$value] = $asset->title.' — #'.$asset->getKey();
                    }
                } catch (\Throwable) {
                    // Damaged media cannot be offered for publication.
                }
            });

        if ($current && ! isset($options[$current])) {
            return [$current => 'تصویر فعلی — خارج از کتابخانه'] + $options;
        }

        return $options;
    }
}
