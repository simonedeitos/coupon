<?php

declare(strict_types=1);

namespace App\Helpers;

final class Str
{
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?: '';
        return trim($value, '-');
    }

    public static function headline(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }

    public static function limit(string $value, int $limit = 120): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 1) . '…' : $value;
    }
}
