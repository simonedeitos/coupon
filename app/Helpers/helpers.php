<?php

declare(strict_types=1);

use App\Helpers\Response;
use App\Helpers\Url;

function app(?string $key = null): mixed
{
    $app = $GLOBALS['couponami'] ?? [];
    return $key === null ? $app : ($app[$key] ?? null);
}

function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = app('config');
    foreach ($segments as $segment) {
        if (! is_array($value) || ! array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim($uri, '/');
    return $path !== '/' ? rtrim($path, '/') : '/';
}

function request_input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function flash(string $key, mixed ...$payload): mixed
{
    if (count($payload) > 0) {
        $_SESSION['_flash'][$key] = $payload[0];
        return null;
    }

    return flash_get($key);
}

function flash_get(string $key, bool $peek = false): mixed
{
    $messages = $_SESSION['_flash'] ?? [];
    $message = $messages[$key] ?? null;
    if (! $peek) {
        unset($_SESSION['_flash'][$key]);
    }
    return $message;
}

function url(string $path = ''): string
{
    return Url::to($path);
}

function asset(string $path): string
{
    $basePath = (string) config('app.asset_url', '/assets');
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}

function css(string $file): string
{
    return asset('css/' . $file);
}

function js(string $file): string
{
    return asset('js/' . $file);
}

function image(string $file): string
{
    return asset('images/' . $file);
}

function csrf_token(): string
{
    return app('csrf')->token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function response_view(string $view, array $data = [], string $layout = 'frontend', int $status = 200): array
{
    return Response::html(app('view')->render($view, $data, $layout), $status);
}

function redirect(string $path, int $status = 302): array
{
    return Response::redirect(Url::to($path), $status);
}
