<?php

namespace App\Support;

class SecureUrl
{
    public static function ensureHttps(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    public static function media(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::ensureHttps($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
