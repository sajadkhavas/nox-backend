<?php

namespace App\Support;

use Dotenv\Dotenv;
use InvalidArgumentException;

final class NoxServiceEnvironment
{
    /** Update only explicit service keys; preserve APP_KEY, DB and other settings. */
    public static function update(string $contents, array $values): string
    {
        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/D', $key) || preg_match('/[\r\n\x00]/', (string) $value)) {
                throw new InvalidArgumentException('Invalid environment entry.');
            }
            $quoted = '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], (string) $value).'"';
            $line = $key.'='.$quoted;
            $pattern = '/^(?:export[ \t]+)?'.preg_quote($key, '/').'[ \t]*=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace_callback($pattern, static fn () => $line, $contents);
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }
        $parsed = Dotenv::parse($contents);
        foreach ($values as $key => $value) {
            if (($parsed[$key] ?? null) !== (string) $value) {
                throw new InvalidArgumentException('Environment validation failed.');
            }
        }
        return $contents;
    }
}
