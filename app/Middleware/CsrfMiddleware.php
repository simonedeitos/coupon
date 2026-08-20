<?php

declare(strict_types=1);

namespace App\Middleware;

final class CsrfMiddleware
{
    public function handle(array $route): ?array
    {
        if (request_method() === 'POST' && ! app('csrf')->validate($_POST['_token'] ?? null)) {
            return response_view('frontend/pages/500', ['meta' => app('seo')->meta(['title' => 'Token CSRF non valido', 'path' => request_path()])], 'app', 419);
        }
        return null;
    }
}
