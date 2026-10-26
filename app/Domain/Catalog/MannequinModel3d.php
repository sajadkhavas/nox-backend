<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MannequinModel3d
{
    public const COLLECTION = 'mannequin-model-3d';

    public static function maxBytes(): int
    {
        return max(1, (int) config('mannequin.model3d.max_bytes', 12 * 1024 * 1024));
    }

    public static function media(Product $product): ?Media
    {
        if (! $product->mannequin_enabled) {
            return null;
        }

        $media = $product->getFirstMedia(self::COLLECTION);

        return $media !== null && self::isValid($media) ? $media : null;
    }

    public static function isValid(Media $media): bool
    {
        $path = $media->getPath();

        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = filesize($path);

        if (! is_int($size) || $size < 20 || $size > self::maxBytes()) {
            return false;
        }

        if (strtolower((string) pathinfo($media->file_name, PATHINFO_EXTENSION)) !== 'glb') {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $header = fread($handle, 12);

            if (! is_string($header) || strlen($header) !== 12 || substr($header, 0, 4) !== 'glTF') {
                return false;
            }

            $version = unpack('Vversion', substr($header, 4, 4));
            $declaredLength = unpack('Vlength', substr($header, 8, 4));

            if (($version['version'] ?? null) !== 2 || ($declaredLength['length'] ?? null) !== $size) {
                return false;
            }

            $chunkHeader = fread($handle, 8);

            if (! is_string($chunkHeader) || strlen($chunkHeader) !== 8) {
                return false;
            }

            $chunkLength = unpack('Vlength', substr($chunkHeader, 0, 4));
            $chunkType = unpack('Vtype', substr($chunkHeader, 4, 4));
            $jsonLength = $chunkLength['length'] ?? 0;

            if (! is_int($jsonLength) || $jsonLength <= 0 || $jsonLength > $size - 20) {
                return false;
            }

            // GLB 2.0 requires the first chunk to be JSON (ASCII "JSON" in little endian).
            if (($chunkType['type'] ?? null) !== 0x4E4F534A) {
                return false;
            }

            $jsonBytes = fread($handle, $jsonLength);

            if (! is_string($jsonBytes) || strlen($jsonBytes) !== $jsonLength) {
                return false;
            }

            $document = json_decode(rtrim($jsonBytes, "\0 \t\r\n"), true);

            if (! is_array($document) || ($document['asset']['version'] ?? null) !== '2.0') {
                return false;
            }

            // A production GLB must be self-contained. Reject child resources that could
            // make the browser fetch arbitrary third-party URLs after the trusted model loads.
            foreach (['buffers', 'images'] as $collection) {
                $entries = $document[$collection] ?? [];

                if (! is_array($entries)) {
                    return false;
                }

                foreach ($entries as $entry) {
                    if (! is_array($entry) || ! array_key_exists('uri', $entry)) {
                        continue;
                    }

                    $uri = $entry['uri'];

                    if (! is_string($uri) || ! str_starts_with($uri, 'data:')) {
                        return false;
                    }
                }
            }

            return true;
        } finally {
            fclose($handle);
        }
    }
}
