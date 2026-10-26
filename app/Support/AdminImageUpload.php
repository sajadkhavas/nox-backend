<?php

namespace App\Support;

final class AdminImageUpload
{
    public const MAX_WIDTH = 6000;

    public const MAX_HEIGHT = 6000;

    /**
     * @return array<int, string>
     */
    public static function acceptedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    public static function maxKilobytes(): int
    {
        return max(
            1,
            (int) floor(((int) config('media-library.max_file_size', 12 * 1024 * 1024)) / 1024),
        );
    }

    public static function maxMegabytesLabel(): string
    {
        $megabytes = self::maxKilobytes() / 1024;

        return rtrim(rtrim(number_format($megabytes, 2, '.', ''), '0'), '.').'MB';
    }

    /**
     * @return array<int, string>
     */
    public static function dimensionRules(): array
    {
        return [
            'dimensions:max_width='.self::MAX_WIDTH.',max_height='.self::MAX_HEIGHT,
        ];
    }
}
