<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$routes = array_merge(require BASE_PATH . '/routes/web.php', require BASE_PATH . '/routes/admin.php', require BASE_PATH . '/routes/api.php');
$path = request_path();
$method = request_method();

if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . ($path === '/' ? '' : $path);
    if (is_file($staticFile)) {
        return false;
    }
}

foreach ($routes as $route) {
    if ($route['method'] !== $method) {
        continue;
    }
    $pattern = preg_replace_callback('/\{([^}]+)\}/', static fn (array $match): string => '(?P<' . $match[1] . '>[^/]+)', $route['pattern']);
    $regex = '#^' . $pattern . '$#';
    if (! preg_match($regex, $path, $matches)) {
        continue;
    }
    $params = array_filter($matches, static fn ($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    $params = [...($route['defaults'] ?? []), ...$params];
    foreach ($params as $key => $value) {
        if (str_ends_with((string) $key, 'id') && is_string($value) && ctype_digit($value)) {
            $params[$key] = (int) $value;
        }
    }
    foreach ($route['middleware'] ?? [] as $middlewareClass) {
        $result = (new $middlewareClass())->handle($route);
        if (is_array($result)) {
            send_response($result);
            return;
        }
    }
    [$class, $action] = $route['handler'];
    $response = (new $class())->{$action}(...array_values($params));
    send_response($response);
    return;
}

send_response(response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Pagina non trovata', 'path' => $path])], 'app', 404));

function send_response(array $response): void
{
    http_response_code($response['status'] ?? 200);
    foreach ($response['headers'] ?? [] as $name => $value) {
        header($name . ': ' . $value);
    }
    switch ($response['type'] ?? 'html') {
        case 'json':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
        case 'xml':
            header('Content-Type: application/xml; charset=utf-8');
            echo $response['content'];
            break;
        case 'raw':
            echo $response['content'];
            break;
        case 'redirect':
            break;
        default:
            header('Content-Type: text/html; charset=utf-8');
            echo $response['content'];
    }
}
