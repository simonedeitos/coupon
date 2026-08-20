<?php

declare(strict_types=1);

namespace App\Helpers;

final class Response
{
    public static function html(string $content, int $status = 200, array $headers = []): array
    {
        return ['type' => 'html', 'content' => $content, 'status' => $status, 'headers' => $headers];
    }

    public static function json(array $data, int $status = 200, array $headers = []): array
    {
        return ['type' => 'json', 'content' => $data, 'status' => $status, 'headers' => $headers];
    }

    public static function xml(string $content, int $status = 200, array $headers = []): array
    {
        return ['type' => 'xml', 'content' => $content, 'status' => $status, 'headers' => $headers];
    }

    public static function redirect(string $location, int $status = 302, array $headers = []): array
    {
        return ['type' => 'redirect', 'content' => '', 'status' => $status, 'headers' => $headers + ['Location' => $location]];
    }
}
