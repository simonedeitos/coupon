<?php

declare(strict_types=1);

namespace App\Helpers;

final class Url
{
    public static function to(string $path = ''): string
    {
        $base = rtrim((string) config('app.base_url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $path === '/' ? ($base ?: '/') : $base . $path;
    }

    public static function asset(string $path): string
    {
        return self::to($path);
    }
}
